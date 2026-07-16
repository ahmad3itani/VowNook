<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use App\Notifications\PaymentDisputeOpened;
use App\Support\Payments\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A couple + wedding + onboarded vendor + accepted booking (100k total,
     * 30k deposit, 8k platform fee), all internally consistent.
     *
     * @return array{couple: User, wedding: Wedding, vendor: VendorProfile, booking: Booking}
     */
    protected function scenario(array $vendorAttrs = [], array $bookingAttrs = []): array
    {
        $couple = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);
        $couple->forceFill(['current_wedding_id' => $wedding->id])->save();

        $vendor = VendorProfile::factory()->create(array_merge([
            'stripe_account_id' => 'acct_test_123',
            'stripe_charges_enabled' => true,
            'stripe_details_submitted' => true,
        ], $vendorAttrs));

        $inquiry = Inquiry::factory()->accepted()->create([
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $vendor->id,
            'couple_user_id' => $couple->id,
        ]);
        $offer = Offer::factory()->accepted()->create([
            'inquiry_id' => $inquiry->id,
            'total_cents' => 100000,
            'deposit_cents' => 30000,
        ]);
        $booking = Booking::factory()->create(array_merge([
            'inquiry_id' => $inquiry->id,
            'offer_id' => $offer->id,
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $vendor->id,
            'total_cents' => 100000,
            'deposit_cents' => 30000,
            'platform_fee_cents' => 8000,
            'status' => BookingStatus::PendingPayment,
        ], $bookingAttrs));

        return compact('couple', 'wedding', 'vendor', 'booking');
    }

    // ── Pure money math ──────────────────────────────────────────────────────

    public function test_deposit_and_balance_fees_sum_to_the_platform_fee(): void
    {
        ['booking' => $booking] = $this->scenario();
        $service = app(StripeService::class);

        $depositFee = $service->feeFor($booking, PaymentType::Deposit);
        $balanceFee = $service->feeFor($booking, PaymentType::Balance);

        $this->assertSame(2400, $depositFee); // round(8000 * 30000/100000)
        $this->assertSame(5600, $balanceFee);
        $this->assertSame($booking->platform_fee_cents, $depositFee + $balanceFee);
    }

    public function test_amounts_split_deposit_and_balance(): void
    {
        ['booking' => $booking] = $this->scenario();
        $service = app(StripeService::class);

        $this->assertSame(30000, $service->amountFor($booking, PaymentType::Deposit));
        $this->assertSame(70000, $service->amountFor($booking, PaymentType::Balance));
    }

    public function test_no_separate_deposit_collects_full_amount(): void
    {
        ['booking' => $booking] = $this->scenario(bookingAttrs: ['deposit_cents' => 0]);
        $service = app(StripeService::class);

        $this->assertSame(100000, $service->amountFor($booking, PaymentType::Deposit));
        $this->assertSame(0, $service->amountFor($booking, PaymentType::Balance));
    }

    public function test_checkout_params_are_a_destination_charge_with_application_fee(): void
    {
        ['booking' => $booking, 'vendor' => $vendor] = $this->scenario();
        $params = app(StripeService::class)->checkoutParams($booking, PaymentType::Deposit, 'https://ok', 'https://no');

        $this->assertSame('payment', $params['mode']);
        $this->assertSame(30000, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame(2400, $params['payment_intent_data']['application_fee_amount']);
        $this->assertSame($vendor->stripe_account_id, $params['payment_intent_data']['transfer_data']['destination']);
        $this->assertSame($booking->id, $params['metadata']['booking_id']);
        $this->assertSame('deposit', $params['metadata']['payment_type']);
    }

    public function test_record_pending_payment_writes_a_pending_row(): void
    {
        ['booking' => $booking] = $this->scenario();
        app(StripeService::class)->recordPendingPayment($booking, PaymentType::Deposit, 'cs_test_abc');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'type' => 'deposit',
            'amount_cents' => 30000,
            'application_fee_cents' => 2400,
            'status' => PaymentStatus::Pending->value,
            'stripe_session_id' => 'cs_test_abc',
        ]);
    }

    // ── Checkout controller (StripeService mocked — no network) ───────────────

    public function test_couple_can_start_deposit_checkout(): void
    {
        ['couple' => $couple, 'booking' => $booking] = $this->scenario();

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('amountFor')->andReturn(30000);
            $mock->shouldReceive('checkoutFor')->once()->andReturn('https://checkout.stripe.test/cs_123');
        });

        $this->actingAs($couple)
            ->post("/bookings/{$booking->id}/checkout/deposit")
            ->assertRedirect('https://checkout.stripe.test/cs_123')
            ->assertSessionHas('vn_pending_purchase.value', 300);
    }

    public function test_success_fires_a_purchase_conversion_with_the_stashed_amount(): void
    {
        ['couple' => $couple, 'booking' => $booking] = $this->scenario();

        $this->actingAs($couple)
            ->withSession(['vn_pending_purchase' => ['booking_id' => $booking->id, 'value' => 300]])
            ->get(route('payments.success', $booking))
            ->assertRedirect()
            ->assertSessionHas('conversion.ga', 'purchase')
            ->assertSessionHas('conversion.meta', 'Purchase');
    }

    public function test_cannot_pay_a_booking_from_another_wedding(): void
    {
        $this->scenario(); // unrelated booking
        $other = User::factory()->create();
        $otherWedding = Wedding::factory()->create(['owner_id' => $other->id]);
        $other->forceFill(['current_wedding_id' => $otherWedding->id])->save();

        ['booking' => $booking] = $this->scenario();

        $this->actingAs($other)
            ->post("/bookings/{$booking->id}/checkout/deposit")
            ->assertForbidden();
    }

    public function test_cannot_pay_when_vendor_is_not_onboarded(): void
    {
        ['couple' => $couple, 'booking' => $booking] = $this->scenario([
            'stripe_charges_enabled' => false,
        ]);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('checkoutFor')->never();
        });

        $this->actingAs($couple)
            ->post("/bookings/{$booking->id}/checkout/deposit")
            ->assertRedirect();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_checkout_degrades_when_stripe_is_not_configured(): void
    {
        ['couple' => $couple, 'booking' => $booking] = $this->scenario();
        config(['services.stripe.secret' => null]);

        $this->actingAs($couple)
            ->post("/bookings/{$booking->id}/checkout/deposit")
            ->assertRedirect();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_balance_cannot_be_paid_before_the_deposit(): void
    {
        ['couple' => $couple, 'booking' => $booking] = $this->scenario();

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('amountFor')->andReturn(70000);
            $mock->shouldReceive('checkoutFor')->never();
        });

        // status is pending_payment, so the balance stage is not allowed yet.
        $this->actingAs($couple)
            ->post("/bookings/{$booking->id}/checkout/balance")
            ->assertRedirect();
    }

    // ── Refunds & disputes ────────────────────────────────────────────────────

    public function test_refund_booking_reverses_the_transfer_and_refunds_the_fee(): void
    {
        ['booking' => $booking] = $this->scenario();
        Payment::create([
            'booking_id' => $booking->id,
            'type' => PaymentType::Deposit->value,
            'amount_cents' => 30000,
            'status' => PaymentStatus::Succeeded->value,
            'currency' => 'cad',
            'stripe_payment_intent_id' => 'pi_refund_1',
        ]);

        $refunds = Mockery::mock();
        $refunds->shouldReceive('create')->once()->with(Mockery::on(fn ($a) => $a['payment_intent'] === 'pi_refund_1'
            && $a['reverse_transfer'] === true
            && $a['refund_application_fee'] === true))->andReturn((object) ['id' => 're_1']);

        $client = Mockery::mock(\Stripe\StripeClient::class);
        $client->refunds = $refunds;

        $service = Mockery::mock(StripeService::class)->makePartial();
        $service->shouldReceive('client')->andReturn($client);

        $this->assertSame(30000, $service->refundBooking($booking->fresh()));
    }

    public function test_dispute_created_alerts_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        ['booking' => $booking] = $this->scenario();
        Payment::create([
            'booking_id' => $booking->id,
            'type' => PaymentType::Deposit->value,
            'amount_cents' => 30000,
            'status' => PaymentStatus::Succeeded->value,
            'currency' => 'cad',
            'stripe_payment_intent_id' => 'pi_dispute_1',
        ]);

        $event = \Stripe\Event::constructFrom([
            'type' => 'charge.dispute.created',
            'data' => ['object' => ['payment_intent' => 'pi_dispute_1', 'amount' => 30000, 'charge' => 'ch_1']],
        ]);

        app(StripeService::class)->handleEvent($event);

        Notification::assertSentTo($admin, PaymentDisputeOpened::class);
    }

    public function test_lost_dispute_reverses_the_vendor_transfer_and_cancels_the_booking(): void
    {
        ['booking' => $booking] = $this->scenario();
        $booking->update(['status' => BookingStatus::PaidInFull]);
        Payment::create([
            'booking_id' => $booking->id,
            'type' => PaymentType::Deposit->value,
            'amount_cents' => 30000,
            'status' => PaymentStatus::Succeeded->value,
            'currency' => 'cad',
            'stripe_payment_intent_id' => 'pi_dispute_2',
        ]);

        $charges = Mockery::mock();
        $charges->shouldReceive('retrieve')->with('ch_2')->andReturn((object) ['transfer' => 'tr_2']);
        $transfers = Mockery::mock();
        $transfers->shouldReceive('createReversal')->once()->with('tr_2', Mockery::on(fn ($a) => $a['amount'] === 30000
            && $a['refund_application_fee'] === true))->andReturn((object) ['id' => 'trr_2']);

        $client = Mockery::mock(\Stripe\StripeClient::class);
        $client->charges = $charges;
        $client->transfers = $transfers;

        $service = Mockery::mock(StripeService::class)->makePartial();
        $service->shouldReceive('client')->andReturn($client);

        $event = \Stripe\Event::constructFrom([
            'type' => 'charge.dispute.closed',
            'data' => ['object' => ['status' => 'lost', 'payment_intent' => 'pi_dispute_2', 'amount' => 30000, 'charge' => 'ch_2']],
        ]);

        $service->handleEvent($event);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Vendor;
use App\Models\VendorProfile;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_x',
            'services.stripe.webhook_secret' => $this->secret,
        ]);
    }

    /** Build a booking with a CRM vendor row (so paid_cents can be asserted). */
    protected function booking(array $attrs = []): Booking
    {
        $wedding = Wedding::factory()->create();
        $vendorProfile = VendorProfile::factory()->create();
        $inquiry = Inquiry::factory()->accepted()->create([
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $vendorProfile->id,
        ]);
        $offer = Offer::factory()->accepted()->create([
            'inquiry_id' => $inquiry->id,
            'total_cents' => 100000,
            'deposit_cents' => 30000,
        ]);
        $crm = Vendor::factory()->create(['wedding_id' => $wedding->id, 'paid_cents' => 0]);

        return Booking::factory()->create(array_merge([
            'inquiry_id' => $inquiry->id,
            'offer_id' => $offer->id,
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $vendorProfile->id,
            'vendor_id' => $crm->id,
            'total_cents' => 100000,
            'deposit_cents' => 30000,
            'platform_fee_cents' => 8000,
            'status' => BookingStatus::PendingPayment,
        ], $attrs));
    }

    /** POST a payload to the webhook with a valid (or, if $secret given, custom) signature. */
    protected function sendEvent(array $payload, ?string $signingSecret = null): \Illuminate\Testing\TestResponse
    {
        $json = json_encode($payload);
        $t = time();
        $sig = hash_hmac('sha256', "{$t}.{$json}", $signingSecret ?? $this->secret);

        return $this->call(
            'POST',
            '/stripe/webhook',
            server: ['HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$sig}", 'CONTENT_TYPE' => 'application/json'],
            content: $json,
        );
    }

    protected function checkoutCompleted(string $sessionId): array
    {
        return [
            'id' => 'evt_'.uniqid(),
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'payment_intent' => 'pi_'.uniqid(),
            ]],
        ];
    }

    public function test_deposit_checkout_advances_booking_and_updates_crm_paid(): void
    {
        $booking = $this->booking();
        Payment::create([
            'booking_id' => $booking->id, 'type' => PaymentType::Deposit->value,
            'amount_cents' => 30000, 'application_fee_cents' => 2400,
            'status' => PaymentStatus::Pending->value, 'currency' => 'cad',
            'stripe_session_id' => 'cs_dep_1',
        ]);

        $this->sendEvent($this->checkoutCompleted('cs_dep_1'))->assertOk();

        $this->assertDatabaseHas('payments', ['stripe_session_id' => 'cs_dep_1', 'status' => 'succeeded']);
        $this->assertSame(BookingStatus::DepositPaid, $booking->fresh()->status);
        $this->assertSame(30000, (int) $booking->vendor->fresh()->paid_cents);
    }

    public function test_balance_checkout_marks_paid_in_full(): void
    {
        $booking = $this->booking(['status' => BookingStatus::DepositPaid]);
        // Deposit already succeeded.
        Payment::create([
            'booking_id' => $booking->id, 'type' => PaymentType::Deposit->value,
            'amount_cents' => 30000, 'status' => PaymentStatus::Succeeded->value,
            'currency' => 'cad', 'stripe_session_id' => 'cs_dep_x',
        ]);
        Payment::create([
            'booking_id' => $booking->id, 'type' => PaymentType::Balance->value,
            'amount_cents' => 70000, 'status' => PaymentStatus::Pending->value,
            'currency' => 'cad', 'stripe_session_id' => 'cs_bal_1',
        ]);

        $this->sendEvent($this->checkoutCompleted('cs_bal_1'))->assertOk();

        $this->assertSame(BookingStatus::PaidInFull, $booking->fresh()->status);
        $this->assertSame(100000, (int) $booking->vendor->fresh()->paid_cents);
    }

    public function test_full_refund_cancels_the_booking(): void
    {
        $booking = $this->booking(['status' => BookingStatus::DepositPaid]);
        Payment::create([
            'booking_id' => $booking->id, 'type' => PaymentType::Deposit->value,
            'amount_cents' => 30000, 'status' => PaymentStatus::Succeeded->value,
            'currency' => 'cad', 'stripe_session_id' => 'cs_r', 'stripe_payment_intent_id' => 'pi_refund',
        ]);

        $this->sendEvent([
            'id' => 'evt_r', 'object' => 'event', 'type' => 'charge.refunded',
            'data' => ['object' => [
                'object' => 'charge', 'payment_intent' => 'pi_refund',
                'amount_refunded' => 30000, 'refunded' => true,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('payments', ['type' => 'refund', 'amount_cents' => 30000, 'status' => 'refunded']);
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_account_updated_syncs_vendor_onboarding_state(): void
    {
        $vendor = VendorProfile::factory()->create([
            'stripe_account_id' => 'acct_sync_1',
            'stripe_charges_enabled' => false,
        ]);

        $this->sendEvent([
            'id' => 'evt_a', 'object' => 'event', 'type' => 'account.updated',
            'data' => ['object' => [
                'object' => 'account', 'id' => 'acct_sync_1',
                'charges_enabled' => true, 'details_submitted' => true,
            ]],
        ])->assertOk();

        $vendor->refresh();
        $this->assertTrue($vendor->stripe_charges_enabled);
        $this->assertTrue($vendor->stripe_details_submitted);
    }

    public function test_a_bad_signature_is_rejected(): void
    {
        $this->sendEvent($this->checkoutCompleted('cs_x'), signingSecret: 'whsec_wrong')
            ->assertStatus(400);
    }

    public function test_webhook_is_a_noop_when_not_configured(): void
    {
        config(['services.stripe.secret' => null]);

        $this->sendEvent($this->checkoutCompleted('cs_x'))->assertOk();
    }

    public function test_duplicate_delivery_is_idempotent(): void
    {
        $booking = $this->booking();
        Payment::create([
            'booking_id' => $booking->id, 'type' => PaymentType::Deposit->value,
            'amount_cents' => 30000, 'status' => PaymentStatus::Pending->value,
            'currency' => 'cad', 'stripe_session_id' => 'cs_dupe',
        ]);

        $event = $this->checkoutCompleted('cs_dupe');
        $this->sendEvent($event)->assertOk();
        $this->sendEvent($event)->assertOk();

        $this->assertSame(30000, (int) $booking->fresh()->vendor->paid_cents);
        $this->assertSame(1, Payment::where('stripe_session_id', 'cs_dupe')->count());
    }
}

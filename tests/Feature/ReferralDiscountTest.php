<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Payments\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Event;
use Tests\TestCase;

class ReferralDiscountTest extends TestCase
{
    use RefreshDatabase;

    // ── referralDiscountEligible() — pure ────────────────────────────────────

    public function test_referred_user_with_no_prior_discount_is_eligible(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create(['referred_by' => $referrer->id]);

        $this->assertTrue(app(StripeService::class)->referralDiscountEligible($user));
    }

    public function test_non_referred_user_is_not_eligible(): void
    {
        $user = User::factory()->create(['referred_by' => null]);

        $this->assertFalse(app(StripeService::class)->referralDiscountEligible($user));
    }

    public function test_referred_user_who_already_used_the_discount_is_not_eligible(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referred_by' => $referrer->id,
            'referral_discount_used_at' => now(),
        ]);

        $this->assertFalse(app(StripeService::class)->referralDiscountEligible($user));
    }

    // ── planCheckout() ────────────────────────────────────────────────────────

    protected function mockClientForCheckout(callable $configureCoupons, callable $assertSessionParams): StripeService
    {
        $sessions = Mockery::mock();
        $sessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on($assertSessionParams))
            ->andReturn((object) ['id' => 'cs_test_x', 'url' => 'https://checkout.stripe.test/cs_test_x']);

        $checkout = Mockery::mock();
        $checkout->sessions = $sessions;

        $coupons = Mockery::mock();
        $configureCoupons($coupons);

        $client = Mockery::mock(\Stripe\StripeClient::class);
        $client->checkout = $checkout;
        $client->coupons = $coupons;

        $service = Mockery::mock(StripeService::class)->makePartial();
        $service->shouldReceive('client')->andReturn($client);

        return $service;
    }

    public function test_checkout_applies_the_referral_coupon_when_it_already_exists(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referred_by' => $referrer->id,
            'stripe_customer_id' => 'cus_existing',
        ]);

        $service = $this->mockClientForCheckout(
            configureCoupons: function ($coupons) {
                $coupons->shouldReceive('retrieve')->once()->with('vownook-referral-20off')
                    ->andReturn((object) ['id' => 'vownook-referral-20off']);
                $coupons->shouldReceive('create')->never();
            },
            assertSessionParams: fn ($params) => ($params['discounts'] ?? null) === [['coupon' => 'vownook-referral-20off']],
        );

        $url = $service->planCheckout($user, 'premium', 'https://ok', 'https://no');

        $this->assertSame('https://checkout.stripe.test/cs_test_x', $url);
    }

    public function test_checkout_creates_the_referral_coupon_when_it_does_not_yet_exist(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referred_by' => $referrer->id,
            'stripe_customer_id' => 'cus_existing',
        ]);

        $service = $this->mockClientForCheckout(
            configureCoupons: function ($coupons) {
                $coupons->shouldReceive('retrieve')->once()->with('vownook-referral-20off')
                    ->andThrow(new \Stripe\Exception\InvalidRequestException('No such coupon'));
                $coupons->shouldReceive('create')->once()->with(Mockery::on(
                    fn ($a) => $a['id'] === 'vownook-referral-20off'
                        && $a['amount_off'] === 2000
                        && $a['currency'] === 'cad'
                        && $a['duration'] === 'once'
                ))->andReturn((object) ['id' => 'vownook-referral-20off']);
            },
            assertSessionParams: fn ($params) => ($params['discounts'] ?? null) === [['coupon' => 'vownook-referral-20off']],
        );

        $service->planCheckout($user, 'premium', 'https://ok', 'https://no');
    }

    public function test_checkout_survives_a_concurrent_coupon_creation_race(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referred_by' => $referrer->id,
            'stripe_customer_id' => 'cus_existing',
        ]);

        $service = $this->mockClientForCheckout(
            configureCoupons: function ($coupons) {
                $coupons->shouldReceive('retrieve')->once()->with('vownook-referral-20off')
                    ->andThrow(new \Stripe\Exception\InvalidRequestException('No such coupon'));
                // Simulates a concurrent request having created it first.
                $coupons->shouldReceive('create')->once()
                    ->andThrow(new \Stripe\Exception\InvalidRequestException('Coupon already exists'));
            },
            assertSessionParams: fn ($params) => ($params['discounts'] ?? null) === [['coupon' => 'vownook-referral-20off']],
        );

        $url = $service->planCheckout($user, 'premium', 'https://ok', 'https://no');

        $this->assertSame('https://checkout.stripe.test/cs_test_x', $url);
    }

    public function test_checkout_never_discounts_a_non_referred_user(): void
    {
        $user = User::factory()->create([
            'referred_by' => null,
            'stripe_customer_id' => 'cus_existing',
        ]);

        $service = $this->mockClientForCheckout(
            configureCoupons: function ($coupons) {
                $coupons->shouldReceive('retrieve')->never();
                $coupons->shouldReceive('create')->never();
            },
            assertSessionParams: fn ($params) => ! array_key_exists('discounts', $params),
        );

        $service->planCheckout($user, 'premium', 'https://ok', 'https://no');
    }

    public function test_checkout_never_discounts_a_user_who_already_used_the_discount(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referred_by' => $referrer->id,
            'referral_discount_used_at' => now(),
            'stripe_customer_id' => 'cus_existing',
        ]);

        $service = $this->mockClientForCheckout(
            configureCoupons: function ($coupons) {
                $coupons->shouldReceive('retrieve')->never();
                $coupons->shouldReceive('create')->never();
            },
            assertSessionParams: fn ($params) => ! array_key_exists('discounts', $params),
        );

        $service->planCheckout($user, 'premium', 'https://ok', 'https://no');
    }

    public function test_checkout_never_discounts_the_planner_subscription_tier(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create([
            'referred_by' => $referrer->id,
            'stripe_customer_id' => 'cus_existing',
        ]);

        $service = $this->mockClientForCheckout(
            configureCoupons: function ($coupons) {
                $coupons->shouldReceive('retrieve')->never();
                $coupons->shouldReceive('create')->never();
            },
            assertSessionParams: fn ($params) => ! array_key_exists('discounts', $params),
        );

        $service->planCheckout($user, 'planner', 'https://ok', 'https://no');
    }

    // ── onPlanPurchased() (via handleEvent) ─────────────────────────────────

    protected function completedCheckoutEvent(User $user, string $tier, string $sessionId = 'cs_plan_1'): Event
    {
        return Event::constructFrom([
            'id' => 'evt_'.$sessionId,
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'metadata' => ['user_id' => (string) $user->id, 'plan_tier' => $tier],
            ]],
        ]);
    }

    public function test_webhook_marks_the_discount_used_for_an_eligible_referred_user_buying_premium(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->plan('free')->create(['referred_by' => $referrer->id]);

        app(StripeService::class)->handleEvent($this->completedCheckoutEvent($user, 'premium'));

        $this->assertNotNull($user->fresh()->referral_discount_used_at);
    }

    public function test_webhook_does_not_mark_the_discount_for_a_non_referred_user(): void
    {
        $user = User::factory()->plan('free')->create(['referred_by' => null]);

        app(StripeService::class)->handleEvent($this->completedCheckoutEvent($user, 'premium'));

        $this->assertNull($user->fresh()->referral_discount_used_at);
    }

    public function test_webhook_does_not_mark_the_discount_for_a_planner_purchase(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create(['referred_by' => $referrer->id, 'account_type' => 'planner']);

        app(StripeService::class)->handleEvent($this->completedCheckoutEvent($user, 'planner'));

        $this->assertNull($user->fresh()->referral_discount_used_at);
    }

    public function test_webhook_is_idempotent_when_replayed_for_the_same_user(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->plan('free')->create(['referred_by' => $referrer->id]);
        $service = app(StripeService::class);

        $service->handleEvent($this->completedCheckoutEvent($user, 'premium', 'cs_plan_1'));
        $firstUsedAt = $user->fresh()->referral_discount_used_at;
        $this->assertNotNull($firstUsedAt);

        // A retried/duplicate webhook for the same session must not error and
        // must not change the already-set timestamp — referralDiscountEligible()
        // now returns false, so the marking branch is a no-op.
        $service->handleEvent($this->completedCheckoutEvent($user, 'premium', 'cs_plan_1'));

        $this->assertTrue($firstUsedAt->equalTo($user->fresh()->referral_discount_used_at));
    }
}

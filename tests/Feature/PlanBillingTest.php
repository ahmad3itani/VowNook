<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Payments\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PlanBillingTest extends TestCase
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

    protected function sendEvent(array $payload): TestResponse
    {
        $json = json_encode($payload);
        $t = time();
        $sig = hash_hmac('sha256', "{$t}.{$json}", $this->secret);

        return $this->call(
            'POST',
            '/stripe/webhook',
            server: ['HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$sig}", 'CONTENT_TYPE' => 'application/json'],
            content: $json,
        );
    }

    // ── Checkout ──────────────────────────────────────────────────────────

    public function test_couple_checkout_redirects_to_stripe(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);

        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('planCheckout')->once()->andReturn('https://checkout.stripe.com/c/pay/abc');
        });

        $this->actingAs($user)
            ->post('/settings/plan/checkout', ['tier' => 'premium'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/abc');
    }

    public function test_couple_cannot_buy_the_planner_tier(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);

        $this->actingAs($user)
            ->post('/settings/plan/checkout', ['tier' => 'planner'])
            ->assertForbidden();
    }

    public function test_vendor_cannot_checkout_a_plan(): void
    {
        $user = User::factory()->create(['account_type' => 'vendor']);

        $this->actingAs($user)
            ->post('/settings/plan/checkout', ['tier' => 'premium'])
            ->assertForbidden();
    }

    public function test_checkout_degrades_when_stripe_is_not_configured(): void
    {
        config(['services.stripe.secret' => null]);
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);

        // No error — just bounced back with a status flash.
        $this->actingAs($user)
            ->post('/settings/plan/checkout', ['tier' => 'premium'])
            ->assertRedirect();
    }

    // ── Webhook ───────────────────────────────────────────────────────────

    public function test_webhook_upgrades_plan_after_a_purchase(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);

        $this->sendEvent([
            'id' => 'evt_p', 'object' => 'event', 'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_plan_1',
                'object' => 'checkout.session',
                'metadata' => ['user_id' => (string) $user->id, 'plan_tier' => 'premium'],
            ]],
        ])->assertOk();

        $this->assertSame('premium', $user->fresh()->plan);
    }

    public function test_webhook_reverts_plan_when_subscription_is_cancelled(): void
    {
        $user = User::factory()->plan('planner')->create([
            'account_type' => 'planner',
            'stripe_subscription_id' => 'sub_123',
        ]);

        $this->sendEvent([
            'id' => 'evt_s', 'object' => 'event', 'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_123', 'object' => 'subscription', 'status' => 'canceled']],
        ])->assertOk();

        $this->assertSame('free', $user->fresh()->plan);
        $this->assertNull($user->fresh()->stripe_subscription_id);
    }
}

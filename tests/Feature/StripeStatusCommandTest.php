<?php

namespace Tests\Feature;

use Tests\TestCase;

class StripeStatusCommandTest extends TestCase
{
    public function test_reports_inert_when_no_keys_are_set(): void
    {
        config([
            'services.stripe.secret' => null,
            'services.stripe.key' => null,
            'services.stripe.webhook_secret' => null,
        ]);

        $this->artisan('stripe:status')
            ->expectsOutputToContain('payments are inert')
            ->assertExitCode(0);
    }

    /**
     * The exact production misconfiguration this guard exists for: a TEST
     * publishable key sitting beside a LIVE secret. Silent today (checkout is a
     * server-side redirect, so nothing reads the publishable key) — which is
     * precisely why the diagnostic has to say it out loud.
     */
    public function test_warns_when_publishable_key_mode_does_not_match_the_secret(): void
    {
        config([
            'services.stripe.secret' => 'sk_live_example',
            'services.stripe.key' => 'pk_test_example',
            'services.stripe.webhook_secret' => 'whsec_example',
        ]);

        $this->artisan('stripe:status')
            ->expectsOutputToContain('Key mode mismatch: STRIPE_SECRET is LIVE but STRIPE_KEY is TEST.');
    }

    public function test_does_not_warn_when_both_keys_are_the_same_mode(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_example',
            'services.stripe.key' => 'pk_test_example',
            'services.stripe.webhook_secret' => 'whsec_example',
        ]);

        $this->artisan('stripe:status')
            ->doesntExpectOutputToContain('Key mode mismatch');
    }
}

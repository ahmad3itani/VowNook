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
}

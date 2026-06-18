<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ga_and_consent_load_when_configured(): void
    {
        config(['analytics.ga_id' => 'G-TEST12345', 'analytics.force' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('G-TEST12345')
            ->assertSee('googletagmanager.com/gtag', false)
            ->assertSee("consent', 'default'", false); // Consent Mode v2 default
    }

    public function test_no_tracking_when_unconfigured(): void
    {
        config(['analytics.ga_id' => null, 'analytics.clarity_id' => null, 'analytics.force' => true]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtag', false)
            ->assertDontSee('clarity.ms/tag', false);
    }

    public function test_search_console_tag_renders_when_set(): void
    {
        config(['analytics.google_site_verification' => 'gsc-token-xyz']);

        $this->get('/')
            ->assertOk()
            ->assertSee('google-site-verification', false)
            ->assertSee('gsc-token-xyz');
    }
}

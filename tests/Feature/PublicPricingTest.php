<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The public /pricing page — SEO head, FAQ schema and sitemap presence. */
class PublicPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_pricing_page_renders_with_seo_and_faq_schema(): void
    {
        $response = $this->get('/pricing');

        $response->assertOk();
        $response->assertSee('Pricing — Free Planning, One-Time Atelier Upgrade', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee(route('pricing'), false);
    }

    public function test_the_sitemap_lists_the_pricing_page(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/pricing'), false);
    }

    public function test_the_features_tour_renders_with_seo_and_is_in_the_sitemap(): void
    {
        $response = $this->get('/features');

        $response->assertOk();
        $response->assertSee('Features — Every Wedding Tool, One Calm Studio', false);
        $response->assertSee(route('features'), false);

        $this->get('/sitemap.xml')->assertSee(url('/features'), false);
    }
}

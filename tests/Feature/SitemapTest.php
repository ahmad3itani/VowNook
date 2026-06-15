<?php

namespace Tests\Feature;

use App\Models\VendorProfile;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_published_content_and_excludes_drafts(): void
    {
        $published = VendorProfile::factory()->published()->create();
        $draft = VendorProfile::factory()->draft()->create();

        $publishedSite = WeddingWebsite::factory()->create(['is_published' => true]);
        $draftSite = WeddingWebsite::factory()->create(['is_published' => false]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');

        $xml = $response->getContent();

        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString(url('/'), $xml);
        $this->assertStringContainsString(route('public.marketplace'), $xml);

        $this->assertStringContainsString(route('public.vendor.show', $published->slug), $xml);
        $this->assertStringNotContainsString(route('public.vendor.show', $draft->slug), $xml);

        $this->assertStringContainsString(route('public.website', $publishedSite->wedding->slug), $xml);
        $this->assertStringNotContainsString(route('public.website', $draftSite->wedding->slug), $xml);
    }
}

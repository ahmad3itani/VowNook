<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\VendorMedia;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_article_emits_person_author_and_word_count(): void
    {
        $post = BlogPost::create([
            'title' => 'Ontario Wedding Budgets',
            'slug' => 'ontario-wedding-budgets',
            'excerpt' => 'A practical guide.',
            'body' => 'Some helpful words about budgets and venues and catering choices.',
            'category' => 'budgeting',
            'author_name' => 'Jane Planner',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $html = $this->get(route('blog.show', $post->slug))->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"Person"', $html);
        $this->assertStringContainsString('"name":"Jane Planner"', $html);
        $this->assertStringContainsString('"wordCount"', $html);
        $this->assertStringContainsString('"articleSection":"Budgeting"', $html);
    }

    public function test_sitemap_includes_image_entries_for_vendor_galleries(): void
    {
        $profile = VendorProfile::factory()->create(['status' => 'published']);
        VendorMedia::create([
            'vendor_profile_id' => $profile->id,
            'path' => "vendor-profiles/{$profile->id}/gallery/x.webp",
            'original_name' => 'x.webp',
            'mime' => 'image/webp',
            'size' => 1000,
            'sort_order' => 1,
        ]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"', $xml);
        $this->assertStringContainsString('<image:loc>', $xml);
    }

    public function test_vendor_localbusiness_schema_has_price_range(): void
    {
        $profile = VendorProfile::factory()->create([
            'status' => 'published',
            'base_price_cents' => 250000,
        ]);

        $html = $this->get(route('public.vendor.show', $profile->slug))->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"LocalBusiness"', $html);
        $this->assertStringContainsString('"priceRange"', $html);
    }
}

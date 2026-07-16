<?php

namespace Tests\Feature;

use App\Enums\VendorCategory;
use App\Models\LocalContent;
use App\Models\VendorProfile;
use App\Support\Seo\LocalCosts;
use App\Support\Seo\LocalGuide;
use Database\Seeders\LocalContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SeoRankingTest extends TestCase
{
    use RefreshDatabase;

    /** A genuine, index-worthy guide for a page (mirrors the seeder's output). */
    private function seedGuide(string $category, ?string $citySlug): LocalContent
    {
        return LocalContent::create([
            'category' => $category,
            'city_slug' => $citySlug,
            'intro' => str_repeat('This is a genuinely useful, data-backed local wedding guide with real cost context and honest advice for couples. ', 4),
            'faqs' => [
                ['question' => 'How much does it cost?', 'answer' => 'A typical range applies.'],
                ['question' => 'How far ahead should I book?', 'answer' => 'Around a year ahead.'],
            ],
        ]);
    }

    // --- Cost data -------------------------------------------------------

    public function test_city_index_scales_costs_above_ontario_baseline(): void
    {
        $costs = new LocalCosts;

        $ontario = $costs->for(VendorCategory::Photography, null);
        $toronto = $costs->for(VendorCategory::Photography, 'toronto'); // index 1.20

        $this->assertNotNull($ontario);
        $this->assertNotNull($toronto);
        $this->assertGreaterThan($ontario['low_cents'], $toronto['low_cents']);
        $this->assertStringStartsWith('$', $toronto['display']);
    }

    public function test_catch_all_category_has_no_cost(): void
    {
        $this->assertNull((new LocalCosts)->for(VendorCategory::Other, null));
    }

    public function test_city_page_exposes_cost_prop(): void
    {
        $this->get('/wedding-photographers/toronto')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/local-city')
                ->has('cost', fn (AssertableInertia $c) => $c
                    ->where('unit', 'flat')
                    ->has('display')
                    ->etc()));
    }

    public function test_category_hub_exposes_cost_prop(): void
    {
        $this->get('/wedding-caterers')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/local-category')
                ->where('cost.unit', 'per_guest'));
    }

    // --- Head-term all-categories hub ------------------------------------

    public function test_wedding_vendors_hub_renders_and_is_indexable(): void
    {
        $this->get('/wedding-vendors')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/local-vendors')
                ->where('place.name', 'Ontario')
                ->has('categories', 12)
                ->has('cities'))
            ->assertSee('Wedding Vendors in Ontario', false)
            ->assertDontSee('noindex', false);
    }

    public function test_wedding_vendors_city_hub_renders(): void
    {
        $this->get('/wedding-vendors/toronto')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('public/local-vendors')
                ->where('place.city', 'Toronto'))
            ->assertSee('Toronto', false);
    }

    public function test_wedding_vendors_unknown_city_404(): void
    {
        $this->get('/wedding-vendors/atlantis')->assertNotFound();
    }

    // --- Content-based indexability --------------------------------------

    public function test_guide_makes_a_vendorless_city_page_indexable(): void
    {
        // No vendors, but a real guide → should be indexable, not noindex.
        $this->seedGuide('photography', 'ottawa');

        $this->get('/wedding-photographers/ottawa')
            ->assertOk()
            ->assertDontSee('noindex', false);
    }

    public function test_thin_city_page_without_guide_stays_noindex(): void
    {
        $this->get('/wedding-photographers/hamilton')
            ->assertOk()
            ->assertSee('noindex', false);
    }

    public function test_sitemap_includes_guide_backed_city_pages(): void
    {
        $this->seedGuide('photography', 'ottawa');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/wedding-photographers/ottawa', false)
            ->assertSee('/wedding-vendors', false);
    }

    // --- Deterministic guide generator + seeder --------------------------

    public function test_local_guide_is_unique_and_substantial(): void
    {
        $guide = app(LocalGuide::class);

        $toronto = $guide->city(VendorCategory::Photography, 'toronto');
        $ottawa = $guide->city(VendorCategory::Photography, 'ottawa');

        $this->assertNotNull($toronto);
        $this->assertNotNull($ottawa);
        $this->assertCount(4, $toronto['faqs']);
        $this->assertStringContainsString('Toronto', $toronto['intro']);
        $this->assertStringContainsString('$', $toronto['intro']);
        // City cost index differs, so the copy genuinely differs city to city.
        $this->assertNotSame($toronto['intro'], $ottawa['intro']);
    }

    public function test_seeder_fills_the_tranche_and_hubs(): void
    {
        $this->seed(LocalContentSeeder::class);

        // 12 hubs + 8 metros × 5 categories = 52 pages.
        $this->assertSame(52, LocalContent::count());
        $this->assertTrue(LocalContent::forPage('photography', 'toronto')->isSubstantial());
        $this->assertTrue(LocalContent::forPage('venue', null)->isSubstantial());
    }

    // --- llms.txt (GEO) --------------------------------------------------

    public function test_llms_txt_exposes_cost_facts_and_head_term_hub(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('Typical Ontario wedding costs', false)
            ->assertSee('/wedding-vendors', false)
            ->assertSee('Wedding Photographers:', false);
    }
}

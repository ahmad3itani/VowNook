<?php

namespace Tests\Feature;

use App\Enums\VendorProfileStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalSeoTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPhotographer(string $city): VendorProfile
    {
        $user = User::factory()->create(['account_type' => 'vendor']);

        return VendorProfile::create([
            'user_id' => $user->id,
            'business_name' => "Studio {$city} ".fake()->unique()->lastName(),
            'category' => 'photography',
            'city' => $city,
            'region' => 'Ontario',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ]);
    }

    public function test_category_hub_renders(): void
    {
        $this->get('/wedding-photographers')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('public/local-category'))
            ->assertSee('Wedding Photographers in Ontario', false);
    }

    public function test_city_category_page_renders(): void
    {
        $this->get('/wedding-photographers/toronto')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('public/local-city'))
            ->assertSee('Wedding Photographers in Toronto, Ontario', false);
    }

    public function test_unknown_category_and_city_404(): void
    {
        $this->get('/wedding-spaceships')->assertNotFound();
        $this->get('/wedding-photographers/atlantis')->assertNotFound();
    }

    public function test_quality_gate_noindexes_thin_city_pages(): void
    {
        // Two vendors — below the threshold of three → noindex.
        $this->publishedPhotographer('Toronto');
        $this->publishedPhotographer('Toronto');

        $this->get('/wedding-photographers/toronto')->assertSee('noindex', false);
    }

    public function test_city_page_is_indexable_once_inventory_clears_the_gate(): void
    {
        foreach (range(1, 3) as $i) {
            $this->publishedPhotographer('Toronto');
        }

        $this->get('/wedding-photographers/toronto')->assertDontSee('noindex', false);
    }

    public function test_robots_txt_lists_sitemap_and_blocks_private_areas(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap:', false)
            ->assertSee('Disallow: /dashboard', false)
            ->assertSee('GPTBot', false);
    }

    public function test_sitemap_includes_category_hubs(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/wedding-photographers', false)
            ->assertSee('/wedding-venues', false);
    }

    public function test_llms_txt_renders(): void
    {
        $this->get('/llms.txt')->assertOk()->assertSee('wedding', false);
    }
}

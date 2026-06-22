<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use App\Support\Affiliates\TravelAffiliates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TravelAffiliateTest extends TestCase
{
    use RefreshDatabase;

    private function publishedSiteWithVenue(bool $showStays = true): Wedding
    {
        $wedding = Wedding::factory()->create(['event_date' => '2026-09-12']);
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id,
            'is_published' => true,
            'venue_name' => 'The Walper Hotel',
            'venue_address' => '20 Queen St S, Kitchener',
            'show_travel_stays' => $showStays,
        ]);

        return $wedding;
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    public function test_helper_builds_a_stay22_url_with_the_account_id_and_dates(): void
    {
        config(['affiliates.stay22.id' => 'aff-123', 'affiliates.stay22.maincolor' => '8a651c']);

        $url = (new TravelAffiliates)->stay22EmbedUrl('The Walper Hotel', '20 Queen St S, Kitchener', Carbon::parse('2026-09-12'));

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://www.stay22.com/embed/gm?aid=aff-123', $url);
        $this->assertStringContainsString('checkin=2026-09-12', $url);
        $this->assertStringContainsString('checkout=2026-09-13', $url);
        $this->assertStringContainsString('maincolor=8a651c', $url);
        $this->assertStringContainsString('venue=', $url);
    }

    public function test_helper_returns_null_when_not_configured_or_no_location(): void
    {
        config(['affiliates.stay22.id' => null]);
        $this->assertNull((new TravelAffiliates)->stay22EmbedUrl('Venue', 'Address'));

        config(['affiliates.stay22.id' => 'aff-123']);
        $this->assertNull((new TravelAffiliates)->stay22EmbedUrl(null, null));
    }

    // ── Public site ──────────────────────────────────────────────────────────

    public function test_public_site_exposes_the_affiliate_map_when_configured(): void
    {
        config(['affiliates.stay22.id' => 'aff-123']);
        $wedding = $this->publishedSiteWithVenue();

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/website')
                ->where('travel.affiliate_partner', 'Stay22')
                ->whereType('travel.affiliate_url', 'string')
            );
    }

    public function test_public_site_hides_the_map_when_not_configured(): void
    {
        config(['affiliates.stay22.id' => null]);
        $wedding = $this->publishedSiteWithVenue();

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('travel.affiliate_url', null));
    }

    public function test_couple_toggle_hides_the_map(): void
    {
        config(['affiliates.stay22.id' => 'aff-123']);
        $wedding = $this->publishedSiteWithVenue(showStays: false);

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('travel.affiliate_url', null));
    }

    public function test_map_is_hidden_without_a_venue(): void
    {
        config(['affiliates.stay22.id' => 'aff-123']);
        $wedding = Wedding::factory()->create();
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id,
            'is_published' => true,
            'venue_name' => null,
            'venue_address' => null,
            'show_travel_stays' => true,
        ]);

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('travel.affiliate_url', null));
    }

    // ── Couple editor ────────────────────────────────────────────────────────

    public function test_couple_can_toggle_the_stays_map(): void
    {
        $user = User::factory()->plan('premium')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)
            ->put('/travel/stays-visibility', ['show_travel_stays' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('wedding_websites', [
            'wedding_id' => $wedding->id, 'show_travel_stays' => false,
        ]);
    }

    public function test_travel_page_exposes_affiliate_props(): void
    {
        config(['affiliates.stay22.id' => 'aff-123']);
        $user = User::factory()->plan('premium')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)
            ->get('/travel')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('travel/index')
                ->where('affiliate_enabled', true)
                ->where('affiliate_partner', 'Stay22')
            );
    }
}

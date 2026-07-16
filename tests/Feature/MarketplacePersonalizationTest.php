<?php

namespace Tests\Feature;

use App\Enums\VendorProfileStatus;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use App\Support\MarketplaceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On-site personalization: a soft "Fits your budget" + "Near you" signal on
 * the marketplace browse grid, driven entirely by the couple's own captured
 * budget + city (no AI, no cost). Every assertion here also pins the
 * backward-compat contract for the PUBLIC marketplace, which never passes
 * personalization context and must see byte-for-byte unchanged output.
 */
class MarketplacePersonalizationTest extends TestCase
{
    use RefreshDatabase;

    private MarketplaceCatalog $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog = app(MarketplaceCatalog::class);
    }

    protected function vendor(array $attributes = []): VendorProfile
    {
        $user = User::factory()->create(['account_type' => 'vendor']);

        return VendorProfile::create(array_merge([
            'user_id' => $user->id,
            'business_name' => 'Test Vendor',
            'category' => 'florist',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ], $attributes));
    }

    public function test_card_data_without_context_omits_personalization_keys_entirely(): void
    {
        $vendor = $this->vendor();

        $data = $this->catalog->cardData($vendor);

        $this->assertArrayNotHasKey('fits_budget', $data);
        $this->assertArrayNotHasKey('near_you', $data);
    }

    public function test_card_data_with_category_budgets_flags_vendors_within_and_over_cap(): void
    {
        $withinCap = $this->vendor(['category' => 'florist', 'base_price_cents' => 100_000]);
        $overCap = $this->vendor(['category' => 'florist', 'base_price_cents' => 500_000]);
        $noCapCategory = $this->vendor(['category' => 'planner', 'base_price_cents' => 100_000]);

        $context = ['category_budgets' => ['florist' => 200_000]];

        $this->assertTrue($this->catalog->cardData($withinCap, $context)['fits_budget']);
        $this->assertFalse($this->catalog->cardData($overCap, $context)['fits_budget']);
        $this->assertArrayNotHasKey('fits_budget', $this->catalog->cardData($noCapCategory, $context));
    }

    public function test_card_data_with_city_name_flags_matching_and_non_matching_vendors(): void
    {
        $matching = $this->vendor(['city' => 'Toronto']);
        $nonMatching = $this->vendor(['city' => 'London']);

        $context = ['city_name' => 'Toronto'];

        $this->assertTrue($this->catalog->cardData($matching, $context)['near_you']);
        $this->assertFalse($this->catalog->cardData($nonMatching, $context)['near_you']);
    }

    public function test_browse_without_city_param_ordering_is_unchanged(): void
    {
        // Same fixture as VendorSinkToBottomTest::test_fully_booked_vendors_sink_below_accepting_ones —
        // proving the new optional param truly no-ops when omitted.
        $this->vendor(['business_name' => 'Ace Blooms', 'is_accepting_bookings' => false]);
        $this->vendor(['business_name' => 'Zephyr Florals', 'is_accepting_bookings' => true]);

        $results = $this->catalog->browse([]);

        $this->assertSame(['Zephyr Florals', 'Ace Blooms'], $results->pluck('business_name')->all());
    }

    public function test_browse_with_city_param_breaks_ties_in_favor_of_the_matching_city(): void
    {
        $this->vendor(['business_name' => 'Toronto Blooms', 'city' => 'Toronto']);
        $this->vendor(['business_name' => 'London Blooms', 'city' => 'London']);

        $results = $this->catalog->browse([], 'Toronto');

        $this->assertSame(['Toronto Blooms', 'London Blooms'], $results->pluck('business_name')->all());
    }

    public function test_founding_vendor_in_another_city_still_beats_matching_city_non_founding_vendor(): void
    {
        $this->vendor(['business_name' => 'Founding Elsewhere', 'city' => 'London', 'is_founding' => true]);
        $this->vendor(['business_name' => 'Local Non-Founding', 'city' => 'Toronto', 'is_founding' => false]);

        $results = $this->catalog->browse([], 'Toronto');

        // Founding promotion tier is never overridden by the "near you" tie-breaker.
        $this->assertSame(['Founding Elsewhere', 'Local Non-Founding'], $results->pluck('business_name')->all());
    }

    public function test_authenticated_couple_marketplace_carries_personalization_keys(): void
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create([
            'owner_id' => $user->id,
            'total_budget_cents' => 3_000_000,
            'city' => 'toronto',
        ]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->vendor(['business_name' => 'Toronto Blooms', 'category' => 'florist', 'city' => 'Toronto', 'base_price_cents' => 50_000]);

        $this->actingAs($user)
            ->get('/vendors/marketplace')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendors/marketplace')
                ->where('profiles.0.fits_budget', true)
                ->where('profiles.0.near_you', true)
            );
    }

    public function test_public_marketplace_never_carries_personalization_keys(): void
    {
        $this->vendor(['business_name' => 'Toronto Blooms', 'category' => 'florist', 'city' => 'Toronto', 'base_price_cents' => 50_000]);

        $this->get('/marketplace')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/marketplace')
                ->where('total', 1)
                ->missing('profiles.0.fits_budget')
                ->missing('profiles.0.near_you')
            );
    }
}

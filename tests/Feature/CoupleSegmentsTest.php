<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use App\Support\Audience\CoupleSegments;
use App\Support\Budget\BudgetAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CoupleSegments is the cheap, computed-only audience layer — no AI, no new
 * data capture. Every bucket boundary and the exact return-array contract are
 * pinned here so the admin vendor-gap view and any future personalization
 * built on top of it can trust the shape.
 */
class CoupleSegmentsTest extends TestCase
{
    use RefreshDatabase;

    private CoupleSegments $segments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->segments = new CoupleSegments();
    }

    private function wedding(array $attributes = []): Wedding
    {
        $owner = $attributes['owner'] ?? User::factory()->create();
        unset($attributes['owner']);

        return Wedding::factory()->create(array_merge(['owner_id' => $owner->id], $attributes));
    }

    public function test_return_contract_has_exactly_the_documented_keys(): void
    {
        $wedding = $this->wedding();

        $result = $this->segments->for($wedding);

        $this->assertEquals(
            [
                'budget_tier', 'budget_tier_label', 'city', 'city_name', 'timeline_urgency', 'timeline_label',
                'guest_scale', 'guest_scale_label', 'plan_tier', 'vibe', 'vibe_label', 'season', 'season_label',
                'interested_categories', 'referred',
            ],
            array_keys($result),
        );
    }

    public function test_vibe_and_season_reflect_settings_when_set(): void
    {
        $wedding = $this->wedding(['settings' => ['vibe' => 'boho-romantic', 'season' => 'fall']]);

        $result = $this->segments->for($wedding);

        $this->assertSame('boho-romantic', $result['vibe']);
        $this->assertSame('Boho & romantic', $result['vibe_label']);
        $this->assertSame('fall', $result['season']);
        $this->assertSame('Fall', $result['season_label']);
    }

    public function test_vibe_and_season_are_null_when_unset(): void
    {
        $wedding = $this->wedding(['settings' => []]);

        $result = $this->segments->for($wedding);

        $this->assertNull($result['vibe']);
        $this->assertNull($result['vibe_label']);
        $this->assertNull($result['season']);
        $this->assertNull($result['season_label']);
    }

    public function test_vibe_and_season_degrade_to_null_on_stale_settings_values(): void
    {
        $wedding = $this->wedding(['settings' => ['vibe' => 'retired-old-vibe', 'season' => 'monsoon']]);

        $result = $this->segments->for($wedding);

        $this->assertNull($result['vibe']);
        $this->assertNull($result['vibe_label']);
        $this->assertNull($result['season']);
        $this->assertNull($result['season_label']);
    }

    public function test_budget_tier_unset_when_null_or_zero(): void
    {
        $this->assertSame('unset', $this->segments->for($this->wedding(['total_budget_cents' => null]))['budget_tier']);
        $this->assertSame('unset', $this->segments->for($this->wedding(['total_budget_cents' => 0]))['budget_tier']);
    }

    public function test_budget_tier_buckets_match_budget_allocator_bands(): void
    {
        $bandKeys = collect(BudgetAllocator::bands())->pluck('key')->all();

        $cases = [
            1_000_000 => 'under-15k',
            2_000_000 => '15-25k',
            3_000_000 => '25-40k',
            5_000_000 => '40-60k',
            8_000_000 => '60k-plus',
        ];

        foreach ($cases as $cents => $expectedTier) {
            $result = $this->segments->for($this->wedding(['total_budget_cents' => $cents]));
            $this->assertSame($expectedTier, $result['budget_tier']);
            $this->assertContains($expectedTier, $bandKeys);
            $this->assertNotSame('Not set yet', $result['budget_tier_label']);
        }
    }

    public function test_timeline_urgency_no_date(): void
    {
        $this->assertSame('no-date', $this->segments->for($this->wedding(['event_date' => null]))['timeline_urgency']);
    }

    public function test_timeline_urgency_past(): void
    {
        $wedding = $this->wedding(['event_date' => now()->subMonths(2)->format('Y-m-d')]);
        $this->assertSame('past', $this->segments->for($wedding)['timeline_urgency']);
    }

    public function test_timeline_urgency_urgent(): void
    {
        $wedding = $this->wedding(['event_date' => now()->addMonths(1)->format('Y-m-d')]);
        $this->assertSame('urgent', $this->segments->for($wedding)['timeline_urgency']);
    }

    public function test_timeline_urgency_this_year(): void
    {
        $wedding = $this->wedding(['event_date' => now()->addMonths(6)->format('Y-m-d')]);
        $this->assertSame('this-year', $this->segments->for($wedding)['timeline_urgency']);
    }

    public function test_timeline_urgency_planning_ahead(): void
    {
        $wedding = $this->wedding(['event_date' => now()->addMonths(18)->format('Y-m-d')]);
        $this->assertSame('planning-ahead', $this->segments->for($wedding)['timeline_urgency']);
    }

    public function test_timeline_urgency_far_out(): void
    {
        $wedding = $this->wedding(['event_date' => now()->addMonths(30)->format('Y-m-d')]);
        $this->assertSame('far-out', $this->segments->for($wedding)['timeline_urgency']);
    }

    public function test_guest_scale_unset_with_no_guests(): void
    {
        $wedding = $this->wedding();
        $this->assertSame('unset', $this->segments->for($wedding)['guest_scale']);
    }

    public function test_guest_scale_intimate(): void
    {
        $wedding = $this->wedding();
        Guest::factory()->count(10)->create(['wedding_id' => $wedding->id]);
        $this->assertSame('intimate', $this->segments->for($wedding)['guest_scale']);
    }

    public function test_guest_scale_medium(): void
    {
        $wedding = $this->wedding();
        Guest::factory()->count(50)->create(['wedding_id' => $wedding->id]);
        $this->assertSame('medium', $this->segments->for($wedding)['guest_scale']);
    }

    public function test_guest_scale_large(): void
    {
        $wedding = $this->wedding();
        Guest::factory()->count(150)->create(['wedding_id' => $wedding->id]);
        $this->assertSame('large', $this->segments->for($wedding)['guest_scale']);
    }

    public function test_guest_scale_grand(): void
    {
        $wedding = $this->wedding();
        Guest::factory()->count(220)->create(['wedding_id' => $wedding->id]);
        $this->assertSame('grand', $this->segments->for($wedding)['guest_scale']);
    }

    public function test_plan_tier_reflects_owner_plan(): void
    {
        $freeOwner = User::factory()->plan('free')->create();
        $premiumOwner = User::factory()->plan('premium')->create();

        $this->assertSame('free', $this->segments->for($this->wedding(['owner' => $freeOwner]))['plan_tier']);
        $this->assertSame('premium', $this->segments->for($this->wedding(['owner' => $premiumOwner]))['plan_tier']);
    }

    public function test_interested_categories_empty_with_no_inquiries(): void
    {
        $wedding = $this->wedding();
        $this->assertSame([], $this->segments->for($wedding)['interested_categories']);
    }

    public function test_interested_categories_aggregates_and_counts_across_inquiries(): void
    {
        $wedding = $this->wedding();

        $photographer1 = VendorProfile::factory()->create(['category' => 'photography']);
        $photographer2 = VendorProfile::factory()->create(['category' => 'photography']);
        $florist = VendorProfile::factory()->create(['category' => 'florist']);

        Inquiry::factory()->create(['wedding_id' => $wedding->id, 'couple_user_id' => $wedding->owner_id, 'vendor_profile_id' => $photographer1->id]);
        Inquiry::factory()->create(['wedding_id' => $wedding->id, 'couple_user_id' => $wedding->owner_id, 'vendor_profile_id' => $photographer2->id]);
        Inquiry::factory()->create(['wedding_id' => $wedding->id, 'couple_user_id' => $wedding->owner_id, 'vendor_profile_id' => $florist->id]);

        $result = $this->segments->for($wedding)['interested_categories'];

        $this->assertSame(
            [
                ['category' => 'photography', 'label' => 'Photography', 'count' => 2],
                ['category' => 'florist', 'label' => 'Florist', 'count' => 1],
            ],
            $result,
        );
    }

    public function test_referred_reflects_owner_referred_by(): void
    {
        $referrer = User::factory()->create();
        $referredOwner = User::factory()->create(['referred_by' => $referrer->id]);
        $organicOwner = User::factory()->create(['referred_by' => null]);

        $this->assertTrue($this->segments->for($this->wedding(['owner' => $referredOwner]))['referred']);
        $this->assertFalse($this->segments->for($this->wedding(['owner' => $organicOwner]))['referred']);
    }

    public function test_city_and_city_name_reflect_the_wedding_city(): void
    {
        $wedding = $this->wedding(['city' => 'toronto']);
        $result = $this->segments->for($wedding);

        $this->assertSame('toronto', $result['city']);
        $this->assertSame('Toronto', $result['city_name']);
    }

    public function test_city_name_is_null_without_a_city(): void
    {
        $wedding = $this->wedding(['city' => null]);
        $result = $this->segments->for($wedding);

        $this->assertNull($result['city']);
        $this->assertNull($result['city_name']);
    }
}

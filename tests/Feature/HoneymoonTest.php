<?php

namespace Tests\Feature;

use App\Models\HoneymoonPlan;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HoneymoonTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    private function premiumCouple(): array
    {
        $user = User::factory()->plan('premium')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id, 'event_date' => '2026-09-12']);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    private function enableAi(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'anthropic',
            'ai.anthropic.key' => 'test-key',
            'ai.anthropic.base_url' => 'https://api.anthropic.com',
            'ai.anthropic.version' => '2023-06-01',
            'ai.openrouter.key' => null,
            'ai.model' => 'claude-sonnet-4-6',
        ]);
    }

    private function fakePackages(): void
    {
        // Lean packages — the craft call no longer returns a day-by-day (that is
        // generated for the chosen package only, in choose()).
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'propose_honeymoons', 'input' => [
                'packages' => [
                    ['tier' => 'essential', 'destination' => 'Riviera Maya', 'airport' => 'CUN', 'why' => 'Most beach for your budget.', 'hotel_name' => 'Resort A', 'flight_estimate_dollars' => 640, 'hotel_estimate_dollars' => 3120, 'activities_estimate_dollars' => 800, 'food_estimate_dollars' => 900],
                    ['tier' => 'signature', 'destination' => 'Maui, Hawaii', 'airport' => 'ogg', 'why' => 'Your vibe exactly.', 'hotel_name' => 'Ocean Suite', 'flight_estimate_dollars' => 980, 'hotel_estimate_dollars' => 4560, 'activities_estimate_dollars' => 1200, 'food_estimate_dollars' => 1100, 'experiences' => [['name' => 'Sunset catamaran cruise', 'blurb' => 'Champagne at sea.', 'est_dollars' => 180]]],
                    ['tier' => 'dream', 'destination' => 'Bora Bora', 'airport' => 'BOB', 'why' => 'The splurge.', 'hotel_name' => 'Overwater Villa', 'flight_estimate_dollars' => 1840, 'hotel_estimate_dollars' => 7900, 'activities_estimate_dollars' => 2000, 'food_estimate_dollars' => 1500],
                ],
            ]]],
            'stop_reason' => 'tool_use',
        ])]);
    }

    private function fakeItinerary(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'honeymoon_itinerary', 'input' => [
                'days' => [
                    ['title' => 'Arrival & sunset dinner', 'plan' => 'Settle in and watch the sunset.', 'spend_dollars' => 120],
                    ['title' => 'Road to Hana', 'plan' => 'Scenic drive with waterfalls.', 'spend_dollars' => 190],
                ],
            ]]],
            'stop_reason' => 'tool_use',
        ])]);
    }

    public function test_free_couple_is_redirected(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/honeymoon')->assertRedirect(route('plan.edit'));
    }

    public function test_premium_couple_can_view(): void
    {
        [$user] = $this->premiumCouple();

        $this->actingAs($user)->get('/honeymoon')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('honeymoon/index'));
    }

    public function test_generate_crafts_three_tiered_packages(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        $this->enableAi();
        $this->fakePackages();

        $this->actingAs($user)->post('/honeymoon/generate', [
            'vibe' => 'beach and food', 'budget' => 9000, 'departure' => 'Toronto',
            'start_date' => '2026-10-01', 'end_date' => '2026-10-09',
        ])->assertRedirect();

        $plan = HoneymoonPlan::forWedding($wedding->id)->first();
        $this->assertNotNull($plan);
        $this->assertCount(3, $plan->packages);
        $this->assertSame('essential', $plan->packages[0]['tier']);
        $this->assertSame('OGG', $plan->packages[1]['airport']); // normalised to uppercase
        $this->assertSame((640 + 3120 + 800 + 900) * 100, $plan->packages[0]['total_cents']);
        $this->assertSame('Sunset catamaran cruise', $plan->packages[1]['experiences'][0]['name']);
        $this->assertSame(18000, $plan->packages[1]['experiences'][0]['est_cents']);
        // Lean craft: nights computed from the dates (Oct 1 → Oct 9), no day-by-day yet.
        $this->assertSame(8, $plan->packages[0]['nights']);
        $this->assertSame([], $plan->packages[1]['days']);
    }

    public function test_choose_generates_a_day_by_day_with_ai(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        $this->enableAi();
        $this->fakeItinerary();

        HoneymoonPlan::create([
            'wedding_id' => $wedding->id,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-09',
            'preferences' => ['interests' => 'snorkelling'],
            'packages' => [[
                'tier' => 'signature', 'destination' => 'Maui', 'airport' => 'OGG', 'why' => 'Yes',
                'hotel_name' => 'Suite', 'flight_cents' => 98000, 'hotel_cents' => 456000,
                'activities_cents' => 0, 'food_cents' => 0, 'total_cents' => 554000,
                'nights' => 8, 'days' => [],
            ]],
        ]);

        $this->actingAs($user)->put('/honeymoon/choose', ['tier' => 'signature'])->assertRedirect();

        $plan = HoneymoonPlan::forWedding($wedding->id)->first();
        $this->assertCount(2, $plan->packages[0]['days']);
        $this->assertSame('Arrival & sunset dinner', $plan->packages[0]['days'][0]['title']);
        $this->assertSame(12000, $plan->packages[0]['days'][0]['spend_cents']);
    }

    public function test_add_to_registry_creates_funds_from_the_chosen_package(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        HoneymoonPlan::create([
            'wedding_id' => $wedding->id,
            'chosen_tier' => 'signature',
            'packages' => [[
                'tier' => 'signature', 'destination' => 'Maui', 'airport' => 'OGG', 'origin_airport' => 'YYZ',
                'why' => 'Yes', 'hotel_name' => 'Ocean Suite', 'flight_cents' => 98000, 'hotel_cents' => 456000,
                'activities_cents' => 0, 'food_cents' => 0, 'total_cents' => 554000, 'days' => [],
                'experiences' => [['name' => 'Catamaran cruise', 'blurb' => 'Sunset.', 'est_cents' => 18000]],
            ]],
        ]);

        $this->actingAs($user)->post('/honeymoon/registry')->assertRedirect();

        $this->assertDatabaseHas('registry_funds', ['wedding_id' => $wedding->id, 'title' => 'Flights to Maui', 'type' => 'honeymoon', 'goal_cents' => 98000]);
        $this->assertDatabaseHas('registry_funds', ['wedding_id' => $wedding->id, 'title' => 'Ocean Suite', 'goal_cents' => 456000]);
        $this->assertDatabaseHas('registry_funds', ['wedding_id' => $wedding->id, 'title' => 'Catamaran cruise', 'goal_cents' => 18000]);
        $this->assertDatabaseHas('honeymoon_plans', ['wedding_id' => $wedding->id, 'registry_added' => true]);

        // Idempotent — a second add does not duplicate.
        $this->actingAs($user)->post('/honeymoon/registry')->assertRedirect();
        $this->assertDatabaseCount('registry_funds', 3);
    }

    public function test_generate_degrades_when_not_configured(): void
    {
        [$user] = $this->premiumCouple();
        config(['ai.enabled' => true, 'ai.anthropic.key' => null, 'ai.openrouter.key' => null]);
        Http::fake();

        $this->actingAs($user)->post('/honeymoon/generate', ['vibe' => 'x'])
            ->assertRedirect()
            ->assertSessionHasErrors('ai');

        Http::assertNothingSent();
    }

    public function test_choose_locks_a_tier_and_enables_booking(): void
    {
        config(['affiliates.stay22.id' => 'aff-123', 'affiliates.travelpayouts.marker' => 'mk-99']);
        [$user, $wedding] = $this->premiumCouple();

        HoneymoonPlan::create([
            'wedding_id' => $wedding->id,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-09',
            'packages' => [[
                'tier' => 'signature', 'destination' => 'Maui', 'airport' => 'OGG', 'why' => 'Yes',
                'hotel_name' => 'Suite', 'flight_cents' => 98000, 'hotel_cents' => 456000,
                'activities_cents' => 120000, 'food_cents' => 110000, 'total_cents' => 784000, 'days' => [],
                'experiences' => [['name' => 'Lava tour', 'blurb' => 'Wow.', 'est_cents' => 22000]],
            ]],
        ]);

        $this->actingAs($user)->put('/honeymoon/choose', ['tier' => 'signature'])->assertRedirect();

        $this->assertDatabaseHas('honeymoon_plans', [
            'wedding_id' => $wedding->id, 'chosen_tier' => 'signature', 'destination' => 'Maui', 'airport' => 'OGG',
        ]);

        $this->actingAs($user)->get('/honeymoon')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('chosen_tier', 'signature')
                ->whereType('stays_url', 'string')
                ->whereType('flights_url', 'string')
                ->where('experiences.0.name', 'Lava tour')
                ->where('experiences.0.url', fn ($u) => str_contains((string) $u, 'getyourguide.com'))
            );
    }

    public function test_chosen_package_shows_live_prices_when_configured(): void
    {
        config([
            'affiliates.stay22.id' => 'aff-123',
            'affiliates.travelpayouts.marker' => 'mk-99',
            'affiliates.travelpayouts.api_token' => 'tok',
        ]);
        Http::fake([
            'api.travelpayouts.com/v1/prices/cheap*' => Http::response(['data' => ['OGG' => ['0' => ['price' => 910]]]]),
            'engine.hotellook.com/*' => Http::response([['priceFrom' => 4200]]),
        ]);

        [$user, $wedding] = $this->premiumCouple();
        HoneymoonPlan::create([
            'wedding_id' => $wedding->id,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-09',
            'chosen_tier' => 'signature',
            'packages' => [[
                'tier' => 'signature', 'destination' => 'Maui', 'airport' => 'OGG', 'origin_airport' => 'YYZ',
                'why' => 'Yes', 'hotel_name' => 'Suite', 'flight_cents' => 98000, 'hotel_cents' => 456000,
                'activities_cents' => 0, 'food_cents' => 0, 'total_cents' => 554000, 'days' => [],
            ]],
        ]);

        $this->actingAs($user)->get('/honeymoon')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('live.flight.found', true)
                ->where('live.flight.price_cents', 91000)
                ->where('live.hotel.found', true)
                ->where('live.hotel.price_cents', 420000)
            );
    }

    public function test_start_over_clears_packages(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        HoneymoonPlan::create([
            'wedding_id' => $wedding->id,
            'packages' => [['tier' => 'signature', 'destination' => 'X']],
            'chosen_tier' => 'signature',
        ]);

        $this->actingAs($user)->delete('/honeymoon')->assertRedirect();

        $plan = HoneymoonPlan::forWedding($wedding->id)->first();
        $this->assertNull($plan->packages);
        $this->assertNull($plan->chosen_tier);
    }

    public function test_travel_page_exposes_preview_urls(): void
    {
        config(['affiliates.stay22.id' => 'aff-123', 'affiliates.travelpayouts.marker' => 'mk-99']);
        [$user, $wedding] = $this->premiumCouple();
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id,
            'venue_name' => 'The Walper',
            'venue_address' => 'Kitchener',
            'nearest_airport' => 'YYZ',
        ]);

        $this->actingAs($user)->get('/travel')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('travel/index')
                ->whereType('stays_preview_url', 'string')
                ->whereType('flights_preview_url', 'string')
            );
    }
}

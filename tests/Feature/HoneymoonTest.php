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
    private function premiumCouple(string $eventDate = '2026-09-12'): array
    {
        $user = User::factory()->plan('premium')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id, 'event_date' => $eventDate]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_free_couple_is_redirected(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/honeymoon')->assertRedirect(route('plan.edit'));
    }

    public function test_premium_couple_can_view_and_save(): void
    {
        [$user, $wedding] = $this->premiumCouple();

        $this->actingAs($user)->get('/honeymoon')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('honeymoon/index'));

        $this->actingAs($user)->put('/honeymoon', [
            'destination' => 'Maui, Hawaii',
            'airport' => 'OGG',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'notes' => 'Snorkeling!',
            'budget_items' => [
                ['label' => 'Flights', 'amount_cents' => 240000],
                ['label' => 'Resort', 'amount_cents' => 500000],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('honeymoon_plans', [
            'wedding_id' => $wedding->id, 'destination' => 'Maui, Hawaii', 'airport' => 'OGG',
        ]);

        $plan = HoneymoonPlan::forWedding($wedding->id)->first();
        $this->assertCount(2, $plan->budget_items);
    }

    public function test_end_date_must_not_precede_start(): void
    {
        [$user] = $this->premiumCouple();

        $this->actingAs($user)->put('/honeymoon', [
            'start_date' => '2026-10-10', 'end_date' => '2026-10-01',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_affiliate_urls_appear_when_configured(): void
    {
        config(['affiliates.stay22.id' => 'aff-123', 'affiliates.travelpayouts.marker' => 'mk-99']);
        [$user, $wedding] = $this->premiumCouple();
        HoneymoonPlan::create([
            'wedding_id' => $wedding->id, 'destination' => 'Maui', 'airport' => 'OGG',
            'start_date' => '2026-10-01', 'end_date' => '2026-10-10',
        ]);

        $this->actingAs($user)->get('/honeymoon')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->whereType('stays_url', 'string')
                ->whereType('flights_url', 'string')
            );
    }

    // ── Plan with AI ───────────────────────────────────────────────────────────

    protected function enableAi(): void
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

    public function test_ai_plan_drafts_a_honeymoon(): void
    {
        [$user] = $this->premiumCouple();
        $this->enableAi();
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'propose_honeymoon', 'input' => [
                'destination' => 'Maui, Hawaii',
                'airport' => 'ogg',
                'highlights' => 'A relaxing beach escape with great food.',
                'budget_items' => [
                    ['label' => 'Flights', 'amount_dollars' => 2400],
                    ['label' => 'Resort', 'amount_dollars' => 5000],
                ],
            ]]],
            'stop_reason' => 'tool_use',
        ])]);

        $this->actingAs($user)
            ->postJson('/honeymoon/ai', ['preferences' => 'beach, warm, around $9000'])
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('destination', 'Maui, Hawaii')
            ->assertJsonPath('airport', 'OGG')
            ->assertJsonPath('budget_items.0.amount_cents', 240000);
    }

    public function test_ai_plan_redirects_a_free_couple(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();
        $this->enableAi();
        Http::fake();

        // The Atelier-gated route blocks a free couple before any AI call.
        $this->actingAs($user)
            ->post('/honeymoon/ai', ['preferences' => 'x'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_ai_plan_degrades_when_not_configured(): void
    {
        [$user] = $this->premiumCouple();
        config(['ai.enabled' => true, 'ai.anthropic.key' => null, 'ai.openrouter.key' => null]);
        Http::fake();

        $this->actingAs($user)
            ->postJson('/honeymoon/ai', ['preferences' => 'x'])
            ->assertOk()
            ->assertJsonPath('available', false);

        Http::assertNothingSent();
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

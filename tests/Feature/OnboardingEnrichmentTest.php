<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use App\Support\Budget\BudgetAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shared onboarding-enrichment screen — one lightweight, skippable capture
 * (city, budget band, vibe, season) shown once to a couple right after their
 * first wedding is created. Every field is optional; the screen must never
 * be shown again once completed or skipped.
 */
class OnboardingEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected function planner(): User
    {
        return User::factory()->create([
            'account_type' => 'planner',
            'plan' => 'planner',
        ]);
    }

    /** @return array{0: User, 1: Wedding} */
    protected function coupleWithWedding(array $weddingAttributes = []): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(array_merge(['owner_id' => $user->id], $weddingAttributes));
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_couple_creating_first_wedding_is_redirected_to_onboarding(): void
    {
        $couple = User::factory()->create();

        $this->actingAs($couple)
            ->post('/weddings', ['name' => 'Our Wedding'])
            ->assertRedirect(route('onboarding.wedding-details.show'));
    }

    public function test_planner_creating_a_wedding_is_not_redirected_to_onboarding(): void
    {
        $planner = $this->planner();

        $this->actingAs($planner)
            ->post('/weddings', ['name' => 'Client Wedding'])
            ->assertRedirect(route('planner.dashboard'));
    }

    public function test_show_renders_the_onboarding_page_for_a_fresh_incomplete_wedding(): void
    {
        [$user] = $this->coupleWithWedding(['settings' => []]);

        $this->actingAs($user)
            ->get('/onboarding/wedding-details')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('onboarding/wedding-details')
                ->has('bands')
                ->has('cities')
                ->has('vibes', 6)
                ->has('seasons', 4)
            );
    }

    public function test_show_redirects_to_dashboard_when_already_done(): void
    {
        [$user] = $this->coupleWithWedding(['settings' => ['onboarding_enrichment_done' => true]]);

        $this->actingAs($user)
            ->get('/onboarding/wedding-details')
            ->assertRedirect(route('dashboard'));
    }

    public function test_show_redirects_to_dashboard_when_user_has_no_current_wedding(): void
    {
        $user = User::factory()->create(['current_wedding_id' => null]);

        $this->actingAs($user)
            ->get('/onboarding/wedding-details')
            ->assertRedirect(route('dashboard'));
    }

    public function test_store_saves_band_city_vibe_and_season(): void
    {
        [$user, $wedding] = $this->coupleWithWedding(['settings' => []]);

        $this->actingAs($user)
            ->post('/onboarding/wedding-details', [
                'band' => '25-40k',
                'city' => 'toronto',
                'vibe' => 'boho-romantic',
                'season' => 'fall',
            ])
            ->assertRedirect(route('dashboard'));

        $wedding->refresh();

        $this->assertSame(BudgetAllocator::centsForBand('25-40k'), $wedding->total_budget_cents);
        $this->assertSame('toronto', $wedding->city);
        $this->assertSame('boho-romantic', $wedding->settings['vibe']);
        $this->assertSame('fall', $wedding->settings['season']);
        $this->assertTrue($wedding->settings['onboarding_enrichment_done']);
    }

    public function test_store_with_skip_marks_done_without_touching_other_fields(): void
    {
        [$user, $wedding] = $this->coupleWithWedding(['settings' => [], 'city' => null, 'total_budget_cents' => null]);

        $this->actingAs($user)
            ->post('/onboarding/wedding-details', ['skip' => true])
            ->assertRedirect(route('dashboard'));

        $wedding->refresh();

        $this->assertNull($wedding->city);
        $this->assertNull($wedding->total_budget_cents);
        $this->assertArrayNotHasKey('vibe', $wedding->settings);
        $this->assertArrayNotHasKey('season', $wedding->settings);
        $this->assertTrue($wedding->settings['onboarding_enrichment_done']);
    }

    public function test_store_with_only_city_is_fine_and_still_marks_done(): void
    {
        [$user, $wedding] = $this->coupleWithWedding(['settings' => []]);

        $this->actingAs($user)
            ->post('/onboarding/wedding-details', ['city' => 'ottawa'])
            ->assertRedirect(route('dashboard'));

        $wedding->refresh();

        $this->assertSame('ottawa', $wedding->city);
        $this->assertNull($wedding->total_budget_cents);
        $this->assertArrayNotHasKey('vibe', $wedding->settings);
        $this->assertTrue($wedding->settings['onboarding_enrichment_done']);
    }

    public function test_store_with_completely_empty_body_does_not_422(): void
    {
        [$user, $wedding] = $this->coupleWithWedding(['settings' => []]);

        $this->actingAs($user)
            ->post('/onboarding/wedding-details', [])
            ->assertRedirect(route('dashboard'));

        $wedding->refresh();
        $this->assertTrue($wedding->settings['onboarding_enrichment_done']);
    }

    public function test_show_is_never_shown_again_after_completion(): void
    {
        [$user] = $this->coupleWithWedding(['settings' => []]);

        $this->actingAs($user)->post('/onboarding/wedding-details', ['skip' => true]);

        $this->actingAs($user)
            ->get('/onboarding/wedding-details')
            ->assertRedirect(route('dashboard'));
    }
}

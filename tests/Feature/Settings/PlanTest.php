<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_page_shows_the_users_current_plan_and_tiers(): void
    {
        $user = User::factory()->plan('premium')->create();

        $this->actingAs($user)
            ->get('/settings/plan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/plan')
                ->where('current', 'premium')
                // Couples see only the couple tiers (free + Atelier).
                ->has('tiers', 2)
            );
    }

    public function test_plan_page_requires_authentication(): void
    {
        $this->get('/settings/plan')->assertRedirect('/login');
    }

    public function test_plan_page_exposes_referral_discount_eligible_true_for_a_referred_unused_user(): void
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create(['referred_by' => $referrer->id, 'account_type' => 'couple']);

        $this->actingAs($user)
            ->get('/settings/plan')
            ->assertInertia(fn ($page) => $page->where('referral_discount_eligible', true));
    }

    public function test_plan_page_exposes_referral_discount_eligible_false_for_a_non_referred_user(): void
    {
        $user = User::factory()->create(['referred_by' => null, 'account_type' => 'couple']);

        $this->actingAs($user)
            ->get('/settings/plan')
            ->assertInertia(fn ($page) => $page->where('referral_discount_eligible', false));
    }
}

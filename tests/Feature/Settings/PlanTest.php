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
                ->has('tiers', 3)
            );
    }

    public function test_plan_page_requires_authentication(): void
    {
        $this->get('/settings/plan')->assertRedirect('/login');
    }
}

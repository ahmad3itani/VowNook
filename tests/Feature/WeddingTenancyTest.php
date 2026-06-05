<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_is_capped_at_one_wedding(): void
    {
        $user = User::factory()->plan('free')->create();
        Wedding::factory()->create(['owner_id' => $user->id]);

        $this->assertFalse($user->can('create', Wedding::class));
    }

    public function test_planner_plan_allows_many_weddings(): void
    {
        $user = User::factory()->plan('planner')->create();
        Wedding::factory()->count(3)->create(['owner_id' => $user->id]);

        $this->assertTrue($user->can('create', Wedding::class));
    }

    public function test_user_can_switch_to_an_accessible_wedding(): void
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->post("/weddings/{$wedding->slug}/switch")
            ->assertRedirect();

        $this->assertSame($wedding->id, $user->fresh()->current_wedding_id);
    }

    public function test_user_cannot_switch_to_an_inaccessible_wedding(): void
    {
        $user = User::factory()->create();
        $other = Wedding::factory()->create();

        $this->actingAs($user)
            ->post("/weddings/{$other->slug}/switch")
            ->assertForbidden();
    }

    public function test_active_wedding_is_shared_to_inertia(): void
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('wedding.active.slug', $wedding->slug)
            );
    }
}

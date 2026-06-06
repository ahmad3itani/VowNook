<?php

namespace Tests\Feature;

use App\Models\BudgetItem;
use App\Models\Guest;
use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_aggregates_stats_for_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        Guest::factory()->create(['wedding_id' => $wedding->id, 'rsvp_status' => 'attending']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'rsvp_status' => 'declined']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'rsvp_status' => 'pending']);

        BudgetItem::factory()->create([
            'wedding_id' => $wedding->id,
            'estimated_cents' => 100000,
            'actual_cents' => 80000,
            'paid_cents' => 40000,
        ]);

        Task::factory()->create(['wedding_id' => $wedding->id, 'is_complete' => true]);
        Task::factory()->create(['wedding_id' => $wedding->id, 'is_complete' => false]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('guests.total', 3)
                ->where('guests.attending', 1)
                ->where('guests.declined', 1)
                ->where('guests.pending', 1)
                ->where('budget.estimated', 1000)
                ->where('budget.paid', 400)
                ->where('tasks.total', 2)
                ->where('tasks.completed', 1)
                ->where('tasks.outstanding', 1)
            );
    }

    public function test_dashboard_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Guest::factory()->create(['wedding_id' => $wedding->id]);

        // Another wedding's data must not bleed in.
        Guest::factory()->count(4)->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('guests.total', 1));
    }

    public function test_dashboard_renders_for_a_user_without_a_wedding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('dashboard')->where('summary', null));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\BudgetItem;
use App\Models\PlannerTemplate;
use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlannerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function planner(): User
    {
        return User::factory()->create([
            'account_type' => 'planner',
            'plan' => 'planner',
        ]);
    }

    public function test_planner_registration_sets_planner_plan(): void
    {
        $this->post('/register', [
            'name' => 'Atelier Events Co',
            'email' => 'planner@example.com',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
            'account_type' => 'planner',
        ])->assertRedirect();

        $user = User::where('email', 'planner@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isPlanner());
        $this->assertSame('planner', $user->plan);
    }

    public function test_dashboard_redirects_planner_to_hq(): void
    {
        $planner = $this->planner();

        $this->actingAs($planner)->get('/dashboard')->assertRedirect(route('planner.dashboard'));
    }

    public function test_workspace_flag_opens_the_wedding_overview_for_planners(): void
    {
        $planner = $this->planner();
        $wedding = Wedding::factory()->create(['owner_id' => $planner->id]);
        $planner->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($planner)
            ->get('/dashboard?workspace=1')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('dashboard'));
    }

    public function test_hq_shows_portfolio_and_attention(): void
    {
        $planner = $this->planner();
        $wedding = Wedding::factory()->create([
            'owner_id' => $planner->id,
            'event_date' => now()->addMonths(6),
        ]);

        Task::factory()->create([
            'wedding_id' => $wedding->id,
            'is_complete' => false,
            'due_date' => now()->subDays(3),
        ]);

        $this->actingAs($planner)
            ->get('/planner')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('planner/dashboard')
                ->has('weddings', 1)
                ->where('totals.overdue_tasks', 1));
    }

    public function test_non_planner_cannot_access_hq(): void
    {
        $couple = User::factory()->create();

        $this->actingAs($couple)->get('/planner')->assertForbidden();
    }

    public function test_planner_creates_client_wedding_and_invites_couple(): void
    {
        $planner = $this->planner();
        $couple = User::factory()->create(['email' => 'couple@example.com']);

        $this->actingAs($planner)
            ->post('/weddings', [
                'name' => 'Olivia & Noah',
                'event_date' => now()->addYear()->toDateString(),
                'couple_email' => 'couple@example.com',
            ])
            ->assertRedirect(route('planner.dashboard'));

        $wedding = Wedding::where('name', 'Olivia & Noah')->first();

        $this->assertNotNull($wedding);
        $this->assertSame($planner->id, $wedding->owner_id);
        $this->assertSame(Role::Partner, $wedding->roleFor($couple));
        $this->assertSame($wedding->id, $planner->refresh()->current_wedding_id);
    }

    public function test_couple_first_wedding_allowed_but_second_blocked_on_free_plan(): void
    {
        $couple = User::factory()->create();

        $this->actingAs($couple)
            ->post('/weddings', ['name' => 'Our Wedding'])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($couple)
            ->from('/dashboard')
            ->post('/weddings', ['name' => 'Second Wedding'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, $couple->ownedWeddings()->count());
    }

    public function test_vendor_cannot_create_a_wedding(): void
    {
        $vendor = User::factory()->create(['account_type' => 'vendor']);

        $this->actingAs($vendor)->post('/weddings', ['name' => 'Nope'])->assertForbidden();
    }

    public function test_checklist_template_round_trip_with_date_offsets(): void
    {
        $planner = $this->planner();

        $source = Wedding::factory()->create([
            'owner_id' => $planner->id,
            'event_date' => Carbon::today()->addDays(100),
        ]);

        // Due 30 days before the source event.
        Task::factory()->create([
            'wedding_id' => $source->id,
            'title' => 'Book the venue',
            'due_date' => Carbon::today()->addDays(70),
            'is_complete' => false,
        ]);

        $this->actingAs($planner)
            ->post('/planner/templates', [
                'name' => 'Standard plan',
                'type' => 'checklist',
                'wedding_id' => $source->id,
            ])
            ->assertRedirect();

        $template = PlannerTemplate::first();
        $this->assertSame(-30, $template->items[0]['offset_days']);

        // Apply to a wedding 200 days out → due lands at day 170.
        $target = Wedding::factory()->create([
            'owner_id' => $planner->id,
            'event_date' => Carbon::today()->addDays(200),
        ]);

        $this->actingAs($planner)
            ->post("/planner/templates/{$template->id}/apply", ['wedding_id' => $target->id])
            ->assertRedirect();

        $task = Task::where('wedding_id', $target->id)->first();

        $this->assertNotNull($task);
        $this->assertSame('Book the venue', $task->title);
        $this->assertTrue($task->due_date->equalTo(Carbon::today()->addDays(170)));
    }

    public function test_budget_template_recreates_categories_and_items(): void
    {
        $planner = $this->planner();

        $target = Wedding::factory()->create(['owner_id' => $planner->id]);

        $template = PlannerTemplate::create([
            'user_id' => $planner->id,
            'type' => 'budget',
            'name' => 'Standard budget',
            'items' => [
                ['name' => 'Venue deposit', 'category' => 'Venue', 'estimated_cents' => 500000],
                ['name' => 'Photographer', 'category' => 'Photo', 'estimated_cents' => 300000],
            ],
        ]);

        $this->actingAs($planner)
            ->post("/planner/templates/{$template->id}/apply", ['wedding_id' => $target->id])
            ->assertRedirect();

        $this->assertSame(2, BudgetItem::where('wedding_id', $target->id)->count());
        $this->assertSame(2, $target->budgetCategories()->count());
    }

    public function test_template_cannot_be_applied_to_inaccessible_wedding(): void
    {
        $planner = $this->planner();
        $stranger = User::factory()->create();
        $other = Wedding::factory()->create(['owner_id' => $stranger->id]);

        $template = PlannerTemplate::create([
            'user_id' => $planner->id,
            'type' => 'budget',
            'name' => 'Mine',
            'items' => [['name' => 'X', 'estimated_cents' => 100]],
        ]);

        $this->actingAs($planner)
            ->post("/planner/templates/{$template->id}/apply", ['wedding_id' => $other->id])
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Task::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        Task::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/checklist')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('checklist/index')
                ->has('tasks', 2)
            );
    }

    public function test_member_can_create_a_task_with_an_assignee(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $wedding->members()->attach($user->id, ['role' => Role::Owner->value]);

        $this->actingAs($user)->post('/checklist', [
            'title' => 'Book the florist',
            'category' => 'planning',
            'priority' => 'high',
            'due_date' => '2026-09-01',
            'assigned_to' => $user->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'wedding_id' => $wedding->id,
            'title' => 'Book the florist',
            'category' => 'planning',
            'priority' => 'high',
            'assigned_to' => $user->id,
            'is_complete' => false,
        ]);
    }

    public function test_viewer_cannot_create_a_task(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/checklist', [
            'title' => 'Nope',
            'category' => 'other',
            'priority' => 'low',
        ])->assertForbidden();
    }

    public function test_cannot_update_a_task_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = Task::factory()->create();

        $this->actingAs($user)->put("/checklist/{$foreign->id}", [
            'title' => 'Hijack',
            'category' => 'other',
            'priority' => 'low',
        ])->assertNotFound();
    }

    public function test_invalid_category_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/checklist', [
            'title' => 'Bad',
            'category' => 'spaceship',
            'priority' => 'low',
        ])->assertSessionHasErrors('category');
    }

    public function test_assignee_must_be_a_member_of_the_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $stranger = User::factory()->create();

        $this->actingAs($user)->post('/checklist', [
            'title' => 'Outsider task',
            'category' => 'planning',
            'priority' => 'medium',
            'assigned_to' => $stranger->id,
        ])->assertSessionHasErrors('assigned_to');
    }

    public function test_toggle_marks_a_task_complete_and_back(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $task = Task::factory()->create([
            'wedding_id' => $wedding->id,
            'is_complete' => false,
        ]);

        $this->actingAs($user)
            ->patch("/checklist/{$task->id}/toggle")
            ->assertRedirect();

        $task->refresh();
        $this->assertTrue($task->is_complete);
        $this->assertNotNull($task->completed_at);

        $this->actingAs($user)
            ->patch("/checklist/{$task->id}/toggle")
            ->assertRedirect();

        $task->refresh();
        $this->assertFalse($task->is_complete);
        $this->assertNull($task->completed_at);
    }

    public function test_stats_are_computed(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Task::factory()->complete()->create(['wedding_id' => $wedding->id]);
        Task::factory()->create([
            'wedding_id' => $wedding->id,
            'is_complete' => false,
            'due_date' => now()->subWeek()->toDateString(),
        ]);
        Task::factory()->create([
            'wedding_id' => $wedding->id,
            'is_complete' => false,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get('/checklist')
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 3)
                ->where('stats.completed', 1)
                ->where('stats.outstanding', 2)
                ->where('stats.overdue', 1)
            );
    }

    public function test_member_can_delete_a_task(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $task = Task::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->delete("/checklist/{$task->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $wedding->members()->attach($user->id, ['role' => Role::Owner->value, 'accepted_at' => now()]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_lists_members_of_the_active_wedding(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        $member = User::factory()->create();
        $wedding->members()->attach($member->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);

        $this->actingAs($owner)
            ->get('/collaborators')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('collaborators/index')
                ->has('members', 2)
            );
    }

    public function test_owner_can_invite_a_collaborator_by_email(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'helper@example.com',
            'role' => 'collaborator',
        ])->assertRedirect();

        // An invitation is created (membership happens on accept), not an immediate member.
        $this->assertDatabaseHas('wedding_invitations', [
            'wedding_id' => $wedding->id,
            'email' => 'helper@example.com',
            'role' => 'collaborator',
        ]);
    }

    public function test_can_invite_an_email_without_an_account(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'nobody@example.com',
            'role' => 'collaborator',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('wedding_invitations', [
            'wedding_id' => $wedding->id,
            'email' => 'nobody@example.com',
        ]);
    }

    public function test_cannot_assign_the_owner_role(): void
    {
        [$owner] = $this->ownerWithWedding();
        User::factory()->create(['email' => 'helper@example.com']);

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'helper@example.com',
            'role' => 'owner',
        ])->assertSessionHasErrors('role');
    }

    public function test_can_change_a_members_role(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        $member = User::factory()->create();
        $wedding->members()->attach($member->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);

        $this->actingAs($owner)->put("/collaborators/{$member->id}", [
            'role' => 'planner',
        ])->assertRedirect();

        $this->assertDatabaseHas('wedding_user', [
            'wedding_id' => $wedding->id,
            'user_id' => $member->id,
            'role' => 'planner',
        ]);
    }

    public function test_the_owner_cannot_be_modified(): void
    {
        [$owner] = $this->ownerWithWedding();

        $this->actingAs($owner)->put("/collaborators/{$owner->id}", [
            'role' => 'viewer',
        ])->assertForbidden();
    }

    public function test_the_owner_cannot_be_removed(): void
    {
        [$owner] = $this->ownerWithWedding();

        $this->actingAs($owner)
            ->delete("/collaborators/{$owner->id}")
            ->assertForbidden();
    }

    public function test_a_member_can_be_removed(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        $member = User::factory()->create(['current_wedding_id' => null]);
        $wedding->members()->attach($member->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);

        $this->actingAs($owner)
            ->delete("/collaborators/{$member->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('wedding_user', [
            'wedding_id' => $wedding->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_a_viewer_cannot_manage_collaborators(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        // Viewers have no collaborators access at all.
        $this->actingAs($viewer)->get('/collaborators')->assertForbidden();
        $this->actingAs($viewer)->post('/collaborators', [
            'email' => 'x@example.com',
            'role' => 'viewer',
        ])->assertForbidden();
    }
}

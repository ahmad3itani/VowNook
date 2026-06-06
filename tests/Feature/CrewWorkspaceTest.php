<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CrewMember;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrewWorkspaceTest extends TestCase
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
        CrewMember::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        CrewMember::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/crew')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('crew/index')
                ->has('members', 2)
            );
    }

    public function test_member_can_add_crew(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/crew', [
            'name' => 'Sophie Tremblay',
            'role' => 'maid_of_honour',
            'email' => 'sophie@example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('crew_members', [
            'wedding_id' => $wedding->id,
            'name' => 'Sophie Tremblay',
            'role' => 'maid_of_honour',
        ]);
    }

    public function test_invalid_role_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/crew', [
            'name' => 'Bad',
            'role' => 'wizard',
        ])->assertSessionHasErrors('role');
    }

    public function test_invalid_email_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/crew', [
            'name' => 'Bad',
            'role' => 'usher',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors('email');
    }

    public function test_viewer_cannot_add_crew(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/crew', [
            'name' => 'Nope',
            'role' => 'usher',
        ])->assertForbidden();
    }

    public function test_cannot_update_crew_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = CrewMember::factory()->create();

        $this->actingAs($user)->put("/crew/{$foreign->id}", [
            'name' => 'Hijack',
            'role' => 'usher',
        ])->assertNotFound();
    }

    public function test_member_can_remove_crew(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $member = CrewMember::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->delete("/crew/{$member->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('crew_members', ['id' => $member->id]);
    }
}

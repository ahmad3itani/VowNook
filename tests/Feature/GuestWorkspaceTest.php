<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    /** Create an owner with an active wedding. */
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
        Guest::factory()->count(2)->create(['wedding_id' => $wedding->id]);

        // Another wedding's guests must not leak in.
        Guest::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/guests')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('guests/index')
                ->has('guests', 2)
            );
    }

    public function test_member_can_create_a_guest(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/guests', [
            'first_name' => 'Dana',
            'last_name' => 'Cole',
            'side' => 'both',
            'age_group' => 'adult',
            'rsvp_status' => 'attending',
        ])->assertRedirect();

        $this->assertDatabaseHas('guests', [
            'wedding_id' => $wedding->id,
            'first_name' => 'Dana',
            'rsvp_status' => 'attending',
        ]);
    }

    public function test_viewer_cannot_create_a_guest(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/guests', [
            'first_name' => 'Nope',
            'side' => 'both',
            'age_group' => 'adult',
            'rsvp_status' => 'pending',
        ])->assertForbidden();
    }

    public function test_cannot_update_a_guest_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = Guest::factory()->create();

        $this->actingAs($user)->put("/guests/{$foreign->id}", [
            'first_name' => 'Hijack',
            'side' => 'both',
            'age_group' => 'adult',
            'rsvp_status' => 'pending',
        ])->assertNotFound();

        $this->assertDatabaseMissing('guests', ['first_name' => 'Hijack']);
    }

    public function test_rsvp_stats_are_computed(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Guest::factory()->count(2)->attending()->create(['wedding_id' => $wedding->id]);
        Guest::factory()->declined()->create(['wedding_id' => $wedding->id]);
        Guest::factory()->pending()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->get('/guests')
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 4)
                ->where('stats.attending', 2)
                ->where('stats.declined', 1)
                ->where('stats.pending', 1)
            );
    }

    public function test_group_must_belong_to_the_active_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreignGroup = GuestGroup::factory()->create();

        $this->actingAs($user)->post('/guests', [
            'first_name' => 'Dana',
            'side' => 'both',
            'age_group' => 'adult',
            'rsvp_status' => 'pending',
            'group_id' => $foreignGroup->id,
        ])->assertSessionHasErrors('group_id');
    }

    public function test_member_can_delete_a_guest(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->delete("/guests/{$guest->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('guests', ['id' => $guest->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Guest;
use App\Models\SeatingTable;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatingWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->plan('premium')->create(); // seating is a paid feature
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        SeatingTable::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        SeatingTable::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/seating')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('seating/index')
                ->has('tables', 2)
                // Premium infographics payload.
                ->has('stats.utilization')
                ->has('stats.unseated_attending')
                ->has('stats.tables_at_capacity')
                ->has('stats.sides.partner_one')
                ->has('stats.meals')
            );
    }

    public function test_member_can_create_a_table(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/seating', [
            'name' => 'Head Table',
            'shape' => 'rectangle',
            'capacity' => 10,
            'position_x' => 50,
            'position_y' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('seating_tables', [
            'wedding_id' => $wedding->id,
            'name' => 'Head Table',
            'shape' => 'rectangle',
            'capacity' => 10,
        ]);
    }

    public function test_viewer_cannot_create_a_table(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/seating', [
            'name' => 'Nope',
            'shape' => 'round',
            'capacity' => 8,
        ])->assertForbidden();
    }

    public function test_cannot_update_a_table_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = SeatingTable::factory()->create();

        $this->actingAs($user)->put("/seating/{$foreign->id}", [
            'name' => 'Hijack',
            'shape' => 'round',
            'capacity' => 8,
        ])->assertNotFound();
    }

    public function test_invalid_shape_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/seating', [
            'name' => 'Bad',
            'shape' => 'hexagon',
            'capacity' => 8,
        ])->assertSessionHasErrors('shape');
    }

    public function test_can_seat_a_guest_at_a_table(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id, 'capacity' => 8]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $guest->id,
            'table_id' => $table->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'table_id' => $table->id,
        ]);
    }

    public function test_cannot_seat_a_guest_at_a_full_table(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id, 'capacity' => 1]);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'table_id' => $table->id]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $guest->id,
            'table_id' => $table->id,
        ])->assertSessionHasErrors('table_id');

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'table_id' => null,
        ]);
    }

    public function test_can_unseat_a_guest(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id, 'table_id' => $table->id]);

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $guest->id,
            'table_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'table_id' => null,
        ]);
    }

    public function test_cannot_seat_a_guest_from_another_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id]);
        $foreignGuest = Guest::factory()->create();

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $foreignGuest->id,
            'table_id' => $table->id,
        ])->assertNotFound();
    }

    public function test_move_updates_table_position(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create([
            'wedding_id' => $wedding->id,
            'position_x' => 10,
            'position_y' => 10,
        ]);

        $this->actingAs($user)->patch("/seating/{$table->id}/move", [
            'position_x' => 75,
            'position_y' => 60,
        ])->assertRedirect();

        $this->assertDatabaseHas('seating_tables', [
            'id' => $table->id,
            'position_x' => 75,
            'position_y' => 60,
        ]);
    }

    public function test_deleting_a_table_frees_its_guests(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id, 'table_id' => $table->id]);

        $this->actingAs($user)
            ->delete("/seating/{$table->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('seating_tables', ['id' => $table->id]);
        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'table_id' => null]);
    }
}

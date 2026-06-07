<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Guest;
use App\Models\SeatingElement;
use App\Models\SeatingTable;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatingFloorPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_includes_elements_and_layout(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        SeatingElement::factory()->create(['wedding_id' => $wedding->id, 'type' => 'dance_floor']);

        $this->actingAs($user)
            ->get('/seating')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('seating/index')
                ->has('elements', 1)
                ->has('layout.room_width')
                ->has('options.elementTypes')
            );
    }

    public function test_member_can_set_the_room_size(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->patch('/seating-layout', [
            'room_width' => 60,
            'room_height' => 45,
        ])->assertRedirect();

        $this->assertDatabaseHas('seating_layouts', [
            'wedding_id' => $wedding->id,
            'room_width' => 60,
            'room_height' => 45,
        ]);
    }

    public function test_member_can_add_and_move_an_element(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/seating-elements', [
            'type' => 'bar',
            'position_x' => 10,
            'position_y' => 10,
            'width' => 20,
            'height' => 8,
        ])->assertRedirect();

        $element = SeatingElement::firstOrFail();
        $this->assertSame($wedding->id, $element->wedding_id);

        $this->actingAs($user)->patch("/seating-elements/{$element->id}/move", [
            'position_x' => 70,
            'position_y' => 55,
        ])->assertRedirect();

        $this->assertDatabaseHas('seating_elements', [
            'id' => $element->id,
            'position_x' => 70,
            'position_y' => 55,
        ]);
    }

    public function test_cannot_touch_an_element_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = SeatingElement::factory()->create();

        $this->actingAs($user)
            ->delete("/seating-elements/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_viewer_cannot_edit_the_floor_plan(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->patch('/seating-layout', [
            'room_width' => 60,
            'room_height' => 45,
        ])->assertForbidden();

        $this->actingAs($viewer)->post('/seating-elements', [
            'type' => 'bar',
            'position_x' => 10,
            'position_y' => 10,
            'width' => 20,
            'height' => 8,
        ])->assertForbidden();
    }

    public function test_can_assign_a_guest_to_a_specific_seat(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id, 'capacity' => 8]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $guest->id,
            'table_id' => $table->id,
            'seat_number' => 3,
        ])->assertRedirect();

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'table_id' => $table->id,
            'seat_number' => 3,
        ]);
    }

    public function test_cannot_assign_two_guests_to_the_same_seat(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id, 'capacity' => 8]);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'table_id' => $table->id, 'seat_number' => 3]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $guest->id,
            'table_id' => $table->id,
            'seat_number' => 3,
        ])->assertSessionHasErrors('seat_number');
    }

    public function test_cannot_assign_a_seat_beyond_capacity(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id, 'capacity' => 4]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $guest->id,
            'table_id' => $table->id,
            'seat_number' => 9,
        ])->assertSessionHasErrors('seat_number');
    }

    public function test_unseating_clears_the_seat_number(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id]);
        $guest = Guest::factory()->create([
            'wedding_id' => $wedding->id,
            'table_id' => $table->id,
            'seat_number' => 2,
        ]);

        $this->actingAs($user)->patch('/seating-assign', [
            'guest_id' => $guest->id,
            'table_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'table_id' => null,
            'seat_number' => null,
        ]);
    }
}

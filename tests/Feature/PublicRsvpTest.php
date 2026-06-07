<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRsvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_renders_the_public_rsvp_page(): void
    {
        $wedding = Wedding::factory()->create();

        $this->get("/w/{$wedding->slug}/rsvp")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/rsvp')
                ->where('searched', false)
                ->has('matches', 0)
                ->where('wedding.slug', $wedding->slug)
            );
    }

    public function test_lookup_returns_name_matched_guests_scoped_to_the_wedding(): void
    {
        $wedding = Wedding::factory()->create();
        Guest::factory()->create(['wedding_id' => $wedding->id, 'first_name' => 'Amelia', 'last_name' => 'Stone']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'first_name' => 'Noah', 'last_name' => 'Brooks']);

        // A same-named guest belonging to another wedding must not leak.
        $other = Wedding::factory()->create();
        Guest::factory()->create(['wedding_id' => $other->id, 'first_name' => 'Amelia', 'last_name' => 'Stone']);

        $this->get("/w/{$wedding->slug}/rsvp?name=Amelia")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/rsvp')
                ->where('searched', true)
                ->has('matches', 1)
                ->where('matches.0.name', 'Amelia Stone')
            );
    }

    public function test_a_short_query_does_not_search(): void
    {
        $wedding = Wedding::factory()->create();

        $this->get("/w/{$wedding->slug}/rsvp?name=A")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('searched', false)
                ->has('matches', 0)
            );
    }

    public function test_respond_updates_the_guest_reply(): void
    {
        $wedding = Wedding::factory()->create();
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'attending',
            'meal_choice' => 'Vegetarian',
            'dietary_notes' => 'No nuts',
        ])->assertRedirect();

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'rsvp_status' => 'attending',
            'meal_choice' => 'Vegetarian',
            'dietary_notes' => 'No nuts',
        ]);
    }

    public function test_respond_rejects_a_guest_from_another_wedding(): void
    {
        $wedding = Wedding::factory()->create();
        $foreignGuest = Guest::factory()->create();

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $foreignGuest->id,
            'rsvp_status' => 'attending',
        ])->assertNotFound();
    }

    public function test_respond_rejects_an_invalid_status(): void
    {
        $wedding = Wedding::factory()->create();
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'pending',
        ])->assertSessionHasErrors('rsvp_status');
    }
}

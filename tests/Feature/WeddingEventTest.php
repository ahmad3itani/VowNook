<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingEventTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    private function premiumCouple(): array
    {
        $user = User::factory()->plan('premium')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_free_couple_is_redirected_from_events(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/events')->assertRedirect(route('plan.edit'));
    }

    public function test_couple_can_create_an_event(): void
    {
        [$user, $wedding] = $this->premiumCouple();

        $this->actingAs($user)->post('/events', [
            'name' => 'Welcome cocktails',
            'type' => 'welcome',
            'event_date' => '2026-08-14',
            'start_time' => '6:00 PM',
            'venue_name' => 'The Rooftop',
            'is_rsvpable' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('wedding_events', [
            'wedding_id' => $wedding->id,
            'name' => 'Welcome cocktails',
            'type' => 'welcome',
            'is_rsvpable' => true,
        ]);
    }

    public function test_couple_can_update_and_delete_an_event(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        $event = WeddingEvent::create([
            'wedding_id' => $wedding->id, 'name' => 'Brunch', 'type' => 'brunch',
        ]);

        $this->actingAs($user)->put("/events/{$event->id}", [
            'name' => 'Farewell brunch', 'type' => 'brunch', 'is_rsvpable' => false,
        ])->assertRedirect();
        $this->assertDatabaseHas('wedding_events', ['id' => $event->id, 'name' => 'Farewell brunch', 'is_rsvpable' => false]);

        $this->actingAs($user)->delete("/events/{$event->id}")->assertRedirect();
        $this->assertDatabaseMissing('wedding_events', ['id' => $event->id]);
    }

    public function test_a_foreign_event_cannot_be_edited(): void
    {
        [$user] = $this->premiumCouple();
        $foreign = WeddingEvent::create([
            'wedding_id' => Wedding::factory()->create()->id, 'name' => 'X', 'type' => 'other',
        ]);

        $this->actingAs($user)->put("/events/{$foreign->id}", ['name' => 'Hijacked', 'type' => 'other'])
            ->assertNotFound();
    }

    public function test_guest_rsvp_records_per_event_replies(): void
    {
        $wedding = Wedding::factory()->create();
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);
        $rehearsal = WeddingEvent::create(['wedding_id' => $wedding->id, 'name' => 'Rehearsal', 'type' => 'rehearsal', 'is_rsvpable' => true]);
        $brunch = WeddingEvent::create(['wedding_id' => $wedding->id, 'name' => 'Brunch', 'type' => 'brunch', 'is_rsvpable' => true]);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'attending',
            'events' => [
                (string) $rehearsal->id => 'attending',
                (string) $brunch->id => 'declined',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('event_guest', [
            'wedding_event_id' => $rehearsal->id, 'guest_id' => $guest->id, 'rsvp_status' => 'attending',
        ]);
        $this->assertDatabaseHas('event_guest', [
            'wedding_event_id' => $brunch->id, 'guest_id' => $guest->id, 'rsvp_status' => 'declined',
        ]);
    }

    public function test_per_event_reply_for_a_foreign_event_is_ignored(): void
    {
        $wedding = Wedding::factory()->create();
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);
        $foreignEvent = WeddingEvent::create([
            'wedding_id' => Wedding::factory()->create()->id, 'name' => 'X', 'type' => 'other', 'is_rsvpable' => true,
        ]);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'attending',
            'events' => [(string) $foreignEvent->id => 'attending'],
        ])->assertRedirect();

        $this->assertDatabaseMissing('event_guest', [
            'wedding_event_id' => $foreignEvent->id, 'guest_id' => $guest->id,
        ]);
    }

    public function test_published_website_exposes_events(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingWebsite::factory()->create(['wedding_id' => $wedding->id, 'is_published' => true]);
        WeddingEvent::create(['wedding_id' => $wedding->id, 'name' => 'Ceremony', 'type' => 'ceremony', 'is_rsvpable' => true]);

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/website')
                ->has('events', 1)
                ->where('events.0.name', 'Ceremony')
            );
    }
}

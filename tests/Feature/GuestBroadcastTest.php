<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\GuestBroadcastMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuestBroadcastTest extends TestCase
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

    public function test_free_couple_is_redirected_from_messages(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/messages')->assertRedirect(route('plan.edit'));
    }

    public function test_broadcast_emails_only_the_chosen_audience(): void
    {
        Notification::fake();
        [$user, $wedding] = $this->premiumCouple();

        $attending = Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'a@x.com', 'rsvp_status' => 'attending']);
        $pending = Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'p@x.com', 'rsvp_status' => 'pending']);
        // No email → never receives.
        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => null, 'rsvp_status' => 'attending']);

        $this->actingAs($user)->post('/messages', [
            'subject' => 'See you soon!',
            'body' => "Hi everyone,\n\nThe ceremony starts at 4:30.",
            'audience' => 'attending',
        ])->assertRedirect();

        Notification::assertSentOnDemand(GuestBroadcastMessage::class, function ($notification, $channels, $notifiable) use ($attending) {
            return in_array($attending->email, (array) $notifiable->routes['mail'], true);
        });
        Notification::assertSentOnDemandTimes(GuestBroadcastMessage::class, 1);

        $this->assertDatabaseHas('guest_broadcasts', [
            'wedding_id' => $wedding->id, 'audience' => 'attending', 'recipient_count' => 1, 'subject' => 'See you soon!',
        ]);

        // The pending guest was not in scope.
        $this->assertSame('p@x.com', $pending->email);
    }

    public function test_all_audience_targets_every_guest_with_an_email(): void
    {
        Notification::fake();
        [$user, $wedding] = $this->premiumCouple();

        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'a@x.com', 'rsvp_status' => 'attending']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'p@x.com', 'rsvp_status' => 'pending']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => '', 'rsvp_status' => 'declined']);

        $this->actingAs($user)->post('/messages', [
            'subject' => 'Hello', 'body' => 'A note for all.', 'audience' => 'all',
        ])->assertRedirect();

        Notification::assertSentOnDemandTimes(GuestBroadcastMessage::class, 2);
    }

    public function test_index_exposes_audience_counts(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'a@x.com', 'rsvp_status' => 'attending']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'p@x.com', 'rsvp_status' => 'pending']);

        $this->actingAs($user)->get('/messages')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('communications/index')
                ->where('counts.all', 2)
                ->where('counts.attending', 1)
                ->where('counts.pending', 1)
            );
    }

    public function test_broadcast_does_not_reach_another_weddings_guests(): void
    {
        Notification::fake();
        [$user, $wedding] = $this->premiumCouple();
        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'mine@x.com', 'rsvp_status' => 'attending']);

        // A guest belonging to another wedding with the same status + an email.
        Guest::factory()->create(['email' => 'other@x.com', 'rsvp_status' => 'attending']);

        $this->actingAs($user)->post('/messages', [
            'subject' => 'Hi', 'body' => 'Scoped note.', 'audience' => 'attending',
        ])->assertRedirect();

        Notification::assertSentOnDemandTimes(GuestBroadcastMessage::class, 1);
    }
}

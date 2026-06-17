<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestSend;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\GuestSaveTheDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SaveTheDateTest extends TestCase
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

    public function test_free_couple_is_redirected(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/save-the-dates')->assertRedirect(route('plan.edit'));
    }

    public function test_sending_creates_a_send_row_and_emails_guests_with_an_email(): void
    {
        Notification::fake();
        [$user, $wedding] = $this->premiumCouple();

        $g1 = Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'a@x.com']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => null]);

        $this->actingAs($user)->post('/save-the-dates/send', ['kind' => 'save_the_date'])->assertRedirect();

        Notification::assertSentOnDemandTimes(GuestSaveTheDate::class, 1);
        $this->assertDatabaseHas('guest_sends', [
            'wedding_id' => $wedding->id, 'guest_id' => $g1->id, 'kind' => 'save_the_date',
        ]);
        $this->assertNotNull(GuestSend::where('guest_id', $g1->id)->first()->sent_at);
    }

    public function test_resending_refreshes_the_token_and_resets_opens(): void
    {
        Notification::fake();
        [$user, $wedding] = $this->premiumCouple();
        $g = Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'a@x.com']);

        $this->actingAs($user)->post('/save-the-dates/send', ['kind' => 'save_the_date']);
        $first = GuestSend::where('guest_id', $g->id)->first();
        $first->forceFill(['opened_at' => now()])->save();

        $this->actingAs($user)->post('/save-the-dates/send', ['kind' => 'save_the_date']);
        $second = GuestSend::where('guest_id', $g->id)->first();

        // Same row (unique guest+kind), new token, opens reset.
        $this->assertSame($first->id, $second->id);
        $this->assertNotSame($first->token, $second->token);
        $this->assertNull($second->opened_at);
        $this->assertSame(1, GuestSend::where('guest_id', $g->id)->count());
    }

    public function test_tracking_pixel_flips_opened_at_once_and_returns_a_gif(): void
    {
        [, $wedding] = $this->premiumCouple();
        $g = Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'a@x.com']);
        $send = GuestSend::create([
            'wedding_id' => $wedding->id, 'guest_id' => $g->id, 'kind' => 'invitation',
            'token' => 'tok123abc', 'sent_at' => now(),
        ]);

        $response = $this->get('/e/tok123abc.gif');
        $response->assertOk();
        $this->assertSame('image/gif', $response->headers->get('Content-Type'));

        $opened = $send->fresh()->opened_at;
        $this->assertNotNull($opened);

        // A second open does not move the timestamp.
        $this->get('/e/tok123abc.gif')->assertOk();
        $this->assertEquals($opened->timestamp, $send->fresh()->opened_at->timestamp);
    }

    public function test_an_unknown_token_still_returns_a_gif(): void
    {
        $this->get('/e/nope.gif')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');
    }

    public function test_dashboard_reports_sent_opened_and_responded(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        $replied = Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'a@x.com', 'rsvp_status' => 'attending']);
        $pending = Guest::factory()->create(['wedding_id' => $wedding->id, 'email' => 'b@x.com', 'rsvp_status' => 'pending']);

        GuestSend::create(['wedding_id' => $wedding->id, 'guest_id' => $replied->id, 'kind' => 'invitation', 'token' => 't1', 'sent_at' => now(), 'opened_at' => now()]);
        GuestSend::create(['wedding_id' => $wedding->id, 'guest_id' => $pending->id, 'kind' => 'invitation', 'token' => 't2', 'sent_at' => now()]);

        $this->actingAs($user)->get('/save-the-dates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('save-the-dates/index')
                ->where('stats.invitation.sent', 2)
                ->where('stats.invitation.opened', 1)
                ->where('stats.invitation.responded', 1)
            );
    }
}

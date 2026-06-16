<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\InquiryStatus;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use App\Notifications\GuestRsvpReminder;
use App\Notifications\OnboardingNudge;
use App\Notifications\PostWeddingThankYou;
use App\Notifications\VendorUnansweredInquiries;
use App\Notifications\WeddingMilestone;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MarketingEngineTest extends TestCase
{
    use RefreshDatabase;

    // ── Phase 0: preferences + CASL ──────────────────────────────────────────

    public function test_marketing_notification_respects_opt_out_but_keeps_database(): void
    {
        $optedOut = User::factory()->create(['email_preferences' => ['planning_tips' => false]]);
        $optedIn = User::factory()->create();

        $nudge = new OnboardingNudge([['label' => 'x', 'url' => '/guests']]);

        $this->assertEqualsCanonicalizing(['database'], $nudge->via($optedOut));
        $this->assertEqualsCanonicalizing(['database', 'mail'], $nudge->via($optedIn));

        // Transactional always mails regardless of prefs.
        $this->assertEqualsCanonicalizing(['mail', 'database'], (new WelcomeNotification())->via($optedOut));
    }

    public function test_signed_unsubscribe_link_flips_one_category(): void
    {
        $user = User::factory()->create();

        $url = URL::signedRoute('email.unsubscribe', ['user' => $user->id, 'category' => 'digest']);
        $this->get($url)->assertOk();

        $this->assertFalse($user->fresh()->email_preferences['digest']);
    }

    public function test_tampered_unsubscribe_link_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->get("/email/unsubscribe/{$user->id}/digest")->assertForbidden();
    }

    public function test_user_can_save_notification_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/settings/notifications', [
            'preferences' => ['product_updates' => true, 'planning_tips' => false, 'milestones' => true, 'digest' => false],
        ])->assertRedirect();

        $prefs = $user->fresh()->email_preferences;
        $this->assertFalse($prefs['planning_tips']);
        $this->assertFalse($prefs['digest']);
        $this->assertTrue($prefs['milestones']);
    }

    // ── Phase A: lifecycle ───────────────────────────────────────────────────

    public function test_registration_sends_a_welcome_notification(): void
    {
        Notification::fake();

        $user = app(CreateNewUser::class)->create([
            'name' => 'Test Couple',
            'email' => 'couple@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        Notification::assertSentTo($user, WelcomeNotification::class);
        $this->assertNotNull($user->marketing_consent_at);
    }

    public function test_milestone_fires_only_at_threshold_and_only_once(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id, 'event_date' => now()->addDays(100)]);

        $this->artisan('weddings:milestones')->assertSuccessful();
        Notification::assertSentToTimes($owner, WeddingMilestone::class, 1);

        // Second run must not re-send the same milestone.
        $this->artisan('weddings:milestones')->assertSuccessful();
        Notification::assertSentToTimes($owner, WeddingMilestone::class, 1);
    }

    public function test_milestone_does_not_fire_off_threshold(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        Wedding::factory()->create(['owner_id' => $owner->id, 'event_date' => now()->addDays(55)]);

        $this->artisan('weddings:milestones')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_onboarding_nudge_skips_couples_who_have_guests(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $wedding->forceFill(['created_at' => now()->subDays(4)])->save();
        Guest::factory()->create(['wedding_id' => $wedding->id]);

        // Even with guests, website + budget steps remain, so a nudge still sends —
        // assert the guest step is NOT among the steps.
        $this->artisan('couples:onboarding-nudge')->assertSuccessful();

        Notification::assertSentTo($owner, OnboardingNudge::class, function ($notification) {
            $labels = array_column($notification->steps, 'label');

            return ! in_array('Add your guest list', $labels, true);
        });
    }

    public function test_guest_reminders_only_target_pending_guests_with_email(): void
    {
        Notification::fake();

        $wedding = Wedding::factory()->create(['event_date' => now()->addDays(30)]);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'rsvp_status' => 'pending', 'email' => 'a@example.com']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'rsvp_status' => 'attending', 'email' => 'b@example.com']);
        Guest::factory()->create(['wedding_id' => $wedding->id, 'rsvp_status' => 'pending', 'email' => null]);

        $sent = \App\Support\GuestReminders::sendFor($wedding);

        $this->assertSame(1, $sent);
        Notification::assertSentOnDemand(GuestRsvpReminder::class);
    }

    public function test_vendor_reengagement_only_targets_old_unanswered_inquiries(): void
    {
        Notification::fake();

        $vendorUser = User::factory()->create();
        $profile = VendorProfile::factory()->create(['user_id' => $vendorUser->id]);

        // Old + unanswered → counts.
        $old = Inquiry::factory()->create([
            'vendor_profile_id' => $profile->id,
            'status' => InquiryStatus::Requested->value,
            'first_response_at' => null,
        ]);
        $old->forceFill(['created_at' => now()->subDays(2)])->save();

        // Recent → ignored.
        Inquiry::factory()->create([
            'vendor_profile_id' => $profile->id,
            'status' => InquiryStatus::Requested->value,
            'first_response_at' => null,
        ]);

        $this->artisan('vendors:unanswered-inquiries')->assertSuccessful();

        Notification::assertSentTo($vendorUser, VendorUnansweredInquiries::class, function ($n) {
            return $n->count === 1;
        });
    }

    // ── Phase B: in-app notification center ──────────────────────────────────

    public function test_database_notification_is_stored_and_can_be_marked_read(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id, 'rsvp_status' => 'attending']);

        $owner->notify(new \App\Notifications\RsvpReceived($wedding, $guest));

        $this->assertSame(1, $owner->unreadNotifications()->count());

        $id = $owner->notifications()->first()->id;
        $this->actingAs($owner)->post("/notifications/{$id}/read")->assertRedirect();

        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_a_user_cannot_mark_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);
        $owner->notify(new \App\Notifications\RsvpReceived($wedding, $guest));

        $id = $owner->notifications()->first()->id;
        $other = User::factory()->create();

        $this->actingAs($other)->post("/notifications/{$id}/read")->assertNotFound();
    }

    public function test_mark_all_read_clears_the_badge(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);
        $owner->notify(new \App\Notifications\RsvpReceived($wedding, $guest));
        $owner->notify(new \App\Notifications\RsvpReceived($wedding, $guest));

        $this->actingAs($owner)->post('/notifications/read-all')->assertRedirect();

        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }

    // ── Phase C: promos & referrals ──────────────────────────────────────────

    public function test_redeeming_a_valid_code_comps_the_plan(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $code = \App\Models\PromoCode::create([
            'code' => 'WEDDING2026', 'plan' => 'premium', 'duration_days' => 365,
        ]);

        $this->actingAs($user)->post('/settings/plan/redeem', ['code' => 'wedding2026'])->assertRedirect();

        $user->refresh();
        $this->assertSame('premium', $user->plan);
        $this->assertNotNull($user->plan_comped_until);
        $this->assertSame(1, $code->fresh()->redeemed_count);
    }

    public function test_a_code_cannot_be_redeemed_twice_by_the_same_user(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        \App\Models\PromoCode::create(['code' => 'ONCE', 'plan' => 'premium', 'duration_days' => 30]);

        $this->actingAs($user)->post('/settings/plan/redeem', ['code' => 'ONCE'])->assertRedirect();
        $this->actingAs($user)->post('/settings/plan/redeem', ['code' => 'ONCE'])
            ->assertSessionHasErrors('code');
    }

    public function test_expire_comps_reverts_lapsed_plans(): void
    {
        $user = User::factory()->create(['plan' => 'premium', 'plan_comped_until' => now()->subDay()]);

        \App\Support\PlanComp::expireOverdue();

        $this->assertSame('free', $user->fresh()->plan);
        $this->assertNull($user->fresh()->plan_comped_until);
    }

    public function test_registration_attributes_a_referrer(): void
    {
        $referrer = User::factory()->create();

        $referred = app(CreateNewUser::class)->create([
            'name' => 'Referred Couple',
            'email' => 'referred@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'ref' => $referrer->referral_code,
        ]);

        $this->assertSame($referrer->id, $referred->referred_by);
    }

    public function test_publishing_website_rewards_the_referrer_once(): void
    {
        Notification::fake();

        $referrer = User::factory()->create(['plan' => 'free']);
        // Publishing is an Atelier feature, so the referred couple must be paid
        // for the publish (the qualifying action) to actually go through.
        $referred = User::factory()->plan('premium')->create(['referred_by' => $referrer->id]);
        $wedding = Wedding::factory()->create(['owner_id' => $referred->id]);
        $referred->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($referred)->put('/website', ['is_published' => true])->assertRedirect();

        $referrer->refresh();
        $this->assertSame('premium', $referrer->plan);
        $this->assertNotNull($referrer->plan_comped_until);
        Notification::assertSentTo($referrer, \App\Notifications\ReferralRewarded::class);

        // Second publish must not double-reward.
        $compAfterFirst = $referrer->plan_comped_until;
        $this->actingAs($referred)->put('/website', ['is_published' => true])->assertRedirect();
        $this->assertEquals($compAfterFirst, $referrer->fresh()->plan_comped_until);
    }

    public function test_admin_can_toggle_founding_vendor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = VendorProfile::factory()->create(['is_founding' => false]);

        $this->actingAs($admin)->patch("/admin/vendors/{$profile->slug}/founding")->assertRedirect();

        $this->assertTrue($profile->fresh()->is_founding);
    }

    // ── Phase D: post-wedding flow ───────────────────────────────────────────

    public function test_post_wedding_flow_thanks_the_couple_and_is_idempotent(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id, 'event_date' => now()->subDays(3)]);

        $this->artisan('weddings:post-wedding')->assertSuccessful();
        Notification::assertSentToTimes($owner, PostWeddingThankYou::class, 1);

        // A perk code was issued.
        $this->assertSame(1, \App\Models\PromoCode::where('note', 'Post-wedding thank-you perk.')->count());

        // Re-run must not re-send or re-issue.
        $this->artisan('weddings:post-wedding')->assertSuccessful();
        Notification::assertSentToTimes($owner, PostWeddingThankYou::class, 1);
        $this->assertSame(1, \App\Models\PromoCode::where('note', 'Post-wedding thank-you perk.')->count());
    }

    public function test_post_wedding_flow_skips_future_weddings(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        Wedding::factory()->create(['owner_id' => $owner->id, 'event_date' => now()->addDays(10)]);

        $this->artisan('weddings:post-wedding')->assertSuccessful();
        Notification::assertNothingSent();
    }
}

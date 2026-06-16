<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use App\Notifications\AdminDailyDigest;
use App\Notifications\NewBookingPlaced;
use App\Notifications\NewUserRegistered;
use App\Notifications\VendorSubmittedForReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admins_are_notified_of_a_new_signup(): void
    {
        $admin = $this->admin();
        Notification::fake();

        app(CreateNewUser::class)->create([
            'name' => 'New Couple',
            'email' => 'newcouple@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        Notification::assertSentTo($admin, NewUserRegistered::class);
    }

    public function test_admins_are_notified_when_a_vendor_submits_for_review(): void
    {
        $admin = $this->admin();
        $vendorUser = User::factory()->create(['account_type' => 'vendor']);
        VendorProfile::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Petals & Co',
            'category' => 'florist',
            'status' => VendorProfileStatus::Draft->value,
        ]);

        Notification::fake();

        $this->actingAs($vendorUser)->post('/vendor/profile/submit')->assertRedirect();

        Notification::assertSentTo($admin, VendorSubmittedForReview::class);
    }

    public function test_admins_are_notified_when_a_booking_is_placed(): void
    {
        $admin = $this->admin();

        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $vendorUser = User::factory()->create(['account_type' => 'vendor']);
        $vendor = VendorProfile::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Petals & Co',
            'category' => 'florist',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ]);

        $inquiry = Inquiry::create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $user->id,
            'vendor_profile_id' => $vendor->id,
            'message' => 'Quote please',
            'status' => InquiryStatus::Offered->value,
        ]);
        Offer::create([
            'inquiry_id' => $inquiry->id,
            'total_cents' => 300000,
            'deposit_cents' => 50000,
            'status' => OfferStatus::Sent->value,
        ]);

        Notification::fake();

        $this->actingAs($user)->post("/vendors/quotes/{$inquiry->id}/accept")->assertRedirect();

        Notification::assertSentTo($admin, NewBookingPlaced::class);
    }

    public function test_daily_digest_emails_admins_when_there_is_activity(): void
    {
        $admin = $this->admin(); // the new admin counts as a signup in the last 24h
        Notification::fake();

        $this->artisan('admin:daily-digest')->assertSuccessful();

        Notification::assertSentTo($admin, AdminDailyDigest::class);
    }

    public function test_daily_digest_is_skipped_on_a_quiet_day(): void
    {
        $this->admin();
        // Backdate so nothing falls inside the last 24h window.
        User::query()->update(['created_at' => now()->subDays(3)]);

        Notification::fake();

        $this->artisan('admin:daily-digest')->assertSuccessful();

        Notification::assertNothingSent();
    }
}

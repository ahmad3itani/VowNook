<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use App\Notifications\NewInquiryReceived;
use App\Notifications\NewOfferReceived;
use App\Notifications\OfferAccepted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    /** @return array{0: VendorProfile, 1: User} */
    protected function publishedVendor(): array
    {
        $vendorUser = User::factory()->create(['account_type' => 'vendor']);

        $profile = VendorProfile::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Petals & Co',
            'category' => 'florist',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ]);

        return [$profile, $vendorUser];
    }

    public function test_sending_an_inquiry_notifies_the_vendor_user(): void
    {
        Notification::fake();

        [$user] = $this->ownerWithWedding();
        [$vendor, $vendorUser] = $this->publishedVendor();

        $this->actingAs($user)
            ->post('/vendors/quotes', [
                'vendor_profile_id' => $vendor->id,
                'message' => 'Are you free for our June wedding?',
            ])
            ->assertRedirect();

        Notification::assertSentTo($vendorUser, NewInquiryReceived::class);
    }

    public function test_vendor_sending_an_offer_notifies_the_couple(): void
    {
        Notification::fake();

        [$user, $wedding] = $this->ownerWithWedding();
        [$vendor, $vendorUser] = $this->publishedVendor();

        $inquiry = Inquiry::create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $user->id,
            'vendor_profile_id' => $vendor->id,
            'message' => 'Quote please',
            'status' => InquiryStatus::Requested->value,
        ]);

        $this->actingAs($vendorUser)
            ->post("/vendor/inquiries/{$inquiry->id}/offer", [
                'total_cents' => 300000,
                'deposit_cents' => 50000,
            ])
            ->assertRedirect();

        Notification::assertSentTo($user, NewOfferReceived::class);
    }

    public function test_accepting_an_offer_notifies_the_vendor(): void
    {
        Notification::fake();

        [$user, $wedding] = $this->ownerWithWedding();
        [$vendor, $vendorUser] = $this->publishedVendor();

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

        $this->actingAs($user)
            ->post("/vendors/quotes/{$inquiry->id}/accept")
            ->assertRedirect();

        Notification::assertSentTo($vendorUser, OfferAccepted::class);
    }
}

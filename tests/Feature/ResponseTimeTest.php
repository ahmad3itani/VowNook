<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseTimeTest extends TestCase
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

    protected function makeInquiry(Wedding $wedding, User $couple, VendorProfile $vendor): Inquiry
    {
        return Inquiry::create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $couple->id,
            'vendor_profile_id' => $vendor->id,
            'message' => 'Quote please',
            'status' => InquiryStatus::Requested->value,
        ]);
    }

    public function test_first_offer_stamps_first_response_and_syncs_stats(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        [$vendor, $vendorUser] = $this->publishedVendor();

        $inquiry = $this->makeInquiry($wedding, $user, $vendor);
        $inquiry->forceFill(['created_at' => now()->subHours(5)->addMinutes(10)])->save();

        $this->actingAs($vendorUser)
            ->post("/vendor/inquiries/{$inquiry->id}/offer", ['total_cents' => 300000])
            ->assertRedirect();

        $inquiry->refresh();
        $this->assertNotNull($inquiry->first_response_at);

        $vendor->refresh();
        $this->assertSame(1, $vendor->response_count);
        $this->assertSame(5, $vendor->response_hours);
    }

    public function test_later_replies_do_not_move_the_first_response_stamp(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        [$vendor, $vendorUser] = $this->publishedVendor();

        $inquiry = $this->makeInquiry($wedding, $user, $vendor);
        $stamp = now()->subHours(2)->startOfSecond();
        $inquiry->forceFill(['first_response_at' => $stamp])->save();

        $this->actingAs($vendorUser)
            ->post("/inquiries/{$inquiry->id}/messages", ['body' => 'Following up!'])
            ->assertRedirect();

        $this->assertTrue($inquiry->refresh()->first_response_at->equalTo($stamp));
    }

    public function test_couple_message_does_not_count_as_vendor_response(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        [$vendor] = $this->publishedVendor();

        $inquiry = $this->makeInquiry($wedding, $user, $vendor);

        $this->actingAs($user)
            ->post("/inquiries/{$inquiry->id}/messages", ['body' => 'Any update?'])
            ->assertRedirect();

        $this->assertNull($inquiry->refresh()->first_response_at);
    }

    public function test_vendor_message_counts_as_first_response(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        [$vendor, $vendorUser] = $this->publishedVendor();

        $inquiry = $this->makeInquiry($wedding, $user, $vendor);

        $this->actingAs($vendorUser)
            ->post("/inquiries/{$inquiry->id}/messages", ['body' => 'Happy to help!'])
            ->assertRedirect();

        $this->assertNotNull($inquiry->refresh()->first_response_at);
        $this->assertSame(1, $vendor->refresh()->response_count);
    }

    public function test_badge_is_hidden_until_three_responses(): void
    {
        [$vendor] = $this->publishedVendor();
        $vendor->forceFill(['response_hours' => 4, 'response_count' => 2])->save();

        $this->get("/marketplace/{$vendor->slug}")
            ->assertInertia(fn ($page) => $page->where('profile.response_hours', null));

        $vendor->forceFill(['response_count' => 3])->save();

        $this->get("/marketplace/{$vendor->slug}")
            ->assertInertia(fn ($page) => $page->where('profile.response_hours', 4));
    }
}

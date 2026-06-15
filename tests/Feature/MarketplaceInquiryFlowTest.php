<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProfile;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceInquiryFlowTest extends TestCase
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

    protected function publishedVendor(): VendorProfile
    {
        $vendorUser = User::factory()->create(['account_type' => 'vendor']);

        return VendorProfile::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Petals & Co',
            'category' => 'florist',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ]);
    }

    public function test_couple_can_browse_the_in_portal_marketplace(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->publishedVendor();

        $this->actingAs($user)
            ->get('/vendors/marketplace')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendors/marketplace')
                ->where('total', 1)
            );
    }

    public function test_browsing_with_a_region_filter_returns_only_matching_vendors(): void
    {
        [$user] = $this->ownerWithWedding();

        $ontarioVendor = $this->publishedVendor();
        $ontarioVendor->update(['region' => 'ON']);

        $bcUser = User::factory()->create(['account_type' => 'vendor']);
        VendorProfile::create([
            'user_id' => $bcUser->id,
            'business_name' => 'Pacific Blooms',
            'category' => 'florist',
            'region' => 'BC',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ]);

        $this->actingAs($user)
            ->get('/vendors/marketplace?region=ON')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendors/marketplace')
                ->where('total', 1)
                ->where('profiles.0.region', 'ON')
                ->where('filters.region', 'ON')
            );
    }

    public function test_couple_can_send_an_inquiry_and_see_it_under_quotes(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $vendor = $this->publishedVendor();

        $this->actingAs($user)
            ->post('/vendors/quotes', [
                'vendor_profile_id' => $vendor->id,
                'message' => 'Are you free for our June wedding?',
            ])
            ->assertRedirect();

        $inquiry = Inquiry::firstOrFail();
        $this->assertSame($wedding->id, $inquiry->wedding_id);
        $this->assertSame(InquiryStatus::Requested, $inquiry->status);

        $this->actingAs($user)
            ->get('/vendors/quotes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inquiries/index')
                ->has('inquiries', 1)
            );
    }

    public function test_accepting_an_offer_creates_a_booking_and_crm_vendor_row(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $vendor = $this->publishedVendor();

        $inquiry = Inquiry::create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $user->id,
            'vendor_profile_id' => $vendor->id,
            'message' => 'Quote please',
            'status' => InquiryStatus::Offered->value,
        ]);

        $offer = Offer::create([
            'inquiry_id' => $inquiry->id,
            'total_cents' => 300000,
            'deposit_cents' => 50000,
            'status' => OfferStatus::Sent->value,
        ]);

        $this->actingAs($user)
            ->post("/vendors/quotes/{$inquiry->id}/accept")
            ->assertRedirect();

        // Booking created with the tiered platform fee (8% under the $5k threshold).
        $booking = Booking::firstOrFail();
        $this->assertSame($offer->id, $booking->offer_id);
        $this->assertSame(300000, $booking->total_cents);
        $this->assertSame(24000, $booking->platform_fee_cents);

        // CRM vendor row bridged into the couple's wedding and linked to the booking.
        $crmVendor = Vendor::where('wedding_id', $wedding->id)->firstOrFail();
        $this->assertSame('Petals & Co', $crmVendor->name);
        $this->assertSame($crmVendor->id, $booking->vendor_id);
        $this->assertSame($vendor->user_id, $crmVendor->vendor_user_id);

        $inquiry->refresh();
        $this->assertSame(InquiryStatus::Accepted, $inquiry->status);
    }

    public function test_compare_page_lists_offers(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $vendor = $this->publishedVendor();

        $inquiry = Inquiry::create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $user->id,
            'vendor_profile_id' => $vendor->id,
            'message' => 'Quote please',
            'status' => InquiryStatus::Offered->value,
        ]);

        Offer::create([
            'inquiry_id' => $inquiry->id,
            'total_cents' => 250000,
            'status' => OfferStatus::Sent->value,
        ]);

        $this->actingAs($user)
            ->get('/vendors/quotes/compare')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendors/quote-compare')
                ->has('groups', 1)
            );
    }
}

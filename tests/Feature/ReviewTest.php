<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    /** @return array{0: User, 1: VendorProfile} */
    protected function publishedVendor(string $name = 'Petals & Co'): array
    {
        $vendorUser = User::factory()->create(['account_type' => 'vendor']);

        $profile = VendorProfile::create([
            'user_id' => $vendorUser->id,
            'business_name' => $name,
            'category' => 'florist',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ]);

        return [$vendorUser, $profile];
    }

    protected function bookingFor(User $couple, Wedding $wedding, VendorProfile $profile): Booking
    {
        $inquiry = Inquiry::create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $couple->id,
            'vendor_profile_id' => $profile->id,
            'message' => 'Quote please',
            'status' => InquiryStatus::Accepted->value,
        ]);

        $offer = Offer::create([
            'inquiry_id' => $inquiry->id,
            'total_cents' => 300000,
            'deposit_cents' => 50000,
            'status' => OfferStatus::Accepted->value,
        ]);

        return Booking::create([
            'inquiry_id' => $inquiry->id,
            'offer_id' => $offer->id,
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $profile->id,
            'total_cents' => 300000,
            'deposit_cents' => 50000,
            'platform_fee_cents' => 30000,
            'status' => BookingStatus::DepositPaid->value,
        ]);
    }

    public function test_couple_can_review_their_own_booking_and_rating_syncs(): void
    {
        [$couple, $wedding] = $this->ownerWithWedding();
        [, $profile] = $this->publishedVendor();
        $booking = $this->bookingFor($couple, $wedding, $profile);

        $this->actingAs($couple)
            ->post('/reviews', [
                'booking_id' => $booking->id,
                'rating' => 4,
                'body' => 'Beautiful arrangements, on time.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $review = Review::firstOrFail();
        $this->assertSame($booking->id, $review->booking_id);
        $this->assertSame($wedding->id, $review->wedding_id);
        $this->assertSame($profile->id, $review->vendor_profile_id);
        $this->assertSame($couple->id, $review->couple_user_id);
        $this->assertSame(4, $review->rating);

        $profile->refresh();
        $this->assertSame(1, $profile->rating_count);
        $this->assertSame(4.0, (float) $profile->rating_avg);
    }

    public function test_duplicate_review_for_the_same_booking_is_rejected(): void
    {
        [$couple, $wedding] = $this->ownerWithWedding();
        [, $profile] = $this->publishedVendor();
        $booking = $this->bookingFor($couple, $wedding, $profile);

        $this->actingAs($couple)->post('/reviews', ['booking_id' => $booking->id, 'rating' => 5]);

        $this->actingAs($couple)
            ->post('/reviews', ['booking_id' => $booking->id, 'rating' => 1])
            ->assertSessionHasErrors('booking_id');

        $this->assertSame(1, Review::count());
    }

    public function test_couple_cannot_review_another_weddings_booking(): void
    {
        [$couple, $wedding] = $this->ownerWithWedding();
        [, $profile] = $this->publishedVendor();
        $this->bookingFor($couple, $wedding, $profile);

        [$otherCouple] = $this->ownerWithWedding();
        $booking = Booking::firstOrFail();

        $this->actingAs($otherCouple)
            ->post('/reviews', ['booking_id' => $booking->id, 'rating' => 5])
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    public function test_vendor_can_respond_to_their_own_review(): void
    {
        [$couple, $wedding] = $this->ownerWithWedding();
        [$vendorUser, $profile] = $this->publishedVendor();
        $booking = $this->bookingFor($couple, $wedding, $profile);

        $review = Review::create([
            'booking_id' => $booking->id,
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $profile->id,
            'couple_user_id' => $couple->id,
            'rating' => 5,
            'body' => 'Wonderful!',
        ]);

        $this->actingAs($vendorUser)
            ->post("/vendor/reviews/{$review->id}/respond", [
                'response' => 'Thank you — it was a pleasure!',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $review->refresh();
        $this->assertSame('Thank you — it was a pleasure!', $review->vendor_response);
        $this->assertNotNull($review->vendor_responded_at);
    }

    public function test_vendor_cannot_respond_to_another_vendors_review(): void
    {
        [$couple, $wedding] = $this->ownerWithWedding();
        [, $profile] = $this->publishedVendor();
        $booking = $this->bookingFor($couple, $wedding, $profile);

        $review = Review::create([
            'booking_id' => $booking->id,
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $profile->id,
            'couple_user_id' => $couple->id,
            'rating' => 5,
        ]);

        [$otherVendorUser] = $this->publishedVendor('Other Florals');

        $this->actingAs($otherVendorUser)
            ->post("/vendor/reviews/{$review->id}/respond", ['response' => 'Not mine.'])
            ->assertForbidden();

        $this->assertNull($review->fresh()->vendor_response);
    }

    public function test_public_vendor_profile_includes_reviews(): void
    {
        [$couple, $wedding] = $this->ownerWithWedding();
        $couple->forceFill(['name' => 'Jane Doe'])->save();
        [, $profile] = $this->publishedVendor();
        $booking = $this->bookingFor($couple, $wedding, $profile);

        Review::create([
            'booking_id' => $booking->id,
            'wedding_id' => $wedding->id,
            'vendor_profile_id' => $profile->id,
            'couple_user_id' => $couple->id,
            'rating' => 5,
            'body' => 'Stunning flowers.',
        ]);
        Review::syncVendorRating($profile->id);

        $this->get("/marketplace/{$profile->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/vendor-profile')
                ->has('profile.reviews', 1)
                ->where('profile.reviews.0.rating', 5)
                ->where('profile.reviews.0.body', 'Stunning flowers.')
                ->where('profile.reviews.0.author', 'Jane D.')
                ->where('profile.rating_count', 1)
            );
    }
}

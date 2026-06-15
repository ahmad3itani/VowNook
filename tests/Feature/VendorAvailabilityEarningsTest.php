<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\VendorAvailability;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorAvailabilityEarningsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: VendorProfile} */
    protected function vendorWithProfile(): array
    {
        $user = User::factory()->create(['account_type' => 'vendor']);

        $profile = VendorProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Golden Hour Photo',
            'category' => 'photography',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => true,
        ]);

        return [$user, $profile];
    }

    public function test_vendor_can_view_availability_calendar(): void
    {
        [$user, $profile] = $this->vendorWithProfile();

        VendorAvailability::create([
            'vendor_profile_id' => $profile->id,
            'date' => now()->toDateString(),
            'status' => 'booked',
        ]);

        $this->actingAs($user)
            ->get('/vendor/availability')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendor/availability')
                ->has('entries', 1)
            );
    }

    public function test_vendor_can_block_and_clear_a_date(): void
    {
        [$user, $profile] = $this->vendorWithProfile();
        $date = now()->addWeek()->toDateString();

        $this->actingAs($user)
            ->post('/vendor/availability', ['date' => $date, 'status' => 'blocked'])
            ->assertRedirect();

        $this->assertDatabaseHas('vendor_availability', [
            'vendor_profile_id' => $profile->id,
            'status' => 'blocked',
        ]);

        $this->actingAs($user)
            ->post('/vendor/availability', ['date' => $date, 'status' => 'available'])
            ->assertRedirect();

        $this->assertDatabaseCount('vendor_availability', 0);
    }

    public function test_vendor_earnings_page_sums_paid_bookings(): void
    {
        [$user, $profile] = $this->vendorWithProfile();

        Booking::factory()->paidInFull()->create([
            'vendor_profile_id' => $profile->id,
            'total_cents' => 100000,
            'deposit_cents' => 20000,
            'platform_fee_cents' => 10000,
        ]);

        Booking::factory()->create([
            'vendor_profile_id' => $profile->id,
            'total_cents' => 50000,
            'deposit_cents' => 0,
            'platform_fee_cents' => 5000,
            'status' => BookingStatus::PendingPayment->value,
        ]);

        $this->actingAs($user)
            ->get('/vendor/earnings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendor/earnings')
                ->where('totals.earned_cents', 90000)
                ->where('totals.pending_cents', 45000)
                ->has('bookings', 2)
            );
    }

    public function test_couple_account_cannot_access_vendor_availability(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/vendor/availability')
            ->assertForbidden();
    }
}

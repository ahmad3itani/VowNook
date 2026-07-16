<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin vendor-gap dashboard is the founder's recruitment priority list:
 * real (non-demo, published, accepting) supply vs. real couple demand, per
 * city × category. Demo listings and non-accepting vendors must never
 * inflate "real supply".
 */
class VendorGapDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/vendor-gaps')->assertForbidden();
    }

    public function test_admin_can_view_the_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/vendor-gaps')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/vendor-gaps'));
    }

    public function test_demo_vendors_are_excluded_from_real_supply(): void
    {
        $demoUser = User::factory()->create(['email' => 'sample@demo.vownook.test', 'account_type' => 'vendor']);
        VendorProfile::factory()->create([
            'user_id' => $demoUser->id,
            'category' => 'photography',
            'city' => 'Toronto',
            'is_accepting_bookings' => true,
        ]);

        $realUser = User::factory()->create(['account_type' => 'vendor']);
        $realVendor = VendorProfile::factory()->create([
            'user_id' => $realUser->id,
            'category' => 'photography',
            'city' => 'Toronto',
            'is_accepting_bookings' => true,
        ]);

        // Give the (toronto, photography) row nonzero demand so it sorts to
        // the very top of the demand-DESC list — otherwise, with almost every
        // other city/category combo also at zero supply, this single-digit
        // supply row could fall outside the top-80 recruitment cap.
        $this->boostDemand('toronto', $realVendor);

        $response = $this->actingAs($this->admin())->get('/admin/vendor-gaps');

        $row = $this->rowFor($response, 'toronto', 'photography');
        $this->assertSame(1, $row['real_supply']);
    }

    public function test_non_accepting_real_vendor_does_not_count_toward_supply(): void
    {
        $realUser = User::factory()->create(['account_type' => 'vendor']);
        $vendor = VendorProfile::factory()->create([
            'user_id' => $realUser->id,
            'category' => 'florist',
            'city' => 'Ottawa',
            'is_accepting_bookings' => false,
        ]);

        $this->boostDemand('ottawa', $vendor);

        $response = $this->actingAs($this->admin())->get('/admin/vendor-gaps');

        $row = $this->rowFor($response, 'ottawa', 'florist');
        $this->assertSame(0, $row['real_supply']);
    }

    /** Create an inquiry so the given (citySlug, vendor.category) row has demand > 0. */
    private function boostDemand(string $citySlug, VendorProfile $vendor): void
    {
        $coupleOwner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $coupleOwner->id, 'city' => $citySlug]);

        Inquiry::factory()->create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $coupleOwner->id,
            'vendor_profile_id' => $vendor->id,
        ]);
    }

    public function test_demand_reflects_inquiries_for_the_matching_city_and_category(): void
    {
        $vendorUser = User::factory()->create(['account_type' => 'vendor']);
        $vendor = VendorProfile::factory()->create([
            'user_id' => $vendorUser->id,
            'category' => 'catering',
            'city' => 'Ottawa',
            'is_accepting_bookings' => true,
        ]);

        $coupleOwner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $coupleOwner->id, 'city' => 'ottawa']);

        Inquiry::factory()->create([
            'wedding_id' => $wedding->id,
            'couple_user_id' => $coupleOwner->id,
            'vendor_profile_id' => $vendor->id,
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/vendor-gaps');

        $row = $this->rowFor($response, 'ottawa', 'catering');
        $this->assertSame(1, $row['demand']);
    }

    private function rowFor($response, string $city, string $category): array
    {
        $rows = $response->viewData('page')['props']['rows'];

        $match = collect($rows)->first(fn ($r) => $r['city'] === $city && $r['category'] === $category);

        $this->assertNotNull($match, "No row found for {$city}/{$category} — it may have fallen outside the top ".count($rows).' rows returned.');

        return $match;
    }
}

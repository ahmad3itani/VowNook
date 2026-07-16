<?php

namespace Tests\Feature;

use App\Enums\VendorProfileStatus;
use App\Models\User;
use App\Models\VendorProfile;
use App\Support\MarketplaceCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vendors who can't take a booking right now sink to the bottom of the
 * marketplace — a couple shouldn't be steered toward someone who can't
 * respond. Ranking within each group (accepting vs. not) is unchanged.
 */
class VendorSinkToBottomTest extends TestCase
{
    use RefreshDatabase;

    protected function vendor(string $name, bool $accepting, array $extra = []): VendorProfile
    {
        $user = User::factory()->create(['account_type' => 'vendor']);

        return VendorProfile::create(array_merge([
            'user_id' => $user->id,
            'business_name' => $name,
            'category' => 'florist',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => $accepting,
        ], $extra));
    }

    public function test_fully_booked_vendors_sink_below_accepting_ones(): void
    {
        // Named so alphabetical order would otherwise put "Ace" first —
        // proving the sink is by availability, not by name.
        $this->vendor('Ace Blooms', accepting: false);
        $this->vendor('Zephyr Florals', accepting: true);

        $results = app(MarketplaceCatalog::class)->browse([]);

        $this->assertSame(['Zephyr Florals', 'Ace Blooms'], $results->pluck('business_name')->all());
    }

    public function test_ordering_within_each_availability_group_is_preserved(): void
    {
        // Among accepting vendors, founding still beats non-founding.
        $this->vendor('Regular Vendor', accepting: true, extra: ['is_founding' => false]);
        $this->vendor('Founding Vendor', accepting: true, extra: ['is_founding' => true]);
        // Among fully-booked vendors, founding still beats non-founding —
        // it's just that the whole fully-booked group sits after accepting ones.
        $this->vendor('Founding But Booked', accepting: false, extra: ['is_founding' => true]);

        $results = app(MarketplaceCatalog::class)->browse([]);

        $this->assertSame(
            ['Founding Vendor', 'Regular Vendor', 'Founding But Booked'],
            $results->pluck('business_name')->all(),
        );
    }

    public function test_demo_sample_listings_sink_below_real_accepting_vendors(): void
    {
        $demoUser = User::factory()->create(['email' => 'sample@demo.vownook.test', 'account_type' => 'vendor']);
        VendorProfile::create([
            'user_id' => $demoUser->id,
            'business_name' => 'Sample Listing Co',
            'category' => 'florist',
            'status' => VendorProfileStatus::Published->value,
            'is_accepting_bookings' => false,
        ]);
        $this->vendor('Real Available Vendor', accepting: true);

        $results = app(MarketplaceCatalog::class)->browse([]);

        $this->assertSame(['Real Available Vendor', 'Sample Listing Co'], $results->pluck('business_name')->all());
    }
}

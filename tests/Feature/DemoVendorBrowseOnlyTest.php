<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `marketplace:demo --browse-only` — fictional vendors must never carry trust
 * badges or accept quote requests (their inboxes are dead).
 */
class DemoVendorBrowseOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_browse_only_strips_badges_and_stops_bookings_for_demo_vendors_only(): void
    {
        $demoUser = User::factory()->create(['email' => 'aperture-oak@demo.vownook.test', 'account_type' => 'vendor']);
        $demo = VendorProfile::factory()->create([
            'user_id' => $demoUser->id,
            'status' => 'published',
            'is_accepting_bookings' => true,
            'is_founding' => true,
            'verified_at' => now(),
        ]);

        $realUser = User::factory()->create(['email' => 'real@vendor.example', 'account_type' => 'vendor']);
        $real = VendorProfile::factory()->create([
            'user_id' => $realUser->id,
            'status' => 'published',
            'is_accepting_bookings' => true,
            'is_founding' => true,
            'verified_at' => now(),
        ]);

        $this->artisan('marketplace:demo', ['--browse-only' => true])
            ->assertSuccessful();

        $demo->refresh();
        $this->assertFalse($demo->is_accepting_bookings);
        $this->assertFalse($demo->is_founding);
        $this->assertNull($demo->verified_at);

        // Real vendors are untouched.
        $real->refresh();
        $this->assertTrue($real->is_accepting_bookings);
        $this->assertTrue($real->is_founding);
        $this->assertNotNull($real->verified_at);
    }
}

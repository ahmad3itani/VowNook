<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
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

    public function test_is_demo_accessor_reflects_the_reserved_email_domain(): void
    {
        $demoUser = User::factory()->create(['email' => 'evergreen-loft@demo.vownook.test', 'account_type' => 'vendor']);
        $demo = VendorProfile::factory()->create(['user_id' => $demoUser->id]);

        $realUser = User::factory()->create(['email' => 'hello@realflorist.ca', 'account_type' => 'vendor']);
        $real = VendorProfile::factory()->create(['user_id' => $realUser->id]);

        $this->assertTrue($demo->load('user')->is_demo);
        $this->assertFalse($real->load('user')->is_demo);
    }

    public function test_a_couple_cannot_inquire_with_a_demo_vendor_even_if_it_is_accepting(): void
    {
        $couple = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $couple->id]);
        $couple->forceFill(['current_wedding_id' => $wedding->id])->save();

        // A sample listing wrongly left in an accepting state must still be
        // uncontactable — the endpoint excludes demo vendors outright.
        $demoUser = User::factory()->create(['email' => 'the-foundry@demo.vownook.test', 'account_type' => 'vendor']);
        $demo = VendorProfile::factory()->create([
            'user_id' => $demoUser->id,
            'status' => 'published',
            'is_accepting_bookings' => true,
        ]);

        $this->actingAs($couple)
            ->post('/vendors/quotes', [
                'vendor_profile_id' => $demo->id,
                'message' => 'Are you free for our wedding?',
            ])
            ->assertNotFound();

        $this->assertSame(0, Inquiry::count());
    }
}

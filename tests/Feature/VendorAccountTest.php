<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\VendorProfileStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_as_a_vendor_creates_a_draft_profile(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Bloom & Petal',
            'email' => 'vendor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'account_type' => 'vendor',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'vendor@example.com')->firstOrFail();
        $this->assertSame(AccountType::Vendor, $user->account_type);
        $this->assertTrue($user->isVendor());

        $profile = $user->vendorProfile;
        $this->assertNotNull($profile);
        $this->assertSame('Bloom & Petal', $profile->business_name);
        $this->assertNotEmpty($profile->slug);
        $this->assertSame(VendorProfileStatus::Draft, $profile->status);
    }

    public function test_registering_as_a_couple_creates_no_profile(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Happy Couple',
            'email' => 'couple@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'account_type' => 'couple',
        ]);

        $user = User::where('email', 'couple@example.com')->firstOrFail();
        $this->assertSame(AccountType::Couple, $user->account_type);
        $this->assertNull($user->vendorProfile);
    }

    public function test_account_type_defaults_to_couple_when_omitted(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'default@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'default@example.com')->firstOrFail();
        $this->assertSame(AccountType::Couple, $user->account_type);
    }

    public function test_vendor_is_redirected_from_dashboard_to_vendor_dashboard(): void
    {
        $user = User::factory()->create(['account_type' => AccountType::Vendor->value]);
        VendorProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Test Vendor',
            'category' => 'florist',
            'status' => VendorProfileStatus::Draft->value,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('vendor.dashboard'));
    }

    public function test_vendor_dashboard_renders_for_a_vendor(): void
    {
        $user = User::factory()->create(['account_type' => AccountType::Vendor->value]);
        VendorProfile::create([
            'user_id' => $user->id,
            'business_name' => 'Test Vendor',
            'category' => 'florist',
            'status' => VendorProfileStatus::Draft->value,
        ]);

        $this->actingAs($user)
            ->get('/vendor')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendor/dashboard')
                ->where('profile.business_name', 'Test Vendor')
                ->where('profile.is_published', false)
            );
    }

    public function test_couple_account_is_redirected_away_from_vendor_dashboard(): void
    {
        $user = User::factory()->create(['account_type' => AccountType::Couple->value]);

        $this->actingAs($user)
            ->get('/vendor')
            ->assertRedirect(route('dashboard'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_view_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
    }

    public function test_admin_can_view_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/settings'));
    }

    public function test_admin_can_update_branding_and_it_persists(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/settings', [
            'app_name' => 'My Studio',
            'brand_primary' => '#112233',
            'brand_tagline' => 'Made with love',
        ])->assertRedirect();

        $this->assertSame('My Studio', Setting::get('app_name'));
        $this->assertSame('#112233', Setting::get('brand_primary'));
    }

    public function test_invalid_color_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/settings', [
            'brand_primary' => 'not-a-color',
        ])->assertSessionHasErrors('brand_primary');
    }
}

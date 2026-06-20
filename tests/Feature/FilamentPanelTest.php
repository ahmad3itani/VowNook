<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_the_manage_panel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/manage')->assertOk();
    }

    public function test_non_admin_cannot_access_the_manage_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/manage')->assertForbidden();
    }

    public function test_suspended_admin_cannot_access_the_manage_panel(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'suspended_at' => now()]);

        // The panel's own access gate (canAccessPanel) blocks a suspended admin.
        $this->actingAs($admin)->get('/manage')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/manage')->assertRedirect();
    }
}

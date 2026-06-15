<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    protected function coupleWedding(): Wedding
    {
        $owner = User::factory()->create();

        return Wedding::factory()->create(['owner_id' => $owner->id]);
    }

    public function test_admin_can_enter_support_and_use_couple_sections(): void
    {
        $admin = $this->admin();
        $wedding = $this->coupleWedding();

        // Before entering support, the admin has no active wedding.
        $this->actingAs($admin)->get('/checklist')->assertForbidden();

        // Enter support for this wedding.
        $this->actingAs($admin)
            ->post("/admin/weddings/{$wedding->slug}/support")
            ->assertRedirect('/dashboard');

        // Now every couple section resolves with full (admin) access.
        $this->actingAs($admin)->get('/checklist')->assertOk();

        $this->actingAs($admin)->post('/checklist', [
            'title' => 'Support-added task',
            'category' => 'planning',
            'priority' => 'medium',
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'wedding_id' => $wedding->id,
            'title' => 'Support-added task',
        ]);

        // Exiting clears the context and locks the sections again.
        $this->actingAs($admin)
            ->post('/admin/support/exit')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->get('/checklist')->assertForbidden();
    }

    public function test_admin_in_support_lands_on_couple_dashboard(): void
    {
        $admin = $this->admin();
        $wedding = $this->coupleWedding();

        // Without support, the admin is bounced to the console.
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        // In support mode, the couple dashboard renders.
        $this->withSession(['support_wedding_id' => $wedding->id])
            ->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('dashboard'));
    }

    public function test_non_admin_cannot_enter_support_or_reach_console(): void
    {
        $couple = User::factory()->create();
        Wedding::factory()->create(['owner_id' => $couple->id]);
        $target = $this->coupleWedding();

        $this->actingAs($couple)->post("/admin/weddings/{$target->slug}/support")->assertForbidden();

        foreach (['/admin/dashboard', '/admin/weddings', '/admin/users', '/admin/marketplace'] as $url) {
            $this->actingAs($couple)->get($url)->assertForbidden();
        }
    }

    public function test_admin_console_pages_render(): void
    {
        $admin = $this->admin();
        $this->coupleWedding();

        $cases = [
            '/admin/dashboard' => 'admin/dashboard',
            '/admin/weddings' => 'admin/weddings',
            '/admin/users' => 'admin/users',
            '/admin/marketplace' => 'admin/marketplace',
        ];

        foreach ($cases as $url => $component) {
            $this->actingAs($admin)->get($url)
                ->assertOk()
                ->assertInertia(fn ($p) => $p->component($component));
        }
    }

    public function test_admin_can_open_wedding_detail_and_change_user_plan(): void
    {
        $admin = $this->admin();
        $wedding = $this->coupleWedding();
        $owner = $wedding->owner;

        $this->actingAs($admin)->get("/admin/weddings/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('admin/wedding-show'));

        $this->actingAs($admin)->put("/admin/users/{$owner->id}/plan", ['plan' => 'premium'])
            ->assertRedirect();

        $this->assertSame('premium', $owner->fresh()->plan);
    }
}

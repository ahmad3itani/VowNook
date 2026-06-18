<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminOversightTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_login_event_records_last_login_and_activity(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        event(new Login('web', $user, false));

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_admin_can_impersonate_a_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertRedirect('/dashboard');

        $this->assertEquals($admin->id, session('impersonator_id'));
        $this->assertAuthenticatedAs($target);
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'admin.impersonate.start',
            'subject_id' => $target->id,
        ]);
    }

    public function test_admins_cannot_be_impersonated(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$other->id}/impersonate")
            ->assertForbidden();
    }

    public function test_non_admin_cannot_impersonate(): void
    {
        $couple = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($couple)
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertForbidden();
    }

    public function test_stop_impersonation_restores_admin(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->withSession(['impersonator_id' => $admin->id])
            ->actingAs($target)
            ->post('/impersonate/stop')
            ->assertRedirect("/admin/users/{$target->id}");

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_suspended_user_is_signed_out_and_blocked(): void
    {
        $couple = User::factory()->create();
        Wedding::factory()->create(['owner_id' => $couple->id]);
        $admin = $this->admin();

        // Admin suspends the account.
        $this->actingAs($admin)
            ->post("/admin/users/{$couple->id}/suspend", ['reason' => 'Spam'])
            ->assertRedirect();

        $couple->refresh();
        $this->assertNotNull($couple->suspended_at);

        // The suspended user is bounced to the suspended page and logged out.
        // (Production re-resolves the user from the DB each request.)
        $this->actingAs($couple)->get('/dashboard')->assertRedirect('/suspended');
        $this->assertGuest();

        // Reinstating restores access.
        $this->actingAs($admin)->post("/admin/users/{$couple->id}/unsuspend")->assertRedirect();
        $this->assertNull($couple->fresh()->suspended_at);
    }

    public function test_admins_cannot_be_suspended(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/admin/users/{$other->id}/suspend")->assertForbidden();
    }

    public function test_user_detail_page_renders(): void
    {
        $admin = $this->admin();
        $couple = User::factory()->create();
        Wedding::factory()->create(['owner_id' => $couple->id]);

        $this->actingAs($admin)->get("/admin/users/{$couple->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('admin/user-show')
                ->where('subject.id', $couple->id)
                ->where('can_impersonate', true));
    }

    public function test_admin_can_comp_a_plan(): void
    {
        $admin = $this->admin();
        $couple = User::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$couple->id}/comp", ['plan' => 'premium', 'days' => 30])
            ->assertRedirect();

        $couple->refresh();
        $this->assertSame('premium', $couple->plan);
        $this->assertNotNull($couple->plan_comped_until);
    }

    public function test_admin_can_send_password_reset_and_resend_verification(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $unverified = User::factory()->unverified()->create();

        $this->actingAs($admin)->post("/admin/users/{$unverified->id}/password-reset")->assertRedirect();
        $this->actingAs($admin)->post("/admin/users/{$unverified->id}/resend-verification")->assertRedirect();

        $this->assertDatabaseHas('activity_logs', ['action' => 'admin.user.resend_verification', 'subject_id' => $unverified->id]);
    }

    public function test_activity_feed_renders_for_admin_only(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/activity')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('admin/activity'));

        $this->actingAs(User::factory()->create())->get('/admin/activity')->assertForbidden();
    }
}

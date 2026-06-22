<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketReceived;
use App\Notifications\SupportTicketReplied;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_opens_a_ticket_and_notifies_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $this->post('/contact', [
            'name' => 'Avery Lane',
            'email' => 'avery@example.com',
            'topic' => 'vendor',
            'message' => 'How fast is moderation?',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'email' => 'avery@example.com',
            'source' => 'contact',
            'category' => 'vendor',
        ]);

        Notification::assertSentTo($admin, SupportTicketReceived::class);
    }

    public function test_user_can_open_an_in_app_ticket(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $couple = User::factory()->create();

        $this->actingAs($couple)->post('/support', [
            'subject' => 'Cannot publish my website',
            'category' => 'technical',
            'message' => 'The publish button does nothing.',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $couple->id,
            'subject' => 'Cannot publish my website',
            'source' => 'in_app',
        ]);

        Notification::assertSentTo($admin, SupportTicketReceived::class);
    }

    /** Regression: a mail-transport failure must not 500 the support form. */
    public function test_ticket_is_still_saved_when_the_admin_alert_fails(): void
    {
        User::factory()->admin()->create();
        $couple = User::factory()->create();

        // Reproduce the production failure: notifying admins throws (bad SMTP key).
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('send', 'sendNow')->andThrow(new \RuntimeException('mail transport down'));
        $this->app->instance(Dispatcher::class, $dispatcher);

        $this->actingAs($couple)->post('/support', [
            'subject' => 'Billing question',
            'category' => 'billing',
            'message' => 'When am I charged?',
        ])->assertRedirect(); // not a 500

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $couple->id,
            'subject' => 'Billing question',
        ]);
    }

    public function test_user_cannot_view_another_users_ticket(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $ticket = SupportTicket::create([
            'user_id' => $a->id,
            'name' => $a->name,
            'email' => $a->email,
            'subject' => 'Private',
            'category' => 'general',
            'message' => 'Hello',
            'source' => 'in_app',
        ]);

        $this->actingAs($b)->get("/support/{$ticket->id}")->assertForbidden();
        $this->actingAs($a)->get("/support/{$ticket->id}")->assertOk();
    }

    public function test_admin_reply_notifies_the_requester_and_marks_pending(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $couple = User::factory()->create();

        $ticket = SupportTicket::create([
            'user_id' => $couple->id,
            'name' => $couple->name,
            'email' => $couple->email,
            'subject' => 'Help',
            'category' => 'general',
            'message' => 'Need help',
            'source' => 'in_app',
        ]);

        $this->actingAs($admin)
            ->post("/admin/support/{$ticket->id}/reply", ['body' => 'Here is how…'])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'is_staff' => true,
        ]);
        $this->assertSame('pending', $ticket->fresh()->status->value);

        Notification::assertSentTo($couple, SupportTicketReplied::class);
    }

    public function test_admin_can_close_a_ticket(): void
    {
        $admin = User::factory()->admin()->create();

        $ticket = SupportTicket::create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'subject' => 'Q',
            'category' => 'general',
            'message' => 'Question',
            'source' => 'contact',
        ]);

        $this->actingAs($admin)
            ->put("/admin/support/{$ticket->id}/status", ['status' => 'closed'])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame('closed', $ticket->status->value);
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_support_inbox_is_admin_only(): void
    {
        $couple = User::factory()->create();

        $this->actingAs($couple)->get('/admin/support')->assertForbidden();
    }
}

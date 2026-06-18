<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SupportTicketReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
        $this->get('/contact')->assertOk();
    }

    public function test_contact_form_notifies_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->post('/contact', [
            'name' => 'Avery Lane',
            'email' => 'avery@example.com',
            'topic' => 'vendor',
            'message' => 'How fast is moderation?',
        ])->assertRedirect();

        Notification::assertSentTo($admin, SupportTicketReceived::class);
    }

    public function test_contact_form_validates_input(): void
    {
        $this->from('/contact')->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'topic' => 'bogus',
            'message' => '',
        ])->assertSessionHasErrors(['name', 'email', 'topic', 'message']);
    }
}

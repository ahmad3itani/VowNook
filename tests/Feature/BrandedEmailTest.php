<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_email_renders_with_the_branded_theme(): void
    {
        $user = User::factory()->create(['name' => 'Amelia']);

        // Rendering exercises the custom mail theme CSS + header/footer blades;
        // a malformed theme or template would throw here.
        $html = (new WelcomeNotification())->toMail($user)->render();

        $this->assertStringContainsString('VowNook', $html);
        $this->assertStringContainsString('Start planning', $html); // the action button
        $this->assertStringContainsString('vendor marketplace', $html); // header tagline
    }
}

<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => 'on',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertNotNull(\App\Models\User::where('email', 'test@example.com')->value('terms_accepted_at'));
        $response->assertSessionHas('conversion.ga', 'sign_up');
    }

    public function test_signup_conversion_survives_the_redirect_chain_to_the_final_page()
    {
        // Unverified users are bounced register → dashboard → email/verify. The
        // conversion flash must survive both hops so the client tracker fires it.
        $response = $this->followingRedirects()->post(route('register.store'), [
            'name' => 'Chain User',
            'email' => 'chain@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => 'on',
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('conversion.ga', 'sign_up'));
    }

    public function test_registration_requires_accepting_the_terms()
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'No Terms',
            'email' => 'noterms@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'noterms@example.com']);
    }
}

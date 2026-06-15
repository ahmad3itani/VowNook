<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalisationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_localisation_editor(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/localisation?locale=fr')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/localisation')
                ->where('active', 'fr')
                ->has('strings')
                ->has('locales')
            );
    }

    public function test_non_admin_cannot_access_localisation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/localisation')->assertForbidden();
    }

    public function test_admin_can_save_a_translation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/localisation', [
            'locale' => 'fr',
            'strings' => ['public.footer' => 'Réalisé avec amour'],
        ])->assertRedirect();

        $this->assertDatabaseHas('translations', [
            'locale' => 'fr',
            'key' => 'public.footer',
            'value' => 'Réalisé avec amour',
        ]);
    }

    public function test_clearing_a_string_removes_the_override(): void
    {
        $admin = User::factory()->admin()->create();
        Translation::put('fr', 'public.footer', 'Existing');

        $this->actingAs($admin)->put('/admin/localisation', [
            'locale' => 'fr',
            'strings' => ['public.footer' => ''],
        ])->assertRedirect();

        $this->assertDatabaseMissing('translations', [
            'locale' => 'fr',
            'key' => 'public.footer',
        ]);
    }

    public function test_unknown_keys_are_ignored(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/localisation', [
            'locale' => 'fr',
            'strings' => ['evil.key' => 'nope'],
        ])->assertRedirect();

        $this->assertDatabaseMissing('translations', ['key' => 'evil.key']);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/localisation', [
            'locale' => 'zz',
            'strings' => ['public.footer' => 'x'],
        ])->assertSessionHasErrors('locale');
    }

    public function test_active_locale_translations_are_shared_to_inertia(): void
    {
        $user = User::factory()->create();
        Setting::put('app_locale', 'fr');
        Translation::put('fr', 'public.footer', 'Réalisé avec VowNook');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('locale', 'fr')
                ->where('translations', fn ($translations) => collect($translations)->get('public.footer')
                    === 'Réalisé avec VowNook')
            );
    }
}

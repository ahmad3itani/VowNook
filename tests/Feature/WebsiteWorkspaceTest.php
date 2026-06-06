<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_editor_renders(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)
            ->get('/website')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('website/index')
                ->where('website.is_published', false)
            );
    }

    public function test_update_creates_or_updates_the_website(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->put('/website', [
            'is_published' => true,
            'headline' => 'We are getting married',
            'venue_name' => 'Rosewood Estate',
            'hero_image_url' => 'https://example.com/hero.jpg',
        ])->assertRedirect();

        $this->assertDatabaseHas('wedding_websites', [
            'wedding_id' => $wedding->id,
            'is_published' => true,
            'venue_name' => 'Rosewood Estate',
        ]);
    }

    public function test_invalid_hero_image_url_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->put('/website', [
            'hero_image_url' => 'not-a-url',
        ])->assertSessionHasErrors('hero_image_url');
    }

    public function test_viewer_can_read_but_not_update(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        WeddingWebsite::factory()->create(['wedding_id' => $wedding->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        // Viewers have read access to the website section, but cannot edit.
        $this->actingAs($viewer)->get('/website')->assertOk();
        $this->actingAs($viewer)->put('/website', [
            'headline' => 'Nope',
        ])->assertForbidden();
    }
}

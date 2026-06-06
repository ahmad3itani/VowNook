<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\InspirationItem;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspirationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        InspirationItem::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        InspirationItem::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/inspiration')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inspiration/index')
                ->has('items', 2)
            );
    }

    public function test_member_can_create_an_item(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/inspiration', [
            'title' => 'Blush peonies',
            'category' => 'flowers',
            'image_url' => 'https://example.com/peonies.jpg',
            'link_url' => 'https://example.com/source',
        ])->assertRedirect();

        $this->assertDatabaseHas('inspiration_items', [
            'wedding_id' => $wedding->id,
            'title' => 'Blush peonies',
            'category' => 'flowers',
        ]);
    }

    public function test_invalid_image_url_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/inspiration', [
            'title' => 'Bad',
            'category' => 'decor',
            'image_url' => 'not-a-url',
        ])->assertSessionHasErrors('image_url');
    }

    public function test_invalid_category_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/inspiration', [
            'title' => 'Bad',
            'category' => 'spaceship',
        ])->assertSessionHasErrors('category');
    }

    public function test_viewer_cannot_create_an_item(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/inspiration', [
            'title' => 'Nope',
            'category' => 'decor',
        ])->assertForbidden();
    }

    public function test_cannot_update_an_item_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = InspirationItem::factory()->create();

        $this->actingAs($user)->put("/inspiration/{$foreign->id}", [
            'title' => 'Hijack',
            'category' => 'decor',
        ])->assertNotFound();
    }

    public function test_member_can_delete_an_item(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $item = InspirationItem::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->delete("/inspiration/{$item->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('inspiration_items', ['id' => $item->id]);
    }
}

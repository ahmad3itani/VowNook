<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryWorkspaceTest extends TestCase
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
        GalleryPhoto::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        GalleryPhoto::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/gallery')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gallery/index')
                ->has('photos', 2)
            );
    }

    public function test_member_can_upload_photos(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery', [
            'photos' => [
                UploadedFile::fake()->image('first-dance.jpg', 800, 600),
                UploadedFile::fake()->image('cake.jpg', 800, 600),
            ],
        ])->assertRedirect();

        $this->assertSame(2, GalleryPhoto::where('wedding_id', $wedding->id)->count());
        $photo = GalleryPhoto::where('original_name', 'first-dance.jpg')->firstOrFail();
        $this->assertSame($wedding->id, $photo->wedding_id);
        Storage::assertExists($photo->path);
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        Storage::fake();
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery', [
            'photos' => [UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('photos.0');
    }

    public function test_viewer_cannot_upload(): void
    {
        Storage::fake();
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/gallery', [
            'photos' => [UploadedFile::fake()->image('nope.jpg')],
        ])->assertForbidden();
    }

    public function test_caption_can_be_updated(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $photo = GalleryPhoto::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->put("/gallery/{$photo->id}", ['caption' => 'Golden hour'])
            ->assertRedirect();

        $this->assertSame('Golden hour', $photo->fresh()->caption);
    }

    public function test_photos_can_be_reordered(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $a = GalleryPhoto::factory()->create(['wedding_id' => $wedding->id, 'sort_order' => 0]);
        $b = GalleryPhoto::factory()->create(['wedding_id' => $wedding->id, 'sort_order' => 1]);

        $this->actingAs($user)->post('/gallery/reorder', [
            'items' => [
                ['id' => $a->id, 'sort_order' => 1],
                ['id' => $b->id, 'sort_order' => 0],
            ],
        ])->assertRedirect();

        $this->assertSame(1, $a->fresh()->sort_order);
        $this->assertSame(0, $b->fresh()->sort_order);
    }

    public function test_reorder_ignores_photos_from_other_weddings(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = GalleryPhoto::factory()->create(['sort_order' => 5]);

        $this->actingAs($user)->post('/gallery/reorder', [
            'items' => [['id' => $foreign->id, 'sort_order' => 99]],
        ])->assertRedirect();

        $this->assertSame(5, $foreign->fresh()->sort_order);
    }

    public function test_bulk_delete_removes_only_own_photos(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();
        $mine = GalleryPhoto::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        $foreign = GalleryPhoto::factory()->create();

        $this->actingAs($user)->post('/gallery/bulk-delete', [
            'ids' => [$mine[0]->id, $mine[1]->id, $foreign->id],
        ])->assertRedirect();

        $this->assertDatabaseMissing('gallery_photos', ['id' => $mine[0]->id]);
        $this->assertDatabaseMissing('gallery_photos', ['id' => $mine[1]->id]);
        $this->assertDatabaseHas('gallery_photos', ['id' => $foreign->id]);
    }

    public function test_albums_can_be_created_renamed_and_deleted(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery/albums', ['name' => 'Engagement'])->assertRedirect();
        $album = GalleryAlbum::firstOrFail();
        $this->assertSame($wedding->id, $album->wedding_id);
        $this->assertSame('Engagement', $album->name);

        $this->actingAs($user)->put("/gallery/albums/{$album->id}", ['name' => 'Engagement Shoot'])->assertRedirect();
        $this->assertSame('Engagement Shoot', $album->fresh()->name);

        $this->actingAs($user)->delete("/gallery/albums/{$album->id}")->assertRedirect();
        $this->assertDatabaseMissing('gallery_albums', ['id' => $album->id]);
    }

    public function test_deleting_an_album_unsorts_its_photos(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $album = GalleryAlbum::create(['wedding_id' => $wedding->id, 'name' => 'A', 'sort_order' => 1]);
        $photo = GalleryPhoto::factory()->create(['wedding_id' => $wedding->id, 'album_id' => $album->id]);

        $this->actingAs($user)->delete("/gallery/albums/{$album->id}")->assertRedirect();

        $this->assertNull($photo->fresh()->album_id);
        $this->assertDatabaseHas('gallery_photos', ['id' => $photo->id]);
    }

    public function test_photos_can_be_moved_between_albums(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $album = GalleryAlbum::create(['wedding_id' => $wedding->id, 'name' => 'A', 'sort_order' => 1]);
        $a = GalleryPhoto::factory()->create(['wedding_id' => $wedding->id]);
        $b = GalleryPhoto::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->post('/gallery/move', [
            'ids' => [$a->id, $b->id],
            'album_id' => $album->id,
        ])->assertRedirect();

        $this->assertSame($album->id, $a->fresh()->album_id);
        $this->assertSame($album->id, $b->fresh()->album_id);

        $this->actingAs($user)->post('/gallery/move', [
            'ids' => [$a->id],
            'album_id' => null,
        ])->assertRedirect();

        $this->assertNull($a->fresh()->album_id);
    }

    public function test_index_filters_photos_by_album(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $album = GalleryAlbum::create(['wedding_id' => $wedding->id, 'name' => 'A', 'sort_order' => 1]);
        GalleryPhoto::factory()->create(['wedding_id' => $wedding->id, 'album_id' => $album->id]);
        GalleryPhoto::factory()->count(2)->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->get("/gallery?album={$album->id}")
            ->assertInertia(fn ($page) => $page->component('gallery/index')->has('photos', 1)->has('albums', 1));

        $this->actingAs($user)->get('/gallery?album=unsorted')
            ->assertInertia(fn ($page) => $page->has('photos', 2));

        $this->actingAs($user)->get('/gallery')
            ->assertInertia(fn ($page) => $page->has('photos', 3));
    }

    public function test_cannot_modify_another_weddings_album(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreignWedding = Wedding::factory()->create();
        $foreign = GalleryAlbum::create(['wedding_id' => $foreignWedding->id, 'name' => 'X', 'sort_order' => 1]);

        $this->actingAs($user)->put("/gallery/albums/{$foreign->id}", ['name' => 'hacked'])->assertNotFound();
        $this->actingAs($user)->delete("/gallery/albums/{$foreign->id}")->assertNotFound();
    }

    public function test_set_as_cover_copies_photo_to_website_hero(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery', [
            'photos' => [UploadedFile::fake()->image('hero.jpg', 1200, 800)],
        ]);
        $photo = GalleryPhoto::firstOrFail();

        $this->actingAs($user)->post("/gallery/{$photo->id}/cover")->assertRedirect();

        $hero = $wedding->website()->first()?->hero_image_path;
        $this->assertNotNull($hero);
        $this->assertStringContainsString("websites/{$wedding->id}/hero/", $hero);
        Storage::assertExists($hero);
    }

    public function test_download_all_returns_a_zip(): void
    {
        Storage::fake();
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery', [
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]);

        $this->actingAs($user)->get('/gallery/download')->assertDownload('vownook-gallery.zip');
    }

    public function test_download_all_redirects_when_empty(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->get('/gallery/download')->assertRedirect();
    }

    public function test_file_route_is_scoped_to_the_active_wedding(): void
    {
        Storage::fake();
        [$user] = $this->ownerWithWedding();
        $foreign = GalleryPhoto::factory()->create();

        $this->actingAs($user)
            ->get("/gallery/{$foreign->id}/file")
            ->assertNotFound();
    }

    public function test_deleting_a_photo_removes_the_file(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery', [
            'photos' => [UploadedFile::fake()->image('shot.jpg')],
        ]);

        $photo = GalleryPhoto::firstOrFail();
        Storage::assertExists($photo->path);

        $this->actingAs($user)
            ->delete("/gallery/{$photo->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('gallery_photos', ['id' => $photo->id]);
        Storage::assertMissing($photo->path);
    }
}

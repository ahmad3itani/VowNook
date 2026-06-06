<?php

namespace Tests\Feature;

use App\Enums\Role;
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

    public function test_member_can_upload_a_photo(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery', [
            'photo' => UploadedFile::fake()->image('first-dance.jpg', 800, 600),
            'caption' => 'Our first dance',
        ])->assertRedirect();

        $photo = GalleryPhoto::firstOrFail();
        $this->assertSame($wedding->id, $photo->wedding_id);
        $this->assertSame('first-dance.jpg', $photo->original_name);
        $this->assertSame('Our first dance', $photo->caption);
        Storage::assertExists($photo->path);
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        Storage::fake();
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/gallery', [
            'photo' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
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
            'photo' => UploadedFile::fake()->image('nope.jpg'),
        ])->assertForbidden();
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
            'photo' => UploadedFile::fake()->image('shot.jpg'),
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

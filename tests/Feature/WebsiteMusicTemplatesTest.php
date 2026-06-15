<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteMusicTemplatesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    // ── Music upload ─────────────────────────────────────────────────────────

    public function test_couple_can_upload_background_music(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/website/music', [
            'music' => UploadedFile::fake()->create('our-song.mp3', 200, 'audio/mpeg'),
        ])->assertRedirect();

        $website = $wedding->fresh()->website;
        $this->assertNotNull($website->music_path);
        $this->assertStringStartsWith("websites/{$wedding->id}/music/", $website->music_path);
        $this->assertSame('our-song', $website->music_title);
        Storage::assertExists($website->music_path);
    }

    public function test_a_non_audio_file_is_rejected(): void
    {
        Storage::fake();
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/website/music', [
            'music' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('music');
    }

    public function test_remove_music_clears_columns_and_deletes_file(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/website/music', [
            'music' => UploadedFile::fake()->create('song.mp3', 100, 'audio/mpeg'),
        ])->assertRedirect();

        $path = $wedding->fresh()->website->music_path;
        Storage::assertExists($path);

        $this->actingAs($user)->delete('/website/music')->assertRedirect();

        $website = $wedding->fresh()->website;
        $this->assertNull($website->music_path);
        $this->assertNull($website->music_title);
        Storage::assertMissing($path);
    }

    public function test_non_writer_cannot_upload_music(): void
    {
        Storage::fake();
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => \App\Enums\Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/website/music', [
            'music' => UploadedFile::fake()->create('song.mp3', 100, 'audio/mpeg'),
        ])->assertForbidden();
    }

    // ── Media serving ────────────────────────────────────────────────────────

    public function test_media_route_serves_uploaded_music(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/website/music', [
            'music' => UploadedFile::fake()->create('song.mp3', 100, 'audio/mpeg'),
        ])->assertRedirect();

        $filename = basename($wedding->fresh()->website->music_path);

        $this->get("/w/{$wedding->slug}/media/music/{$filename}")->assertOk();
    }

    public function test_unknown_media_type_is_not_found(): void
    {
        [, $wedding] = $this->ownerWithWedding();

        $this->get("/w/{$wedding->slug}/media/secrets/anything.mp3")->assertNotFound();
    }

    // ── Templates ─────────────────────────────────────────────────────────────

    public function test_a_new_template_is_accepted(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->put('/website', ['template' => 'royal'])->assertRedirect();

        $this->assertSame('royal', $wedding->fresh()->website->template);
    }

    public function test_an_invalid_template_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->put('/website', ['template' => 'neon'])
            ->assertSessionHasErrors('template');
    }

    // ── Public page exposes music ──────────────────────────────────────────────

    public function test_public_page_exposes_music_for_a_new_template(): void
    {
        Storage::fake();
        [$user, $wedding] = $this->ownerWithWedding();

        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id,
            'is_published' => true,
            'template' => 'dolce',
        ]);

        $this->actingAs($user)->post('/website/music', [
            'music' => UploadedFile::fake()->create('first-dance.mp3', 150, 'audio/mpeg'),
        ])->assertRedirect();

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/website')
                ->where('content.template', 'dolce')
                ->where('content.music_title', 'first-dance')
                ->whereNot('content.music_url', null));
    }
}

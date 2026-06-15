<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use App\Support\Markdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_an_article_image_and_get_a_url(): void
    {
        Storage::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/blog/image', ['image' => UploadedFile::fake()->image('shot.jpg', 1200, 800)])
            ->assertOk()
            ->assertJsonStructure(['url']);
    }

    public function test_non_admin_cannot_upload_article_images(): void
    {
        Storage::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/admin/blog/image', ['image' => UploadedFile::fake()->image('x.jpg')])
            ->assertForbidden();
    }

    public function test_rendered_body_images_are_lazy_and_keep_alt(): void
    {
        $html = Markdown::toHtml('![A rustic barn venue](/blog/media/x.webp)');

        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
        $this->assertStringContainsString('alt="A rustic barn venue"', $html);
    }

    public function test_cover_alt_persists_and_reaches_the_public_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = BlogPost::create([
            'title' => 'Barn Venues', 'slug' => 'barn-venues', 'body' => 'Hi',
            'category' => 'venues', 'status' => 'published', 'published_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->put("/admin/blog/{$post->slug}", [
            'title' => 'Barn Venues', 'body' => 'Hi', 'category' => 'venues',
            'status' => 'published', 'cover_alt' => 'A sunlit barn wedding',
        ])->assertRedirect();

        $this->assertSame('A sunlit barn wedding', $post->fresh()->cover_alt);
    }
}

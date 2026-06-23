<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use App\Support\Blog\BlogTopics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BlogAutopilotTest extends TestCase
{
    use RefreshDatabase;

    private function enableAi(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'anthropic',
            'ai.anthropic.key' => 'test-key',
            'ai.anthropic.base_url' => 'https://api.anthropic.com',
            'ai.anthropic.version' => '2023-06-01',
            'ai.openrouter.key' => null,
            'ai.model' => 'claude-sonnet-4-6',
            'ai.blog_autopilot.enabled' => true,
            'ai.blog_autopilot.per_run' => 1,
            'ai.blog_autopilot.min_words' => 20,
        ]);
    }

    private function fakeArticle(?string $body = null): void
    {
        $body = $body ?? str_repeat('This is a useful sentence about planning an Ontario wedding. ', 20);

        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'write_article', 'input' => [
                'title' => 'A Generated Ontario Wedding Article',
                'excerpt' => 'A short summary of the article.',
                'body_markdown' => $body,
                'meta_description' => 'A search-friendly meta description.',
            ]]],
            'stop_reason' => 'tool_use',
        ])]);
    }

    /** @return array{slug:string, title:string, category:string, brief:string} */
    private function topic(int $i): array
    {
        return BlogTopics::all()[$i];
    }

    public function test_it_publishes_an_article_when_enabled(): void
    {
        $this->enableAi();
        $this->fakeArticle();

        $this->artisan('blog:autopilot')->assertSuccessful();

        $this->assertDatabaseHas('blog_posts', [
            'slug' => $this->topic(0)['slug'],
            'status' => 'published',
            'author_name' => 'VowNook',
            'category' => $this->topic(0)['category'],
        ]);
        $this->assertNotNull(BlogPost::where('slug', $this->topic(0)['slug'])->first()->published_at);
    }

    public function test_it_skips_when_disabled(): void
    {
        $this->enableAi();
        config(['ai.blog_autopilot.enabled' => false]);
        Http::fake();

        $this->artisan('blog:autopilot')->assertSuccessful();

        $this->assertSame(0, BlogPost::count());
        Http::assertNothingSent();
    }

    public function test_force_runs_even_when_disabled(): void
    {
        $this->enableAi();
        config(['ai.blog_autopilot.enabled' => false]);
        $this->fakeArticle();

        $this->artisan('blog:autopilot', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, BlogPost::count());
    }

    public function test_it_dedupes_existing_topics(): void
    {
        $this->enableAi();
        $this->fakeArticle();

        // The first queue topic is already published — autopilot must skip it
        // and publish the next one instead, never duplicating.
        $first = $this->topic(0);
        BlogPost::create([
            'slug' => $first['slug'],
            'title' => 'Already here',
            'body' => 'Existing body.',
            'category' => $first['category'],
            'author_name' => 'VowNook',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->artisan('blog:autopilot')->assertSuccessful();

        $this->assertDatabaseHas('blog_posts', ['slug' => $this->topic(1)['slug']]);
        $this->assertSame(1, BlogPost::where('slug', $first['slug'])->count());
    }

    public function test_it_rejects_thin_content(): void
    {
        $this->enableAi();
        config(['ai.blog_autopilot.min_words' => 100]);
        $this->fakeArticle('Too short.');

        $this->artisan('blog:autopilot')->assertSuccessful();

        $this->assertSame(0, BlogPost::count());
    }

    public function test_it_degrades_when_ai_not_configured(): void
    {
        config([
            'ai.enabled' => true,
            'ai.anthropic.key' => null,
            'ai.openrouter.key' => null,
            'ai.blog_autopilot.enabled' => true,
        ]);
        Http::fake();

        $this->artisan('blog:autopilot')->assertSuccessful();

        $this->assertSame(0, BlogPost::count());
        Http::assertNothingSent();
    }

    public function test_admin_can_reach_the_autopilot_route(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'account_type' => 'couple']);

        $this->actingAs($admin)->post('/admin/blog/autopilot')->assertRedirect();
    }

    public function test_non_admin_cannot_reach_the_autopilot_route(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'account_type' => 'couple']);

        $this->actingAs($user)->post('/admin/blog/autopilot')->assertForbidden();
    }
}

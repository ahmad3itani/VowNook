<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function makePost(array $attrs = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'title' => 'A Lovely Ontario Wedding',
            'slug' => 'a-lovely-ontario-wedding',
            'excerpt' => 'A short excerpt.',
            'body' => "## Heading\n\nSome **markdown** body with a [link](/marketplace).",
            'category' => 'planning_tips',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $attrs));
    }

    // ── Public ───────────────────────────────────────────────────────────────

    public function test_index_shows_only_live_posts(): void
    {
        $this->makePost(['slug' => 'live', 'title' => 'Live Post']);
        $this->makePost(['slug' => 'draft', 'title' => 'Draft Post', 'status' => 'draft', 'published_at' => null]);
        $this->makePost(['slug' => 'future', 'title' => 'Future Post', 'published_at' => now()->addWeek()]);

        $this->get('/blog')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('public/blog-index')->has('posts', 1)
                ->where('posts.0.slug', 'live'));
    }

    public function test_a_published_post_renders_with_blogposting_schema(): void
    {
        $this->makePost();

        $response = $this->get('/blog/a-lovely-ontario-wedding')->assertOk();
        // The SEO head is server-rendered into the blade root view.
        $response->assertSee('"@type":"BlogPosting"', false);
        $response->assertSee('A Lovely Ontario Wedding', false);
    }

    public function test_markdown_body_renders_to_html(): void
    {
        $this->makePost();

        $this->get('/blog/a-lovely-ontario-wedding')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('public/blog-show')
                ->where('post.body_html', fn ($html) => str_contains($html, '<strong>markdown</strong>')
                    && str_contains($html, '<h2>')));
    }

    public function test_draft_and_unknown_posts_404(): void
    {
        $this->makePost(['slug' => 'secret', 'status' => 'draft', 'published_at' => null]);

        $this->get('/blog/secret')->assertNotFound();
        $this->get('/blog/does-not-exist')->assertNotFound();
    }

    public function test_category_filter_scopes_the_list(): void
    {
        $this->makePost(['slug' => 'budget-one', 'category' => 'budgeting']);
        $this->makePost(['slug' => 'planning-one', 'category' => 'planning_tips']);

        $this->get('/blog/category/budgeting')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->has('posts', 1)->where('posts.0.slug', 'budget-one'));

        $this->get('/blog/category/not-a-category')->assertNotFound();
    }

    public function test_sitemap_lists_published_posts(): void
    {
        $this->makePost(['slug' => 'in-sitemap']);
        $this->makePost(['slug' => 'draft-hidden', 'status' => 'draft', 'published_at' => null]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/blog/in-sitemap', $xml);
        $this->assertStringNotContainsString('/blog/draft-hidden', $xml);
    }

    // ── Admin authoring ──────────────────────────────────────────────────────

    public function test_non_admin_cannot_reach_blog_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/blog')->assertForbidden();
    }

    public function test_admin_can_create_and_publish_a_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'My First Post',
            'body' => '## Hi',
            'category' => 'venues',
            'status' => 'published',
        ])->assertRedirect();

        $post = BlogPost::where('title', 'My First Post')->firstOrFail();
        $this->assertSame('my-first-post', $post->slug); // auto-slugged
        $this->assertNotNull($post->published_at);

        // Now publicly visible.
        $this->get('/blog/my-first-post')->assertOk();
    }

    public function test_slugs_are_unique(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->makePost(['slug' => 'taken-title', 'title' => 'Taken Title']);

        $this->actingAs($admin)->post('/admin/blog', [
            'title' => 'Taken Title', 'body' => 'x', 'category' => 'venues', 'status' => 'draft',
        ])->assertRedirect();

        $this->assertSame('taken-title-2', BlogPost::where('title', 'Taken Title')->where('status', 'draft')->value('slug'));
    }
}

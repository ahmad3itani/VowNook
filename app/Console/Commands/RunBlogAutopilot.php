<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Support\Ai\AiService;
use App\Support\Blog\AiBlogWriter;
use App\Support\Blog\BlogTopics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Writes and PUBLISHES SEO blog articles from the curated Ontario topic queue.
 * Off unless BLOG_AUTOPILOT_ENABLED=true (or --force). Deliberately publishes a
 * small number per run on a slow cadence, de-dupes against existing slugs, and
 * relies on {@see AiBlogWriter}'s length gate so nothing thin ever ships.
 */
class RunBlogAutopilot extends Command
{
    protected $signature = 'blog:autopilot
        {--force : Run even when BLOG_AUTOPILOT_ENABLED is off}
        {--limit= : Override how many articles to publish this run}';

    protected $description = 'Generate and publish SEO blog articles from the curated Ontario topic queue.';

    public function handle(AiService $ai, AiBlogWriter $writer): int
    {
        if (! config('ai.blog_autopilot.enabled') && ! $this->option('force')) {
            $this->info('Blog autopilot is off (set BLOG_AUTOPILOT_ENABLED=true). Skipping.');

            return self::SUCCESS;
        }

        if (! $ai->isConfigured()) {
            $this->warn('AI is not configured — skipping blog autopilot.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) ($this->option('limit') ?: config('ai.blog_autopilot.per_run')));

        $existing = BlogPost::pluck('slug')->all();
        $queue = collect(BlogTopics::all())
            ->reject(fn (array $t) => in_array($t['slug'], $existing, true))
            ->take($limit);

        if ($queue->isEmpty()) {
            $this->info('No new topics left in the queue — nothing to publish.');

            return self::SUCCESS;
        }

        $published = 0;

        foreach ($queue as $topic) {
            $article = $writer->write($topic, $this->relatedLinksFor($topic));

            if ($article === null) {
                $this->warn("Skipped (generation failed or too thin): {$topic['slug']}");

                continue;
            }

            $post = BlogPost::create([
                'slug' => $topic['slug'],
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'body' => $article['body'],
                'category' => $topic['category'],
                'author_name' => 'VowNook',
                'meta_description' => $article['meta_description'],
                'status' => 'published',
                'published_at' => now(),
            ]);

            $published++;
            $this->info("Published: /blog/{$post->slug}");
            Log::info('Blog autopilot published an article', ['slug' => $post->slug, 'title' => $post->title]);
        }

        $this->info("Blog autopilot finished — {$published} article(s) published.");

        return self::SUCCESS;
    }

    /**
     * Already-published posts in the same cluster (the pillar + siblings) for the
     * writer to link to. Only published slugs are returned, so internal links
     * never 404 while the cluster is still filling in.
     *
     * @param  array<string,mixed>  $topic
     * @return list<array{title:string, url:string}>
     */
    private function relatedLinksFor(array $topic): array
    {
        $cluster = $topic['cluster'] ?? null;

        if ($cluster === null) {
            return [];
        }

        $pillar = BlogTopics::clusters()[$cluster]['pillar'] ?? null;

        $candidates = collect(BlogTopics::all())
            ->where('cluster', $cluster)
            ->pluck('slug')
            ->push($pillar)
            ->filter()
            ->reject(fn ($slug) => $slug === $topic['slug'])
            ->unique();

        return BlogPost::published()
            ->whereIn('slug', $candidates)
            ->limit(5)
            ->get(['slug', 'title'])
            ->map(fn (BlogPost $p) => ['title' => $p->title, 'url' => '/blog/'.$p->slug])
            ->all();
    }
}

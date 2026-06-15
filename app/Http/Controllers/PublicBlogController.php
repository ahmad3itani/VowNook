<?php

namespace App\Http\Controllers;

use App\Enums\BlogCategory;
use App\Models\BlogPost;
use App\Support\Seo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The public, SEO-first blog: an editorial index (optionally filtered by
 * category) and individual articles with BlogPosting structured data.
 */
class PublicBlogController extends Controller
{
    public function index(?string $categorySlug = null): Response
    {
        $category = $categorySlug ? BlogCategory::fromSeoSlug($categorySlug) : null;
        abort_if($categorySlug !== null && $category === null, 404);

        $posts = BlogPost::query()
            ->published()
            ->when($category, fn ($q) => $q->where('category', $category->value))
            ->latest('published_at')
            ->get();

        $base = rtrim(config('app.url'), '/');
        $title = $category
            ? "{$category->label()} — Wedding Blog"
            : 'Wedding Planning Blog — Real Ontario Advice';
        $description = $category
            ? "Practical {$category->label()} articles for couples planning a wedding in Ontario."
            : 'Honest, practical advice for planning a wedding in Ontario — budgets, timelines, venues, and choosing the right vendors.';
        $canonical = $category ? route('blog.category', $category->seoSlug()) : route('blog.index');

        $seo = Seo::make(
            title: $title,
            description: $description,
            canonical: $canonical,
            type: 'website',
            schemas: [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Blog',
                    'name' => 'VowNook Wedding Blog',
                    'url' => route('blog.index'),
                ],
                Seo::breadcrumbs(array_filter([
                    'Home' => $base,
                    'Blog' => route('blog.index'),
                    $category?->label() => $category ? $canonical : null,
                ])),
            ],
        );

        return Inertia::render('public/blog-index', [
            'posts' => $posts->map(fn (BlogPost $p) => $this->card($p))->values(),
            'categories' => array_map(
                fn (BlogCategory $c) => ['label' => $c->label(), 'slug' => $c->seoSlug()],
                BlogCategory::cases(),
            ),
            'active_category' => $category?->seoSlug(),
        ])->withViewData(['seo' => $seo]);
    }

    public function show(string $slug): Response
    {
        $post = BlogPost::query()->published()->where('slug', $slug)->firstOrFail();

        $base = rtrim(config('app.url'), '/');
        $url = route('blog.show', $post->slug);
        $cover = $post->coverUrl();
        $description = $post->meta_description
            ?: ($post->excerpt ?: Str::limit(strip_tags($post->renderedBody()), 155));

        $related = BlogPost::query()
            ->published()
            ->where('category', $post->category->value)
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        // Cover as a schema.org ImageObject (with dimensions when known).
        $imageNode = null;
        if ($cover) {
            $imageNode = ['@type' => 'ImageObject', 'url' => $cover];
            if ($dims = \App\Support\ImageOptimizer::dimensions($post->cover_image_path)) {
                $imageNode['width'] = $dims[0];
                $imageNode['height'] = $dims[1];
            }
        }

        $seo = Seo::make(
            title: $post->meta_title ?: $post->title,
            description: $description,
            canonical: $url,
            image: $cover,
            type: 'article',
            schemas: [
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    'headline' => $post->title,
                    'description' => $description,
                    'image' => $imageNode,
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'dateModified' => $post->updated_at?->toIso8601String(),
                    // A named Person strengthens E-E-A-T vs a bare Organization.
                    'author' => ['@type' => 'Person', 'name' => $post->author_name],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => config('app.name'),
                        'logo' => ['@type' => 'ImageObject', 'url' => $base.'/apple-touch-icon.png'],
                    ],
                    'articleSection' => $post->category->label(),
                    'wordCount' => str_word_count(strip_tags($post->renderedBody())),
                    'inLanguage' => 'en-CA',
                    'isAccessibleForFree' => true,
                    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
                ], fn ($v) => $v !== null && $v !== []),
                Seo::breadcrumbs([
                    'Home' => $base,
                    'Blog' => route('blog.index'),
                    $post->title => $url,
                ]),
            ],
        );

        return Inertia::render('public/blog-show', [
            'post' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'body_html' => $post->renderedBody(),
                'excerpt' => $post->excerpt,
                'cover_url' => $cover,
                'cover_alt' => $post->cover_alt ?: $post->title,
                'category' => ['label' => $post->category->label(), 'slug' => $post->category->seoSlug()],
                'author_name' => $post->author_name,
                'published_at' => $post->published_at?->toIso8601String(),
                'published_label' => $post->published_at?->format('F j, Y'),
                'reading_minutes' => $post->readingMinutes(),
            ],
            'related' => $related->map(fn (BlogPost $p) => $this->card($p))->values(),
        ])->withViewData(['seo' => $seo]);
    }

    public function media(string $filename): StreamedResponse
    {
        $path = 'blog/'.basename($filename);
        abort_unless(Storage::exists($path), 404);

        return Storage::response($path);
    }

    /** @return array<string,mixed> */
    protected function card(BlogPost $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt ?: Str::limit(strip_tags($post->renderedBody()), 140),
            'cover_url' => $post->coverUrl(),
            'category' => ['label' => $post->category->label(), 'slug' => $post->category->seoSlug()],
            'published_label' => $post->published_at?->format('M j, Y'),
            'reading_minutes' => $post->readingMinutes(),
        ];
    }
}

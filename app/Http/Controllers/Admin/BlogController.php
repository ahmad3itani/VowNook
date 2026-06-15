<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlogCategory;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin authoring for the public blog: create, edit, publish and delete posts.
 */
class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::query()
            ->latest('updated_at')
            ->get()
            ->map(fn (BlogPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'category' => $p->category->label(),
                'status' => $p->status,
                'published_label' => $p->published_at?->format('M j, Y'),
            ]);

        return Inertia::render('admin/blog-index', ['posts' => $posts]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/blog-edit', [
            'post' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $post = new BlogPost($data);
        $post->slug = BlogPost::uniqueSlug($data['title']);
        $post->published_at = $this->resolvePublishedAt($data);
        $post->save();

        return redirect()->route('admin.blog.edit', $post)->with('status', 'post-created');
    }

    public function edit(BlogPost $post): Response
    {
        return Inertia::render('admin/blog-edit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'category' => $post->category->value,
                'author_name' => $post->author_name,
                'meta_title' => $post->meta_title,
                'meta_description' => $post->meta_description,
                'status' => $post->status,
                'published_at' => $post->published_at?->toDateString(),
                'cover_url' => $post->coverUrl(),
                'cover_alt' => $post->cover_alt,
                'public_url' => $post->status === 'published' ? route('blog.show', $post->slug) : null,
            ],
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $data = $this->validateData($request);

        $post->fill($data);
        $post->published_at = $this->resolvePublishedAt($data, $post);
        $post->save();

        return back()->with('status', 'post-updated');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        if ($post->cover_image_path) {
            Storage::delete($post->cover_image_path);
        }
        $post->delete();

        return redirect()->route('admin.blog.index')->with('status', 'post-deleted');
    }

    public function uploadCover(Request $request, BlogPost $post): RedirectResponse
    {
        $request->validate(['cover' => ['required', 'image', 'max:8192']]);

        if ($post->cover_image_path) {
            Storage::delete($post->cover_image_path);
        }

        $post->cover_image_path = ImageOptimizer::store($request->file('cover'), 'blog', 2000);
        $post->save();

        return back()->with('status', 'cover-updated');
    }

    /**
     * Upload an in-article image and return its public URL (the editor inserts
     * it as markdown). Post-independent so it works while drafting a new post.
     */
    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:8192']]);

        $path = ImageOptimizer::store($request->file('image'), 'blog', 1600);

        return response()->json(['url' => route('blog.media', basename($path))]);
    }

    /** @return array<string,mixed> */
    protected function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_alt' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'category' => ['required', Rule::in(BlogCategory::values())],
            'author_name' => ['nullable', 'string', 'max:120'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['author_name'] = $data['author_name'] ?? 'VowNook';

        return $data;
    }

    /**
     * A published post gets a published_at (defaulting to now); a draft keeps
     * its date so re-publishing preserves the original date when present.
     *
     * @param  array<string,mixed>  $data
     */
    protected function resolvePublishedAt(array $data, ?BlogPost $existing = null): ?string
    {
        if (($data['status'] ?? null) !== 'published') {
            return $existing?->published_at?->toDateTimeString();
        }

        return $data['published_at']
            ?? $existing?->published_at?->toDateTimeString()
            ?? now()->toDateTimeString();
    }

    /** @return array<string,mixed> */
    protected function options(): array
    {
        return [
            'categories' => array_map(
                fn (BlogCategory $c) => ['value' => $c->value, 'label' => $c->label()],
                BlogCategory::cases(),
            ),
        ];
    }
}

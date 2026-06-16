<?php

namespace App\Http\Controllers;

use App\Models\GalleryPhoto;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use App\Support\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GalleryController extends Controller
{
    public function __construct(
        protected CurrentWedding $current,
        protected PlanLimits $limits,
    ) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $photos = GalleryPhoto::query()
            ->forWedding($weddingId)
            ->ordered()
            ->get();

        return Inertia::render('gallery/index', [
            'photos' => $photos->map(fn (GalleryPhoto $p) => [
                'id' => $p->id,
                'caption' => $p->caption,
                'original_name' => $p->original_name,
                'size' => $p->size,
                'url' => route('gallery.file', $p),
            ]),
            'stats' => [
                'total' => $photos->count(),
                'size' => $photos->sum('size'),
            ],
            'plan' => [
                'used' => $photos->count(),
                'limit' => $this->limits->limit($this->current->get(), 'max_gallery_photos'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'max:30'],
            'photos.*' => ['image', 'max:10240'], // 10 MB each
        ]);

        $wedding = $this->current->get();
        $weddingId = $wedding->id;
        $order = (int) GalleryPhoto::forWedding($weddingId)->max('sort_order');

        foreach ($request->file('photos') as $file) {
            // Enforce the plan limit per file so a batch upload stops at the
            // cap instead of silently overshooting it.
            $this->limits->enforceGallery($wedding);

            $path = ImageOptimizer::store($file, "galleries/{$weddingId}", 2000);

            GalleryPhoto::create([
                'wedding_id' => $weddingId,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => Storage::mimeType($path) ?: $file->getMimeType(),
                'size' => Storage::size($path),
                'sort_order' => ++$order,
            ]);
        }

        return back()->with('status', 'photo-uploaded');
    }

    public function update(Request $request, GalleryPhoto $photo): RedirectResponse
    {
        $this->authorizeTenant($photo);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $photo->update(['caption' => $data['caption'] ?? null]);

        return back()->with('status', 'photo-updated');
    }

    public function destroy(GalleryPhoto $photo): RedirectResponse
    {
        $this->authorizeTenant($photo);

        Storage::delete($photo->path);
        $photo->delete();

        return back()->with('status', 'photo-deleted');
    }

    /** Persist a drag-and-drop reordering (only this wedding's photos). */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $ownIds = GalleryPhoto::forWedding($this->current->id())->pluck('id')->all();

        foreach ($data['items'] as $item) {
            if (in_array($item['id'], $ownIds, true)) {
                GalleryPhoto::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        }

        return back()->with('status', 'gallery-reordered');
    }

    /** Delete several photos at once (multi-select), scoped to the tenant. */
    public function destroyMany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $photos = GalleryPhoto::forWedding($this->current->id())
            ->whereIn('id', $data['ids'])
            ->get();

        foreach ($photos as $photo) {
            Storage::delete($photo->path);
            $photo->delete();
        }

        return back()->with('status', 'photos-deleted');
    }

    /** Stream a photo's bytes, gated by tenancy + the gallery read permission. */
    public function file(GalleryPhoto $photo): StreamedResponse
    {
        $this->authorizeTenant($photo);

        abort_unless(Storage::exists($photo->path), 404);

        return Storage::response($photo->path, $photo->original_name, [
            'Content-Type' => $photo->mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    protected function authorizeTenant(GalleryPhoto $photo): void
    {
        abort_unless($photo->wedding_id === $this->current->id(), 404);
    }
}

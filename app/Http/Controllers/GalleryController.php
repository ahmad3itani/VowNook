<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use App\Support\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GalleryController extends Controller
{
    public function __construct(
        protected CurrentWedding $current,
        protected PlanLimits $limits,
    ) {}

    public function index(Request $request): Response
    {
        $weddingId = $this->current->id();

        $albums = GalleryAlbum::forWedding($weddingId)
            ->ordered()
            ->withCount('photos')
            ->get();

        // Active view: 'all' (default), 'unsorted', or a numeric album id.
        $active = (string) $request->query('album', 'all');
        if ($active !== 'all' && $active !== 'unsorted' && ! $albums->contains('id', (int) $active)) {
            $active = 'all';
        }

        $query = GalleryPhoto::query()->forWedding($weddingId)->ordered();
        if ($active === 'unsorted') {
            $query->whereNull('album_id');
        } elseif ($active !== 'all') {
            $query->where('album_id', (int) $active);
        }
        $photos = $query->get();

        return Inertia::render('gallery/index', [
            'photos' => $photos->map(fn (GalleryPhoto $p) => [
                'id' => $p->id,
                'album_id' => $p->album_id,
                'caption' => $p->caption,
                'original_name' => $p->original_name,
                'size' => $p->size,
                'url' => route('gallery.file', $p),
            ]),
            'albums' => $albums->map(fn (GalleryAlbum $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'count' => $a->photos_count,
            ]),
            'active_album' => $active,
            'stats' => [
                'total' => GalleryPhoto::forWedding($weddingId)->count(),
                'size' => GalleryPhoto::forWedding($weddingId)->sum('size'),
            ],
            'plan' => [
                'used' => GalleryPhoto::forWedding($weddingId)->count(),
                'limit' => $this->limits->limit($this->current->get(), 'max_gallery_photos'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'max:30'],
            'photos.*' => ['image', 'max:10240'], // 10 MB each
            'album_id' => ['nullable', 'integer'],
        ]);

        $wedding = $this->current->get();
        $weddingId = $wedding->id;

        // Only honour an album that belongs to this wedding.
        $albumId = $data['album_id'] ?? null;
        if ($albumId !== null && ! GalleryAlbum::forWedding($weddingId)->whereKey($albumId)->exists()) {
            $albumId = null;
        }

        $order = (int) GalleryPhoto::forWedding($weddingId)->max('sort_order');

        foreach ($request->file('photos') as $file) {
            // Enforce the plan limit per file so a batch upload stops at the
            // cap instead of silently overshooting it.
            $this->limits->enforceGallery($wedding);

            $path = ImageOptimizer::store($file, "galleries/{$weddingId}", 2000);

            GalleryPhoto::create([
                'wedding_id' => $weddingId,
                'album_id' => $albumId,
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

    /** Use a gallery photo as the public wedding website's hero/cover image. */
    public function setAsCover(GalleryPhoto $photo): RedirectResponse
    {
        $this->authorizeTenant($photo);

        abort_unless(Storage::exists($photo->path), 404);

        $wedding = $this->current->get();
        $website = $wedding->website()->firstOrCreate(['wedding_id' => $wedding->id]);

        // Copy into the hero location so it serves via the website media route
        // and survives the original gallery photo being deleted.
        $ext = pathinfo($photo->path, PATHINFO_EXTENSION) ?: 'jpg';
        $heroPath = "websites/{$wedding->id}/hero/".Str::random(40).'.'.$ext;
        Storage::put($heroPath, Storage::get($photo->path));

        if ($website->hero_image_path) {
            Storage::delete($website->hero_image_path);
        }
        $website->update(['hero_image_path' => $heroPath]);

        return back()->with('status', 'cover-set');
    }

    /** Stream a ZIP of every gallery photo for this wedding. */
    public function downloadAll(): SymfonyResponse
    {
        $weddingId = $this->current->id();
        $photos = GalleryPhoto::forWedding($weddingId)->ordered()->get();

        if ($photos->isEmpty()) {
            return back()->with('status', 'gallery-empty');
        }

        abort_unless(class_exists(\ZipArchive::class), 503, 'Zip support is unavailable.');

        $tmp = (string) tempnam(sys_get_temp_dir(), 'vownook-gallery');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($photos as $i => $photo) {
            if (! Storage::exists($photo->path)) {
                continue;
            }

            // Index-prefixed so names are unique and keep the gallery order.
            $name = sprintf('%03d-%s', $i + 1, basename($photo->original_name ?: "photo-{$photo->id}.jpg"));
            $zip->addFromString($name, (string) Storage::get($photo->path));
        }

        $zip->close();

        return response()->download($tmp, 'vownook-gallery.zip')->deleteFileAfterSend(true);
    }

    // ── Albums ──────────────────────────────────────────────────────────────

    public function storeAlbum(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $weddingId = $this->current->id();
        $order = (int) GalleryAlbum::forWedding($weddingId)->max('sort_order');

        GalleryAlbum::create([
            'wedding_id' => $weddingId,
            'name' => $data['name'],
            'sort_order' => $order + 1,
        ]);

        return back()->with('status', 'album-created');
    }

    public function updateAlbum(Request $request, GalleryAlbum $album): RedirectResponse
    {
        $this->authorizeAlbum($album);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $album->update(['name' => $data['name']]);

        return back()->with('status', 'album-updated');
    }

    /** Delete an album; its photos are kept and become Unsorted. */
    public function destroyAlbum(GalleryAlbum $album): RedirectResponse
    {
        $this->authorizeAlbum($album);

        // nullOnDelete on the FK un-sorts the photos automatically.
        $album->delete();

        return back()->with('status', 'album-deleted');
    }

    /** Move selected photos into an album (or to Unsorted when album_id is null). */
    public function moveToAlbum(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'album_id' => ['nullable', 'integer'],
        ]);

        $weddingId = $this->current->id();

        $albumId = $data['album_id'] ?? null;
        if ($albumId !== null && ! GalleryAlbum::forWedding($weddingId)->whereKey($albumId)->exists()) {
            abort(404);
        }

        GalleryPhoto::forWedding($weddingId)
            ->whereIn('id', $data['ids'])
            ->update(['album_id' => $albumId]);

        return back()->with('status', 'photos-moved');
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

    protected function authorizeAlbum(GalleryAlbum $album): void
    {
        abort_unless($album->wedding_id === $this->current->id(), 404);
    }
}

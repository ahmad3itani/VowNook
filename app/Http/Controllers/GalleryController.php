<?php

namespace App\Http\Controllers;

use App\Models\GalleryPhoto;
use App\Support\CurrentWedding;
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
            ->latest()
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
        $this->limits->enforceGallery($this->current->get());

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:10240'], // 10 MB
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $data['photo'];
        $weddingId = $this->current->id();
        $path = $file->store("galleries/{$weddingId}");

        GalleryPhoto::create([
            'wedding_id' => $weddingId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'caption' => $data['caption'] ?? null,
        ]);

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

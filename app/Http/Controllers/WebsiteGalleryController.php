<?php

namespace App\Http\Controllers;

use App\Models\WeddingWebsitePhoto;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebsiteGalleryController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'photo'   => ['required', 'image', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $wedding = $this->current->get();
        $website = $wedding->website()->firstOrCreate(['wedding_id' => $wedding->id]);

        $path = ImageOptimizer::store($request->file('photo'), "websites/{$wedding->id}/gallery", 2000);

        $maxOrder = $website->photos()->max('sort_order') ?? 0;

        WeddingWebsitePhoto::create([
            'wedding_website_id' => $website->id,
            'path'       => $path,
            'caption'    => $request->input('caption'),
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('status', 'photo-uploaded');
    }

    public function destroy(WeddingWebsitePhoto $photo): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($photo->website->wedding_id === $wedding->id, 403);

        Storage::delete($photo->path);
        $photo->delete();

        return back()->with('status', 'photo-deleted');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'items'             => ['required', 'array'],
            'items.*.id'        => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $wedding = $this->current->get();
        $website = $wedding->website;
        abort_if($website === null, 404);

        $photoIds = $website->photos()->pluck('id')->all();

        foreach ($request->input('items') as $item) {
            if (in_array($item['id'], $photoIds, true)) {
                WeddingWebsitePhoto::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        }

        return back()->with('status', 'gallery-reordered');
    }
}

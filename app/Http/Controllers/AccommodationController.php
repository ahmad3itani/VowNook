<?php

namespace App\Http\Controllers;

use App\Models\WeddingAccommodation;
use App\Support\Affiliates\TravelAffiliates;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Couple-side "Travel & stays" editor — hotel room blocks, rentals and transport
 * options for out-of-town guests, plus a free-text travel-notes field shown on
 * the public wedding site.
 */
class AccommodationController extends Controller
{
    public const TYPES = ['hotel', 'rental', 'transport'];

    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();

        $stays = WeddingAccommodation::forWedding($wedding->id)->ordered()->get();

        return Inertia::render('travel/index', [
            'accommodations' => $stays->map(fn (WeddingAccommodation $a) => $this->data($a, $wedding->slug)),
            'travel_notes' => $wedding->website?->travel_notes ?? '',
            'types' => self::TYPES,
            // The affiliate "stays near your venue" map: whether it can show at all
            // (an account id is configured), the couple's toggle, and the venue it
            // would search around.
            'affiliate_enabled' => app(TravelAffiliates::class)->isConfigured(),
            'affiliate_partner' => TravelAffiliates::PARTNER,
            'show_travel_stays' => (bool) ($wedding->website?->show_travel_stays ?? true),
            'venue_name' => $wedding->website?->venue_name,
            'has_venue' => filled($wedding->website?->venue_name) || filled($wedding->website?->venue_address),
        ]);
    }

    /** Toggle the affiliate "stays near your venue" map on the public site. */
    public function updateStaysVisibility(Request $request): RedirectResponse
    {
        $data = $request->validate(['show_travel_stays' => ['required', 'boolean']]);
        $wedding = $this->current->get();

        $website = $wedding->website()->firstOrCreate(['wedding_id' => $wedding->id]);
        $website->update(['show_travel_stays' => $data['show_travel_stays']]);

        return back()->with('status', 'travel-saved');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateStay($request);
        $wedding = $this->current->get();

        $data['wedding_id'] = $wedding->id;
        $data['sort_order'] = (int) WeddingAccommodation::forWedding($wedding->id)->max('sort_order') + 1;
        $data['image_path'] = $this->storeImage($request, $wedding->id);

        WeddingAccommodation::create($data);

        return back()->with('status', 'travel-saved');
    }

    public function update(Request $request, WeddingAccommodation $accommodation): RedirectResponse
    {
        $this->authorizeOwn($accommodation->wedding_id);
        $data = $this->validateStay($request);

        if ($path = $this->storeImage($request, $accommodation->wedding_id)) {
            if ($accommodation->image_path) {
                Storage::delete($accommodation->image_path);
            }
            $data['image_path'] = $path;
        }

        $accommodation->update($data);

        return back()->with('status', 'travel-saved');
    }

    public function destroy(WeddingAccommodation $accommodation): RedirectResponse
    {
        $this->authorizeOwn($accommodation->wedding_id);

        if ($accommodation->image_path) {
            Storage::delete($accommodation->image_path);
        }
        $accommodation->delete();

        return back()->with('status', 'travel-deleted');
    }

    /** The free-text "getting there / parking / shuttle" notes live on the website. */
    public function updateNotes(Request $request): RedirectResponse
    {
        $data = $request->validate(['travel_notes' => ['nullable', 'string', 'max:2000']]);
        $wedding = $this->current->get();

        $website = $wedding->website()->firstOrCreate(['wedding_id' => $wedding->id]);
        $website->update(['travel_notes' => $data['travel_notes'] ?? null]);

        return back()->with('status', 'travel-saved');
    }

    /** @return array<string,mixed> */
    private function validateStay(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'address' => ['nullable', 'string', 'max:300'],
            'blurb' => ['nullable', 'string', 'max:1000'],
            'booking_url' => ['nullable', 'url', 'max:300'],
            'block_code' => ['nullable', 'string', 'max:80'],
            'price_note' => ['nullable', 'string', 'max:80'],
            'distance_note' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
        ]);
    }

    private function storeImage(Request $request, int $weddingId): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $request->validate(['image' => ['image', 'max:10240']]);

        return ImageOptimizer::store($request->file('image'), "websites/{$weddingId}/travel", 1200);
    }

    private function authorizeOwn(int $weddingId): void
    {
        abort_unless($weddingId === $this->current->id(), 404);
    }

    /** @return array<string,mixed> */
    private function data(WeddingAccommodation $a, string $slug): array
    {
        return [
            'id' => $a->id,
            'name' => $a->name,
            'type' => $a->type,
            'address' => $a->address,
            'blurb' => $a->blurb,
            'booking_url' => $a->booking_url,
            'block_code' => $a->block_code,
            'price_note' => $a->price_note,
            'distance_note' => $a->distance_note,
            'is_active' => $a->is_active,
            'image_url' => $a->image_path
                ? route('website.media', [$slug, 'travel', basename($a->image_path)])
                : null,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\RegistryFund;
use App\Models\RegistryItem;
use App\Support\CurrentWedding;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Couple-side gift registry editor: cash/honeymoon/custom funds (guests pay the
 * couple directly via the couple's own payout link) and gift items (guests claim).
 */
class RegistryController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();

        $funds = RegistryFund::forWedding($wedding->id)->ordered()->withCount('contributions')->get();
        $items = RegistryItem::forWedding($wedding->id)->ordered()->get();

        return Inertia::render('registry/index', [
            'funds' => $funds->map(fn (RegistryFund $f) => $this->fundData($f, $wedding->slug)),
            'items' => $items->map(fn (RegistryItem $i) => $this->itemData($i, $wedding->slug)),
        ]);
    }

    // ── Funds ────────────────────────────────────────────────────────────────

    public function storeFund(Request $request): RedirectResponse
    {
        $data = $this->validateFund($request);
        $wedding = $this->current->get();

        $data['wedding_id'] = $wedding->id;
        $data['sort_order'] = (int) RegistryFund::forWedding($wedding->id)->max('sort_order') + 1;
        $data['image_path'] = $this->storeImage($request, $wedding->id);

        RegistryFund::create($data);

        return back()->with('status', 'registry-saved');
    }

    public function updateFund(Request $request, RegistryFund $fund): RedirectResponse
    {
        $this->authorizeOwn($fund->wedding_id);
        $data = $this->validateFund($request);

        if ($path = $this->storeImage($request, $fund->wedding_id)) {
            if ($fund->image_path) {
                Storage::delete($fund->image_path);
            }
            $data['image_path'] = $path;
        }

        $fund->update($data);

        return back()->with('status', 'registry-saved');
    }

    public function destroyFund(RegistryFund $fund): RedirectResponse
    {
        $this->authorizeOwn($fund->wedding_id);

        if ($fund->image_path) {
            Storage::delete($fund->image_path);
        }
        $fund->delete();

        return back()->with('status', 'registry-deleted');
    }

    // ── Items ────────────────────────────────────────────────────────────────

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $this->validateItem($request);
        $wedding = $this->current->get();

        $data['wedding_id'] = $wedding->id;
        $data['sort_order'] = (int) RegistryItem::forWedding($wedding->id)->max('sort_order') + 1;
        $data['image_path'] = $this->storeImage($request, $wedding->id);

        RegistryItem::create($data);

        return back()->with('status', 'registry-saved');
    }

    public function updateItem(Request $request, RegistryItem $item): RedirectResponse
    {
        $this->authorizeOwn($item->wedding_id);
        $data = $this->validateItem($request);

        if ($path = $this->storeImage($request, $item->wedding_id)) {
            if ($item->image_path) {
                Storage::delete($item->image_path);
            }
            $data['image_path'] = $path;
        }

        $item->update($data);

        return back()->with('status', 'registry-saved');
    }

    public function destroyItem(RegistryItem $item): RedirectResponse
    {
        $this->authorizeOwn($item->wedding_id);

        if ($item->image_path) {
            Storage::delete($item->image_path);
        }
        $item->delete();

        return back()->with('status', 'registry-deleted');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function validateFund(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'blurb' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:cash,honeymoon,custom'],
            'goal_cents' => ['nullable', 'integer', 'min:0'],
            'payout_url' => ['nullable', 'url', 'max:300'],
            'is_active' => ['boolean'],
        ]);
    }

    /** @return array<string,mixed> */
    private function validateItem(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'blurb' => ['nullable', 'string', 'max:1000'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'store_url' => ['nullable', 'url', 'max:300'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
    }

    private function storeImage(Request $request, int $weddingId): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $request->validate(['image' => ['image', 'max:10240']]);

        return ImageOptimizer::store($request->file('image'), "websites/{$weddingId}/registry", 1200);
    }

    private function authorizeOwn(int $weddingId): void
    {
        abort_unless($weddingId === $this->current->id(), 404);
    }

    /** @return array<string,mixed> */
    private function fundData(RegistryFund $f, string $slug): array
    {
        return [
            'id' => $f->id,
            'title' => $f->title,
            'blurb' => $f->blurb,
            'type' => $f->type,
            'goal_cents' => $f->goal_cents,
            'raised_cents' => $f->raised_cents,
            'payout_url' => $f->payout_url,
            'is_active' => $f->is_active,
            'contributions_count' => $f->contributions_count,
            'image_url' => $f->image_path
                ? route('website.media', [$slug, 'registry', basename($f->image_path)])
                : null,
        ];
    }

    /** @return array<string,mixed> */
    private function itemData(RegistryItem $i, string $slug): array
    {
        return [
            'id' => $i->id,
            'name' => $i->name,
            'blurb' => $i->blurb,
            'price_cents' => $i->price_cents,
            'store_url' => $i->store_url,
            'quantity' => $i->quantity,
            'claimed_count' => $i->claimed_count,
            'image_url' => $i->image_path
                ? route('website.media', [$slug, 'registry', basename($i->image_path)])
                : null,
        ];
    }
}

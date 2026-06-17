<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Guest;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gifts & thank-yous — the couple's record of every gift received (registry
 * contributions auto-flow in; physical/cash gifts are added by hand) plus
 * whether a thank-you note has gone out.
 */
class GiftController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();

        $gifts = Gift::forWedding($wedding->id)
            ->with('guest:id,first_name,last_name')
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('gifts/index', [
            'gifts' => $gifts->map(fn (Gift $g) => $this->giftData($g)),
            'summary' => [
                'total' => $gifts->count(),
                'pending' => $gifts->where('thank_you_sent', false)->count(),
                'cash_cents' => (int) $gifts->whereIn('kind', ['fund', 'cash'])->sum('amount_cents'),
            ],
            'kinds' => Gift::KINDS,
            'guests' => $wedding->guests()->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn (Guest $g) => ['id' => $g->id, 'name' => trim($g->first_name.' '.($g->last_name ?? ''))]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGift($request);
        $wedding = $this->current->get();

        $data['wedding_id'] = $wedding->id;
        $this->assertGuestOwned($data['guest_id'] ?? null, $wedding->id);

        Gift::create($data);

        return back()->with('status', 'gift-saved');
    }

    public function update(Request $request, Gift $gift): RedirectResponse
    {
        $this->authorizeOwn($gift->wedding_id);
        $data = $this->validateGift($request);
        $this->assertGuestOwned($data['guest_id'] ?? null, $gift->wedding_id);

        $gift->update($data);

        return back()->with('status', 'gift-saved');
    }

    /** Toggle (or set) the thank-you-sent flag. */
    public function toggleThankYou(Request $request, Gift $gift): RedirectResponse
    {
        $this->authorizeOwn($gift->wedding_id);

        $sent = $request->boolean('thank_you_sent', ! $gift->thank_you_sent);
        $gift->update(['thank_you_sent' => $sent]);

        return back()->with('status', 'gift-saved');
    }

    public function destroy(Gift $gift): RedirectResponse
    {
        $this->authorizeOwn($gift->wedding_id);

        $gift->delete();

        return back()->with('status', 'gift-deleted');
    }

    /** @return array<string,mixed> */
    private function validateGift(Request $request): array
    {
        return $request->validate([
            'from_name' => ['nullable', 'string', 'max:160'],
            'kind' => ['required', 'in:'.implode(',', Gift::KINDS)],
            'amount_cents' => ['nullable', 'integer', 'min:0'],
            'received_at' => ['nullable', 'date'],
            'guest_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'thank_you_sent' => ['boolean'],
        ]);
    }

    private function assertGuestOwned(?int $guestId, int $weddingId): void
    {
        if ($guestId !== null) {
            abort_unless(
                Guest::query()->forWedding($weddingId)->whereKey($guestId)->exists(),
                422,
            );
        }
    }

    private function authorizeOwn(int $weddingId): void
    {
        abort_unless($weddingId === $this->current->id(), 404);
    }

    /** @return array<string,mixed> */
    private function giftData(Gift $g): array
    {
        return [
            'id' => $g->id,
            'from_name' => $g->from_name,
            'kind' => $g->kind,
            'amount_cents' => $g->amount_cents,
            'received_at' => $g->received_at?->toDateString(),
            'thank_you_sent' => $g->thank_you_sent,
            'notes' => $g->notes,
            'guest' => $g->guest ? ['id' => $g->guest->id, 'name' => trim($g->guest->first_name.' '.($g->guest->last_name ?? ''))] : null,
            'from_registry' => $g->registry_contribution_id !== null,
        ];
    }
}

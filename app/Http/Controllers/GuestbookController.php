<?php

namespace App\Http\Controllers;

use App\Models\GuestbookEntry;
use App\Models\Wedding;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The wedding-site guestbook: guests leave well-wishes publicly (held for the
 * couple's approval), and the couple moderates them from the website editor.
 */
class GuestbookController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    /** Public — a guest leaves a well-wish on a published wedding site. */
    public function store(Request $request, Wedding $wedding): RedirectResponse
    {
        abort_unless($wedding->website?->is_published, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        GuestbookEntry::create([
            'wedding_id' => $wedding->id,
            'name' => $data['name'],
            'message' => $data['message'],
        ]);

        return back()->with('status', 'guestbook-submitted');
    }

    /** Couple — approve a pending entry so it shows publicly. */
    public function approve(GuestbookEntry $entry): RedirectResponse
    {
        $this->authorizeOwn($entry);

        $entry->update(['approved_at' => now()]);

        return back()->with('status', 'guestbook-approved');
    }

    public function destroy(GuestbookEntry $entry): RedirectResponse
    {
        $this->authorizeOwn($entry);

        $entry->delete();

        return back()->with('status', 'guestbook-deleted');
    }

    private function authorizeOwn(GuestbookEntry $entry): void
    {
        abort_unless($entry->wedding_id === $this->current->id(), 404);
    }
}

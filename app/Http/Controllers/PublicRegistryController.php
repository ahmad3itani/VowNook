<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\RegistryContribution;
use App\Models\RegistryFund;
use App\Models\RegistryItem;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Guest-facing registry actions on the public wedding website. Funds are
 * pass-through (guests pay the couple via the couple's payout link), so a
 * contribution here is a self-reported log that drives the progress bar and
 * the couple's thank-you list.
 */
class PublicRegistryController extends Controller
{
    public function contribute(Request $request, Wedding $wedding, RegistryFund $fund): RedirectResponse
    {
        abort_unless($fund->wedding_id === $wedding->id, 404);

        $data = $request->validate([
            'contributor_name' => ['nullable', 'string', 'max:120'],
            'contributor_email' => ['nullable', 'email', 'max:160'],
            'amount_cents' => ['required', 'integer', 'min:100', 'max:100000000'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $contribution = RegistryContribution::create([
            'registry_fund_id' => $fund->id,
            'contributor_name' => $data['contributor_name'] ?? null,
            'contributor_email' => $data['contributor_email'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'message' => $data['message'] ?? null,
            'status' => 'logged',
        ]);

        $fund->increment('raised_cents', $data['amount_cents']);

        // Auto-create a gift row so it lands on the couple's thank-you list.
        Gift::create([
            'wedding_id' => $wedding->id,
            'registry_contribution_id' => $contribution->id,
            'from_name' => $data['contributor_name'] ?? null,
            'kind' => 'fund',
            'amount_cents' => $data['amount_cents'],
            'received_at' => now(),
            'notes' => $data['message'] ?? null,
        ]);

        return back()->with('status', 'contribution-recorded');
    }

    public function claim(Request $request, Wedding $wedding, RegistryItem $item): RedirectResponse
    {
        abort_unless($item->wedding_id === $wedding->id, 404);

        if ($item->claimed_count >= $item->quantity) {
            return back()->with('status', 'item-already-claimed');
        }

        $item->increment('claimed_count');

        return back()->with('status', 'item-claimed');
    }
}

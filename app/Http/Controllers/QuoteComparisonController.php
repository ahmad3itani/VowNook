<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Support\CurrentWedding;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Side-by-side comparison of the offers a couple has received, grouped by vendor
 * category — the "choose the most suitable" step of the marketplace loop.
 */
class QuoteComparisonController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();
        abort_if($wedding === null, 403, 'No active wedding.');

        // Inquiries that have a live offer to compare (offered or already accepted).
        $inquiries = Inquiry::forWedding($wedding->id)
            ->whereIn('status', [InquiryStatus::Offered->value, InquiryStatus::Accepted->value])
            ->with(['vendorProfile', 'offer'])
            ->get()
            ->filter(fn (Inquiry $i) => $i->offer !== null);

        // Group by category label so a couple compares like-for-like vendors.
        $groups = $inquiries
            ->groupBy(fn (Inquiry $i) => $i->vendorProfile?->category?->label() ?? 'Other')
            ->map(fn ($items, $label) => [
                'category' => $label,
                'offers'   => $items->map(fn (Inquiry $i) => [
                    'inquiry_id'    => $i->id,
                    'vendor_name'   => $i->vendorProfile?->business_name,
                    'vendor_slug'   => $i->vendorProfile?->slug,
                    'status'        => $i->status?->value,
                    'is_accepted'   => $i->status === InquiryStatus::Accepted,
                    'total_cents'   => $i->offer->total_cents,
                    'deposit_cents' => $i->offer->deposit_cents,
                    'line_items'    => $i->offer->line_items ?? [],
                    'valid_until'   => $i->offer->valid_until?->toDateString(),
                    'terms'         => $i->offer->terms,
                    'can_accept'    => $i->status === InquiryStatus::Offered
                        && $i->offer->status->value === 'sent',
                ])->values()->all(),
            ])
            ->values();

        return Inertia::render('vendors/quote-compare', [
            'groups'      => $groups,
            'quote_badge' => Inquiry::offersAwaiting($wedding->id),
        ]);
    }
}

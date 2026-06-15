<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Notifications\NewOfferReceived;
use App\Support\CurrentVendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct(protected CurrentVendorProfile $current) {}

    /** Vendor sends or replaces the offer for an inquiry. */
    public function store(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeTenant($inquiry);

        abort_unless(
            in_array($inquiry->status, [InquiryStatus::Requested, InquiryStatus::Offered]),
            422,
            'Cannot send an offer on this inquiry.',
        );

        $data = $this->validated($request);

        // Withdraw any existing sent offer before creating a new one.
        $inquiry->offer()?->where('status', OfferStatus::Sent->value)
            ->update(['status' => OfferStatus::Withdrawn->value]);

        $offer = Offer::create([
            ...$data,
            'inquiry_id' => $inquiry->id,
            'status'     => OfferStatus::Sent->value,
        ]);

        $inquiry->update(['status' => InquiryStatus::Offered->value]);

        $inquiry->recordVendorResponse();

        $inquiry->coupleUser?->notify(new NewOfferReceived($inquiry, $offer));

        return back()->with('status', 'offer-sent');
    }

    // -----------------------------------------------------------------------

    private function validated(Request $request): array
    {
        return $request->validate([
            'total_cents'       => ['required', 'integer', 'min:1'],
            'deposit_cents'     => ['nullable', 'integer', 'min:0', 'lte:total_cents'],
            'line_items'        => ['nullable', 'array'],
            'line_items.*.name' => ['required', 'string', 'max:120'],
            'line_items.*.amount_cents' => ['required', 'integer', 'min:0'],
            'line_items.*.qty' => ['nullable', 'integer', 'min:1'],
            'terms'             => ['nullable', 'string', 'max:2000'],
            'valid_until'       => ['nullable', 'date', 'after:today'],
        ]);
    }

    private function authorizeTenant(Inquiry $inquiry): void
    {
        abort_unless($inquiry->vendor_profile_id === $this->current->id(), 403);
    }
}

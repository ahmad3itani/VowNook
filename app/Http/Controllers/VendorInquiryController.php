<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Models\Inquiry;
use App\Models\InquiryMessage;
use App\Models\Offer;
use App\Support\CurrentVendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VendorInquiryController extends Controller
{
    public function __construct(protected CurrentVendorProfile $current) {}

    public function index(): Response
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $inquiries = Inquiry::forVendorProfile($profile->id)
            ->with(['wedding', 'offer'])
            ->latest()
            ->get()
            ->map(fn (Inquiry $i) => [
                'id'               => $i->id,
                'status'           => $i->status?->value,
                'status_label'     => $i->status?->label(),
                'couple_wedding'   => $i->wedding?->name,
                'event_date'       => $i->event_date?->toDateString(),
                'guest_count'      => $i->guest_count,
                'budget_cents'     => $i->budget_cents,
                'has_offer'        => $i->offer !== null,
                'offer_status'     => $i->offer?->status?->value,
                'created_at'       => $i->created_at?->toDateString(),
            ]);

        $stats = [
            'new'      => $inquiries->where('status', InquiryStatus::Requested->value)->count(),
            'offered'  => $inquiries->where('status', InquiryStatus::Offered->value)->count(),
            'accepted' => $inquiries->where('status', InquiryStatus::Accepted->value)->count(),
        ];

        return Inertia::render('vendor/inquiries', [
            'inquiries' => $inquiries,
            'stats'     => $stats,
        ]);
    }

    public function show(Inquiry $inquiry): Response
    {
        $this->authorizeTenant($inquiry);

        $inquiry->load(['wedding', 'vendorService', 'offer', 'messages.sender', 'booking.review']);

        // Mark unread messages from the couple as read.
        $inquiry->messages()
            ->where('sender_user_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $offer = $inquiry->offer;

        return Inertia::render('vendor/inquiry-show', [
            'inquiry' => [
                'id'          => $inquiry->id,
                'status'      => $inquiry->status?->value,
                'status_label' => $inquiry->status?->label(),
                'message'     => $inquiry->message,
                'event_date'  => $inquiry->event_date?->toDateString(),
                'guest_count' => $inquiry->guest_count,
                'budget_cents' => $inquiry->budget_cents,
                'wedding_name' => $inquiry->wedding?->name,
                'service'     => $inquiry->vendorService ? ['name' => $inquiry->vendorService->name] : null,
                'offer'       => $offer ? [
                    'id'            => $offer->id,
                    'total_cents'   => $offer->total_cents,
                    'deposit_cents' => $offer->deposit_cents,
                    'line_items'    => $offer->line_items ?? [],
                    'terms'         => $offer->terms,
                    'valid_until'   => $offer->valid_until?->toDateString(),
                    'status'        => $offer->status?->value,
                    'status_label'  => $offer->status?->label(),
                ] : null,
                'review'      => $inquiry->booking?->review ? [
                    'id'              => $inquiry->booking->review->id,
                    'rating'          => $inquiry->booking->review->rating,
                    'body'            => $inquiry->booking->review->body,
                    'vendor_response' => $inquiry->booking->review->vendor_response,
                ] : null,
                'messages'    => $inquiry->messages->map(fn (InquiryMessage $m) => [
                    'id'          => $m->id,
                    'body'        => $m->body,
                    'is_mine'     => $m->sender_user_id === Auth::id(),
                    'sender_name' => $m->sender?->name,
                    'created_at'  => $m->created_at?->format('M j, g:i a'),
                ])->all(),
            ],
        ]);
    }

    /** Vendor withdraws their sent offer. */
    public function withdrawOffer(Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeTenant($inquiry);

        $offer = $inquiry->offer;
        abort_if($offer === null || $offer->status !== OfferStatus::Sent, 422);

        $offer->update(['status' => OfferStatus::Withdrawn->value]);
        $inquiry->update(['status' => InquiryStatus::Requested->value]);

        return back()->with('status', 'offer-withdrawn');
    }

    // -----------------------------------------------------------------------

    private function authorizeTenant(Inquiry $inquiry): void
    {
        abort_unless($inquiry->vendor_profile_id === $this->current->id(), 403);
    }
}

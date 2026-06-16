<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Support\Payments\StripeService;
use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Enums\VendorProfileStatus;
use App\Enums\VendorStatus;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\InquiryMessage;
use App\Models\Vendor;
use App\Models\VendorProfile;
use App\Notifications\NewInquiryReceived;
use App\Notifications\OfferAccepted;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();
        abort_if($wedding === null, 403, 'No active wedding.');

        $inquiries = Inquiry::forWedding($wedding->id)
            ->with(['vendorProfile', 'offer'])
            ->latest()
            ->get()
            ->map(fn (Inquiry $i) => $this->serializeCard($i));

        return Inertia::render('inquiries/index', [
            'inquiries'   => $inquiries,
            'quote_badge' => Inquiry::offersAwaiting($wedding->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_if($wedding === null, 403, 'No active wedding.');

        $data = $request->validate([
            'vendor_profile_id' => ['required', 'integer', 'exists:vendor_profiles,id'],
            'vendor_service_id'  => ['nullable', 'integer', 'exists:vendor_services,id'],
            'event_date'         => ['nullable', 'date'],
            'guest_count'        => ['nullable', 'integer', 'min:1'],
            'budget_cents'       => ['nullable', 'integer', 'min:0'],
            'message'            => ['required', 'string', 'max:2000'],
        ]);

        // Verify vendor is published and accepting bookings.
        $vendor = VendorProfile::where('id', $data['vendor_profile_id'])
            ->where('status', VendorProfileStatus::Published->value)
            ->where('is_accepting_bookings', true)
            ->firstOrFail();

        // A referenced service must belong to that same vendor — never another's.
        if (! empty($data['vendor_service_id'])
            && ! $vendor->services()->whereKey($data['vendor_service_id'])->exists()) {
            return back()->withErrors(['vendor_service_id' => 'That service is not offered by this vendor.']);
        }

        // One open inquiry per wedding per vendor at a time.
        $existing = Inquiry::forWedding($wedding->id)
            ->where('vendor_profile_id', $vendor->id)
            ->whereIn('status', [InquiryStatus::Requested->value, InquiryStatus::Offered->value])
            ->exists();

        if ($existing) {
            return back()->withErrors(['vendor_profile_id' => 'You already have an open inquiry with this vendor.']);
        }

        $inquiry = Inquiry::create([
            ...$data,
            'wedding_id'    => $wedding->id,
            'couple_user_id' => Auth::id(),
            'status'         => InquiryStatus::Requested->value,
        ]);

        $vendor->user?->notify(new NewInquiryReceived($inquiry));

        return redirect()->route('quotes.show', $inquiry)
            ->with('status', 'inquiry-sent');
    }

    public function show(Inquiry $inquiry): Response
    {
        $this->authorizeCouple($inquiry);

        $inquiry->load(['vendorProfile', 'vendorService', 'offer', 'messages.sender', 'booking.review']);

        // Mark unread messages from the vendor as read.
        $inquiry->messages()
            ->where('sender_user_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('inquiries/show', [
            'inquiry' => $this->serializeFull($inquiry),
        ]);
    }

    /** Couple accepts the vendor's offer → creates booking + CRM vendor row. */
    public function accept(Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeCouple($inquiry);

        DB::transaction(function () use ($inquiry) {
            // Re-read under a row lock so two concurrent accepts can't both pass
            // the status check and create duplicate bookings.
            $inquiry = Inquiry::whereKey($inquiry->id)->lockForUpdate()->firstOrFail();

            abort_unless($inquiry->status === InquiryStatus::Offered, 422, 'No offer to accept.');

            $offer = $inquiry->offer;
            abort_if($offer === null || $offer->status !== OfferStatus::Sent, 422, 'Offer is no longer available.');

            // 1. Transition statuses.
            $offer->update(['status' => OfferStatus::Accepted->value]);
            $inquiry->update(['status' => InquiryStatus::Accepted->value]);

            // 2. Create the booking record.
            $booking = Booking::create([
                'inquiry_id'        => $inquiry->id,
                'offer_id'          => $offer->id,
                'wedding_id'        => $inquiry->wedding_id,
                'vendor_profile_id' => $inquiry->vendor_profile_id,
                'total_cents'       => $offer->total_cents,
                'deposit_cents'     => $offer->deposit_cents,
                'platform_fee_cents' => \App\Support\PlatformFee::for($offer->total_cents),
                'status'            => BookingStatus::PendingPayment->value,
            ]);

            // 3. Bridge: create a CRM vendors row in the couple's wedding so the
            //    booked vendor appears in their planning workspace automatically.
            $vendorProfile = $inquiry->vendorProfile;
            $crmVendor = Vendor::create([
                'wedding_id'       => $inquiry->wedding_id,
                'name'             => $vendorProfile->business_name,
                'category'         => $vendorProfile->category?->value ?? 'other',
                'status'           => VendorStatus::Booked->value,
                'email'            => $vendorProfile->email,
                'phone'            => $vendorProfile->phone,
                'website'          => $vendorProfile->website,
                'cost_cents'       => $offer->total_cents,
                'paid_cents'       => 0,
                'vendor_user_id'   => $vendorProfile->user_id,
            ]);

            // 4. Link the booking back to the CRM row.
            $booking->update(['vendor_id' => $crmVendor->id]);
        });

        $inquiry->refresh()->load(['vendorProfile.user', 'offer']);

        if ($inquiry->offer) {
            $inquiry->vendorProfile?->user?->notify(new OfferAccepted($inquiry, $inquiry->offer));
        }

        // Tell admins a booking just landed on the platform.
        $booking = Booking::where('inquiry_id', $inquiry->id)->latest('id')->first();
        if ($booking) {
            $booking->loadMissing(['vendorProfile', 'wedding']);
            \Illuminate\Support\Facades\Notification::send(
                \App\Models\User::where('is_admin', true)->get(),
                new \App\Notifications\NewBookingPlaced($booking),
            );
        }

        return redirect()->route('quotes.show', $inquiry)
            ->with('status', 'offer-accepted');
    }

    /** Couple declines the vendor's offer. */
    public function decline(Inquiry $inquiry): RedirectResponse
    {
        $this->authorizeCouple($inquiry);

        abort_unless($inquiry->status === InquiryStatus::Offered, 422);

        $offer = $inquiry->offer;
        if ($offer && $offer->status === OfferStatus::Sent) {
            $offer->update(['status' => OfferStatus::Declined->value]);
        }

        $inquiry->update(['status' => InquiryStatus::Declined->value]);

        return redirect()->route('quotes.index')->with('status', 'offer-declined');
    }

    // -----------------------------------------------------------------------

    private function authorizeCouple(Inquiry $inquiry): void
    {
        abort_unless(
            $inquiry->wedding_id === $this->current->id(),
            403,
        );
    }

    private function serializeCard(Inquiry $i): array
    {
        return [
            'id'              => $i->id,
            'vendor_name'     => $i->vendorProfile?->business_name,
            'vendor_slug'     => $i->vendorProfile?->slug,
            'status'          => $i->status?->value,
            'status_label'    => $i->status?->label(),
            'has_offer'       => $i->offer !== null,
            'created_at'      => $i->created_at?->toDateString(),
        ];
    }

    private function serializeFull(Inquiry $i): array
    {
        $offer = $i->offer;
        $booking = $i->booking;

        return [
            'id'                => $i->id,
            'status'            => $i->status?->value,
            'status_label'      => $i->status?->label(),
            'message'           => $i->message,
            'event_date'        => $i->event_date?->toDateString(),
            'guest_count'       => $i->guest_count,
            'budget_cents'      => $i->budget_cents,
            'vendor' => [
                'business_name' => $i->vendorProfile?->business_name,
                'slug'          => $i->vendorProfile?->slug,
                'category_label' => $i->vendorProfile?->category?->label(),
                'logo_url'      => $i->vendorProfile?->logo_path
                    ? route('public.vendor.logo', $i->vendorProfile->slug)
                    : null,
            ],
            'service' => $i->vendorService ? [
                'name' => $i->vendorService->name,
            ] : null,
            'offer' => $offer ? [
                'id'            => $offer->id,
                'total_cents'   => $offer->total_cents,
                'deposit_cents' => $offer->deposit_cents,
                'line_items'    => $offer->line_items ?? [],
                'terms'         => $offer->terms,
                'valid_until'   => $offer->valid_until?->toDateString(),
                'status'        => $offer->status?->value,
                'status_label'  => $offer->status?->label(),
            ] : null,
            'booking' => $booking ? [
                'id'            => $booking->id,
                'total_cents'   => $booking->total_cents,
                'deposit_cents' => $booking->deposit_cents,
                'status'        => $booking->status?->value,
                'status_label'  => $booking->status?->label(),
                // Payments (Phase 4).
                'deposit_due_cents'  => app(StripeService::class)->amountFor($booking, PaymentType::Deposit),
                'balance_due_cents'  => app(StripeService::class)->amountFor($booking, PaymentType::Balance),
                'amount_paid_cents'  => (int) $booking->payments()
                    ->where('status', PaymentStatus::Succeeded->value)
                    ->whereIn('type', [PaymentType::Deposit->value, PaymentType::Balance->value])
                    ->sum('amount_cents'),
                'payments_configured' => app(StripeService::class)->isConfigured(),
                'vendor_can_receive' => (bool) $i->vendorProfile?->stripe_charges_enabled,
            ] : null,
            'review' => $booking?->review ? [
                'rating'          => $booking->review->rating,
                'body'            => $booking->review->body,
                'vendor_response' => $booking->review->vendor_response,
            ] : null,
            'messages' => $i->messages->map(fn (InquiryMessage $m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'sender_id'  => $m->sender_user_id,
                'sender_name' => $m->sender?->name,
                'is_mine'    => $m->sender_user_id === Auth::id(),
                'created_at' => $m->created_at?->format('M j, g:i a'),
                'read_at'    => $m->read_at?->toIso8601String(),
            ])->all(),
            'current_user_id' => Auth::id(),
        ];
    }
}

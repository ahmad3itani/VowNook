<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Support\CurrentWedding;
use App\Support\Payments\StripeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Couple-side payment: deposit then balance for an accepted booking, via hosted
 * Stripe Checkout (destination charge → vendor, application_fee → platform).
 */
class PaymentController extends Controller
{
    public function __construct(
        protected CurrentWedding $current,
        protected StripeService $stripe,
    ) {}

    /** Start a Checkout session for the deposit or the balance. */
    public function checkout(Booking $booking, string $type): Response
    {
        $this->authorizeTenant($booking);

        $paymentType = PaymentType::tryFrom($type);
        abort_unless(in_array($paymentType, [PaymentType::Deposit, PaymentType::Balance], true), 404);

        if (! $this->stripe->isConfigured()) {
            return back()->with('status', 'payments-unavailable');
        }

        $vendor = $booking->vendorProfile;
        if (! $vendor?->stripe_charges_enabled) {
            return back()->with('status', 'vendor-not-ready');
        }

        // Stage guard: deposit only while pending, balance only after the deposit.
        $allowed = $paymentType === PaymentType::Deposit
            ? $booking->status === BookingStatus::PendingPayment
            : $booking->status === BookingStatus::DepositPaid;

        if (! $allowed || $this->stripe->amountFor($booking, $paymentType) <= 0) {
            return back()->with('status', 'payment-not-due');
        }

        $url = $this->stripe->checkoutFor(
            $booking,
            $paymentType,
            route('payments.success', $booking),
            route('payments.cancel', $booking),
        );

        return Inertia::location($url);
    }

    /** Stripe redirects here after a successful checkout (webhook confirms). */
    public function success(Booking $booking): RedirectResponse
    {
        $this->authorizeTenant($booking);

        return redirect()
            ->route('quotes.show', $booking->inquiry_id)
            ->with('status', 'payment-processing');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        $this->authorizeTenant($booking);

        return redirect()
            ->route('quotes.show', $booking->inquiry_id)
            ->with('status', 'payment-cancelled');
    }

    protected function authorizeTenant(Booking $booking): void
    {
        abort_unless($booking->wedding_id === $this->current->id(), 403);
    }
}

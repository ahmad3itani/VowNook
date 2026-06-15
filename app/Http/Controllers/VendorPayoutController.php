<?php

namespace App\Http\Controllers;

use App\Support\CurrentVendorProfile;
use App\Support\Payments\StripeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vendor-side Stripe Connect onboarding: create/continue an Express account so
 * the vendor can receive payouts (minus the platform commission).
 */
class VendorPayoutController extends Controller
{
    public function __construct(
        protected CurrentVendorProfile $current,
        protected StripeService $stripe,
    ) {}

    /** Begin (or resume) Express onboarding — redirects to Stripe. */
    public function connect(): Response
    {
        $vendor = $this->current->get();
        abort_unless($vendor !== null, 403);

        if (! $this->stripe->isConfigured()) {
            return back()->with('status', 'payouts-unavailable');
        }

        $url = $this->stripe->onboardingLink(
            $vendor,
            route('vendor.payouts.return'),
            route('vendor.payouts.refresh'),
        );

        return Inertia::location($url);
    }

    /** Stripe returns here after onboarding — sync the account state. */
    public function return(): RedirectResponse
    {
        $vendor = $this->current->get();

        if ($vendor && $this->stripe->isConfigured()) {
            $this->stripe->syncAccountStatus($vendor);
        }

        return redirect()->route('vendor.dashboard')->with('status', 'payouts-updated');
    }

    /** Stripe sends the vendor here if the onboarding link expired — restart it. */
    public function refresh(): RedirectResponse
    {
        $vendor = $this->current->get();

        if (! $vendor || ! $this->stripe->isConfigured()) {
            return redirect()->route('vendor.dashboard');
        }

        $url = $this->stripe->onboardingLink(
            $vendor,
            route('vendor.payouts.return'),
            route('vendor.payouts.refresh'),
        );

        return redirect()->away($url);
    }
}

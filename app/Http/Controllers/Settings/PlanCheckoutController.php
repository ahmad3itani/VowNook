<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Payments\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts a Stripe Checkout to upgrade the signed-in user's plan. The webhook
 * (StripeService::handleEvent) is the source of truth that flips `plan`.
 */
class PlanCheckoutController extends Controller
{
    public function __construct(protected StripeService $stripe) {}

    public function checkout(Request $request): Response|RedirectResponse
    {
        $data = $request->validate([
            'tier' => ['required', 'in:premium,planner'],
        ]);

        if (! $this->stripe->isConfigured()) {
            return back()->with('status', 'billing-unavailable');
        }

        $user = $request->user();

        // Couples buy the Atelier (premium) tier; planners buy HQ. Block a
        // mismatched purchase (and vendors, who are commission-based).
        $tierAudience = config("plans.tiers.{$data['tier']}.audience");
        $userAudience = $user->isPlanner() ? 'planner' : ($user->isVendor() ? 'vendor' : 'couple');
        abort_unless($tierAudience === $userAudience, 403);

        $url = $this->stripe->planCheckout(
            $user,
            $data['tier'],
            route('plan.edit').'?checkout=success',
            route('plan.edit').'?checkout=cancel',
        );

        return Inertia::location($url);
    }
}

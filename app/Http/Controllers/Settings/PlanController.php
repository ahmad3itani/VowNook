<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only view of the user's subscription plan and the available tiers.
 * Billing wiring (Stripe + other providers) arrives in a later phase.
 */
class PlanController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        // Couples see couple tiers, planners see the planner tier; vendors are
        // commission-based and see no subscription tiers at all.
        $audience = $user->isPlanner() ? 'planner' : 'couple';

        $tiers = $user->isVendor()
            ? collect()
            : collect(config('plans.tiers'))
                ->filter(fn (array $tier) => ($tier['audience'] ?? 'couple') === $audience)
                ->map(fn (array $tier, string $key) => [
                    'key' => $key,
                    'name' => $tier['name'],
                    'price' => $tier['price'],
                    'max_weddings' => $tier['max_weddings'],
                    'max_guests_per_wedding' => $tier['max_guests_per_wedding'],
                    'max_collaborators_per_wedding' => $tier['max_collaborators_per_wedding'],
                    'max_gallery_photos' => $tier['max_gallery_photos'],
                    'features' => $tier['features'] ?? [],
                ])
                ->values();

        return Inertia::render('settings/plan', [
            'current' => $user->planKey(),
            'account_type' => $user->account_type?->value ?? 'couple',
            'stripe_enabled' => filled(config('services.stripe.secret')),
            'tiers' => $tiers,
            'comped_until' => $user->plan_comped_until?->toFormattedDateString(),
            'referral' => [
                'code' => $user->referral_code,
                'url' => url('/register?ref='.$user->referral_code),
                'count' => $user->referrals()->count(),
                'reward_days' => \App\Support\Referrals::REWARD_DAYS,
            ],
        ]);
    }
}

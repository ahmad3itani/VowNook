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

        $tiers = collect(config('plans.tiers'))
            ->map(fn (array $tier, string $key) => [
                'key' => $key,
                'name' => $tier['name'],
                'price' => $tier['price'],
                'max_weddings' => $tier['max_weddings'],
                'max_guests_per_wedding' => $tier['max_guests_per_wedding'],
                'max_collaborators_per_wedding' => $tier['max_collaborators_per_wedding'],
                'max_gallery_photos' => $tier['max_gallery_photos'],
            ])
            ->values();

        return Inertia::render('settings/plan', [
            'current' => $user->plan,
            'tiers' => $tiers,
        ]);
    }
}

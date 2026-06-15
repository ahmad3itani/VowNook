<?php

namespace App\Http\Middleware;

use App\Support\CurrentVendorProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the authenticated vendor's profile into the request-scoped
 * CurrentVendorProfile singleton. No-op for non-vendor accounts.
 */
class SetCurrentVendorProfile
{
    public function __construct(protected CurrentVendorProfile $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Vendors always have a profile; planners may opt into a public
        // marketplace listing (also a VendorProfile, category=planner).
        if ($user && ($user->isVendor() || $user->isPlanner())) {
            $this->current->set($user->vendorProfile);
        }

        return $next($request);
    }
}

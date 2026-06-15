<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Enums\VendorProfileStatus;
use App\Models\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a planner account opt into a public marketplace listing — a VendorProfile
 * (category = planner) reusing the full vendor portfolio/gallery/SEO tooling, so
 * planners get discovered on /marketplace and the /wedding-planners SEO page.
 */
class PlannerListingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isPlanner(), 403);

        if ($user->vendorProfile === null) {
            VendorProfile::create([
                'user_id' => $user->id,
                'business_name' => $user->name,
                'category' => VendorCategory::Planner->value,
                'email' => $user->email,
                'status' => VendorProfileStatus::Draft->value,
            ]);
        }

        return redirect()->route('vendor.profile.edit')->with('status', 'listing-created');
    }
}

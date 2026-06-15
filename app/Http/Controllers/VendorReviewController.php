<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Support\CurrentVendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Vendor side — public response to a couple's review. */
class VendorReviewController extends Controller
{
    public function __construct(protected CurrentVendorProfile $current) {}

    public function respond(Request $request, Review $review): RedirectResponse
    {
        abort_unless($review->vendor_profile_id === $this->current->id(), 403);

        $data = $request->validate([
            'response' => ['required', 'string', 'max:1000'],
        ]);

        $review->update([
            'vendor_response' => $data['response'],
            'vendor_responded_at' => now(),
        ]);

        return back()->with('status', 'review-response-saved');
    }
}

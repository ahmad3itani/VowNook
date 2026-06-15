<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\VendorService;
use App\Support\CurrentVendorProfile;
use App\Support\Payments\StripeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The marketplace vendor's business dashboard (account_type=vendor). Distinct
 * from /vendor-portal, which is the day-of view a couple grants a booked vendor
 * inside one wedding.
 */
class VendorDashboardController extends Controller
{
    public function __construct(protected CurrentVendorProfile $current) {}

    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();

        // Only vendor accounts belong here.
        if (! $user->isVendor()) {
            return redirect()->route('dashboard');
        }

        $profile = $this->current->get();

        $stats = ['services' => 0, 'inquiries' => 0, 'bookings' => 0, 'earnings' => 0];

        if ($profile) {
            $stats['services'] = VendorService::forVendorProfile($profile->id)->count();

            // Open leads needing a response (new requests).
            $stats['inquiries'] = Inquiry::forVendorProfile($profile->id)
                ->where('status', InquiryStatus::Requested->value)
                ->count();

            $stats['bookings'] = Booking::where('vendor_profile_id', $profile->id)->count();

            // Earnings = total of bookings that have been paid (deposit or full).
            $stats['earnings'] = Booking::where('vendor_profile_id', $profile->id)
                ->whereIn('status', [
                    BookingStatus::DepositPaid->value,
                    BookingStatus::PaidInFull->value,
                    BookingStatus::Completed->value,
                ])
                ->sum('total_cents') / 100;
        }

        return Inertia::render('vendor/dashboard', [
            'profile' => $profile ? [
                'id' => $profile->id,
                'business_name' => $profile->business_name,
                'slug' => $profile->slug,
                'category' => $profile->category->label(),
                'status' => $profile->status->value,
                'status_label' => $profile->status->label(),
                'is_published' => $profile->status === VendorProfileStatus::Published,
            ] : null,
            'stats' => $stats,
            'payouts' => [
                'configured' => app(StripeService::class)->isConfigured(),
                'connected' => filled($profile?->stripe_account_id),
                'charges_enabled' => (bool) $profile?->stripe_charges_enabled,
                'details_submitted' => (bool) $profile?->stripe_details_submitted,
            ],
        ]);
    }
}

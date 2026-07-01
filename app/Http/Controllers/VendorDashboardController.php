<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\VendorAvailability;
use App\Models\VendorMedia;
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
        $completeness = null;

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

            // Profile strength — complete listings win more inquiries, so show
            // vendors exactly what's missing (the marketplace-standard meter).
            $photoCount = VendorMedia::forVendorProfile($profile->id)->count();
            $availabilityCount = VendorAvailability::where('vendor_profile_id', $profile->id)->count();
            $stripeConfigured = app(StripeService::class)->isConfigured();

            $items = [
                ['key' => 'logo', 'label' => 'Upload your logo', 'done' => filled($profile->logo_path), 'href' => '/vendor/profile'],
                ['key' => 'cover', 'label' => 'Add a cover photo', 'done' => filled($profile->cover_path), 'href' => '/vendor/profile'],
                ['key' => 'description', 'label' => 'Tell your story (150+ characters)', 'done' => mb_strlen((string) $profile->description) >= 150, 'href' => '/vendor/profile'],
                ['key' => 'photos', 'label' => 'Add 3+ portfolio photos', 'done' => $photoCount >= 3, 'href' => '/vendor/profile'],
                ['key' => 'services', 'label' => 'List at least one package', 'done' => $stats['services'] > 0, 'href' => '/vendor/services'],
                ['key' => 'availability', 'label' => 'Mark your availability', 'done' => $availabilityCount > 0, 'href' => '/vendor/availability'],
                ['key' => 'published', 'label' => 'Submit for review & go live', 'done' => $profile->status === VendorProfileStatus::Published, 'href' => '/vendor/profile'],
            ];

            if ($stripeConfigured) {
                $items[] = ['key' => 'payouts', 'label' => 'Connect payouts', 'done' => (bool) $profile->stripe_charges_enabled, 'href' => '/vendor'];
            }

            $done = count(array_filter($items, fn ($i) => $i['done']));
            $completeness = [
                'pct' => (int) round($done / count($items) * 100),
                'items' => $items,
            ];
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
            'completeness' => $completeness,
            'payouts' => [
                'configured' => app(StripeService::class)->isConfigured(),
                'connected' => filled($profile?->stripe_account_id),
                'charges_enabled' => (bool) $profile?->stripe_charges_enabled,
                'details_submitted' => (bool) $profile?->stripe_details_submitted,
            ],
        ]);
    }
}

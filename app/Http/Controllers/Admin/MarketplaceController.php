<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Offer;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-wide view of marketplace activity: quote requests, offers, and
 * bookings across every couple and vendor.
 */
class MarketplaceController extends Controller
{
    public function index(): Response
    {
        $inquiriesByStatus = Inquiry::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $bookingsByStatus = Booking::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stats = [
            'inquiries' => Inquiry::count(),
            'inquiries_by_status' => $inquiriesByStatus,
            'offers' => Offer::count(),
            'bookings' => Booking::count(),
            'bookings_by_status' => $bookingsByStatus,
            'gmv' => (int) (Booking::where('status', '!=', BookingStatus::Cancelled->value)->sum('total_cents') / 100),
            'platform_fees' => (int) (Booking::where('status', '!=', BookingStatus::Cancelled->value)->sum('platform_fee_cents') / 100),
        ];

        $recentInquiries = Inquiry::with(['wedding:id,name', 'vendorProfile:id,business_name'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (Inquiry $i) => [
                'id' => $i->id,
                'wedding_name' => $i->wedding?->name,
                'vendor_name' => $i->vendorProfile?->business_name,
                'status' => $i->status->label(),
                'created_at' => $i->created_at?->toDateString(),
            ]);

        $recentBookings = Booking::with(['wedding:id,name', 'vendorProfile:id,business_name'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'wedding_name' => $b->wedding?->name,
                'vendor_name' => $b->vendorProfile?->business_name,
                'total' => $b->total_cents / 100,
                'status' => $b->status->label(),
                'created_at' => $b->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/marketplace', [
            'stats' => $stats,
            'recent' => [
                'inquiries' => $recentInquiries,
                'bookings' => $recentBookings,
            ],
        ]);
    }
}

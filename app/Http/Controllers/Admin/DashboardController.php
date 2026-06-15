<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountType;
use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Enums\VendorProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        $usersByType = User::query()
            ->selectRaw('account_type, count(*) as c')
            ->groupBy('account_type')
            ->pluck('c', 'account_type');

        $vendorsByStatus = VendorProfile::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stats = [
            'total_weddings' => Wedding::count(),
            'upcoming_weddings' => Wedding::where('event_date', '>=', $today)->count(),
            'total_users' => User::count(),
            'new_users_30d' => User::where('created_at', '>=', $today->copy()->subDays(30))->count(),
            'couples' => (int) ($usersByType[AccountType::Couple->value] ?? 0),
            'planners' => (int) ($usersByType[AccountType::Planner->value] ?? 0),
            'vendors' => (int) ($usersByType[AccountType::Vendor->value] ?? 0),
            'vendor_profiles' => VendorProfile::count(),
            'vendors_pending' => (int) ($vendorsByStatus[VendorProfileStatus::PendingReview->value] ?? 0),
            'vendors_published' => (int) ($vendorsByStatus[VendorProfileStatus::Published->value] ?? 0),
            'open_inquiries' => Inquiry::whereIn('status', [InquiryStatus::Requested->value, InquiryStatus::Offered->value])->count(),
            'total_bookings' => Booking::count(),
            // Gross merchandise value across non-cancelled bookings, in dollars.
            'gmv' => (int) (Booking::where('status', '!=', BookingStatus::Cancelled->value)->sum('total_cents') / 100),
        ];

        $recentWeddings = Wedding::with('owner:id,name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Wedding $w) => [
                'id' => $w->id,
                'slug' => $w->slug,
                'name' => $w->name,
                'owner_name' => $w->owner?->name,
                'created_at' => $w->created_at?->toDateString(),
            ]);

        $recentUsers = User::latest()
            ->limit(6)
            ->get(['id', 'name', 'email', 'account_type', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'account_type' => $u->account_type->value,
                'created_at' => $u->created_at?->toDateString(),
            ]);

        $recentBookings = Booking::with(['wedding:id,name', 'vendorProfile:id,business_name'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'wedding_name' => $b->wedding?->name,
                'vendor_name' => $b->vendorProfile?->business_name,
                'total' => $b->total_cents / 100,
                'status' => $b->status->value,
                'created_at' => $b->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'recent' => [
                'weddings' => $recentWeddings,
                'users' => $recentUsers,
                'bookings' => $recentBookings,
            ],
        ]);
    }
}

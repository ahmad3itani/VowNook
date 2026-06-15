<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Support\CurrentVendorProfile;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The marketplace vendor's earnings overview — bookings with totals,
 * platform fees, and net payout amounts.
 */
class VendorEarningsController extends Controller
{
    private const PAID_STATUSES = [
        BookingStatus::DepositPaid,
        BookingStatus::PaidInFull,
        BookingStatus::Completed,
    ];

    public function __construct(protected CurrentVendorProfile $current) {}

    public function index(): Response
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $bookings = Booking::where('vendor_profile_id', $profile->id)
            ->with('wedding:id,name,event_date')
            ->latest()
            ->get();

        $paid = $bookings->filter(fn (Booking $b) => in_array($b->status, self::PAID_STATUSES, true));
        $pending = $bookings->where('status', BookingStatus::PendingPayment);

        return Inertia::render('vendor/earnings', [
            'totals' => [
                'earned_cents'   => $paid->sum(fn (Booking $b) => $b->total_cents - $b->platform_fee_cents),
                'pending_cents'  => $pending->sum(fn (Booking $b) => $b->total_cents - $b->platform_fee_cents),
                'fees_cents'     => $paid->sum('platform_fee_cents'),
                'bookings_count' => $bookings->count(),
            ],
            'bookings' => $bookings->map(fn (Booking $b) => [
                'id'                 => $b->id,
                'wedding_name'       => $b->wedding?->name,
                'event_date'         => $b->wedding?->event_date?->toDateString(),
                'total_cents'        => $b->total_cents,
                'deposit_cents'      => $b->deposit_cents,
                'platform_fee_cents' => $b->platform_fee_cents,
                'net_cents'          => $b->total_cents - $b->platform_fee_cents,
                'status'             => $b->status?->value,
                'status_label'       => $b->status?->label(),
                'created_at'         => $b->created_at?->toDateString(),
            ])->values(),
        ]);
    }
}

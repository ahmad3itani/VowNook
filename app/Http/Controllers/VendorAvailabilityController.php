<?php

namespace App\Http\Controllers;

use App\Models\VendorAvailability;
use App\Support\CurrentVendorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The marketplace vendor's availability calendar. A date with no row is
 * available; rows mark dates as booked or blocked.
 */
class VendorAvailabilityController extends Controller
{
    public function __construct(protected CurrentVendorProfile $current) {}

    public function index(Request $request): Response
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        // ?month=YYYY-MM, defaults to the current month.
        $month = $request->query('month');
        $start = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::today()->startOfMonth();

        $entries = VendorAvailability::forVendorProfile($profile->id)
            ->whereBetween('date', [$start->copy()->subMonth(), $start->copy()->addMonths(2)])
            ->get()
            ->map(fn (VendorAvailability $a) => [
                'date'   => $a->date->toDateString(),
                'status' => $a->status,
                'note'   => $a->note,
            ])
            ->values();

        return Inertia::render('vendor/availability', [
            'month'   => $start->format('Y-m'),
            'entries' => $entries,
        ]);
    }

    /** Set or clear the status of a single date. */
    public function update(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $data = $request->validate([
            'date'   => ['required', 'date'],
            'status' => ['required', 'string', 'in:booked,blocked,available'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === 'available') {
            VendorAvailability::forVendorProfile($profile->id)
                ->whereDate('date', $data['date'])
                ->delete();
        } else {
            VendorAvailability::updateOrCreate(
                ['vendor_profile_id' => $profile->id, 'date' => $data['date']],
                ['status' => $data['status'], 'note' => $data['note'] ?? null],
            );
        }

        return back()->with('status', 'availability-updated');
    }
}

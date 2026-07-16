<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * Advances paid bookings to "completed" once the wedding day has passed — the
 * transition nothing else wrote, which left vendor earnings and dashboards
 * reading a status that never occurred. A booking that reached at least
 * deposit-paid and whose event date is in the past represents a delivered
 * service. Cancelled and never-paid (pending) bookings are left untouched.
 */
class CompletePastBookings extends Command
{
    protected $signature = 'bookings:complete';

    protected $description = 'Mark paid bookings whose wedding date has passed as completed.';

    public function handle(): int
    {
        $count = Booking::query()
            ->whereIn('status', [
                BookingStatus::DepositPaid->value,
                BookingStatus::PaidInFull->value,
            ])
            ->whereHas('wedding', fn ($q) => $q->whereDate('event_date', '<', now()->toDateString()))
            ->update(['status' => BookingStatus::Completed->value]);

        $this->info("Marked {$count} booking(s) completed.");

        return self::SUCCESS;
    }
}

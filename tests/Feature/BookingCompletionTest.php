<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `bookings:complete` advances paid bookings to Completed once the wedding day
 * has passed — the lifecycle transition nothing wrote before, which left vendor
 * earnings/dashboards reading a status that never occurred.
 */
class BookingCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_bookings_complete_after_the_event_date(): void
    {
        $pastPaid = Booking::factory()->paidInFull()->create();
        $pastPaid->wedding->update(['event_date' => now()->subDay()]);

        $futurePaid = Booking::factory()->paidInFull()->create();
        $futurePaid->wedding->update(['event_date' => now()->addMonth()]);

        // Never paid (still pending) — even past its date, it isn't "completed".
        $pastPending = Booking::factory()->create();
        $pastPending->wedding->update(['event_date' => now()->subDay()]);

        $this->artisan('bookings:complete')->assertSuccessful();

        $this->assertSame(BookingStatus::Completed, $pastPaid->fresh()->status);
        $this->assertSame(BookingStatus::PaidInFull, $futurePaid->fresh()->status);
        $this->assertSame(BookingStatus::PendingPayment, $pastPending->fresh()->status);
    }
}

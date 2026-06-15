<?php

namespace App\Support;

use App\Models\Wedding;
use App\Notifications\GuestRsvpReminder;
use Illuminate\Support\Facades\Notification;

class GuestReminders
{
    /**
     * Email every still-pending guest who has an email address. Returns the
     * number of reminders sent. Used by the manual button and the scheduler.
     */
    public static function sendFor(Wedding $wedding): int
    {
        $guests = $wedding->guests()
            ->where('rsvp_status', 'pending')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        foreach ($guests as $guest) {
            Notification::route('mail', $guest->email)
                ->notify(new GuestRsvpReminder($wedding, $guest));
        }

        return $guests->count();
    }
}

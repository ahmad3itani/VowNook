<?php

namespace App\Console\Commands;

use App\Models\Wedding;
use App\Support\GuestReminders;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendRsvpReminders extends Command
{
    protected $signature = 'weddings:rsvp-reminders';

    protected $description = 'Auto-remind non-responding guests as the wedding approaches';

    /** Days before the event to fire the automatic reminder. */
    private const REMIND_DAYS_OUT = 21;

    public function handle(): int
    {
        $today = Carbon::today();
        $total = 0;

        Wedding::query()->whereNotNull('event_date')->chunkById(100, function ($weddings) use ($today, &$total) {
            foreach ($weddings as $wedding) {
                $daysOut = (int) $today->diffInDays($wedding->event_date, false);

                if ($daysOut !== self::REMIND_DAYS_OUT) {
                    continue;
                }

                if (($wedding->settings['rsvp_auto_reminded'] ?? false) === true) {
                    continue;
                }

                $total += GuestReminders::sendFor($wedding);

                $settings = $wedding->settings ?? [];
                $settings['rsvp_auto_reminded'] = true;
                $wedding->update(['settings' => $settings]);
            }
        });

        $this->info("Sent {$total} RSVP reminder(s).");

        return self::SUCCESS;
    }
}

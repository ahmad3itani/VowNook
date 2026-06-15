<?php

namespace App\Console\Commands;

use App\Models\Wedding;
use App\Notifications\WeeklyDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendWeeklyDigests extends Command
{
    protected $signature = 'weddings:weekly-digest';

    protected $description = 'Email couples a weekly planning summary (tasks, RSVPs, quotes)';

    public function handle(): int
    {
        $today = Carbon::today();
        $sent = 0;

        Wedding::query()->with('owner')->chunkById(100, function ($weddings) use ($today, &$sent) {
            foreach ($weddings as $wedding) {
                if ($wedding->owner === null) {
                    continue;
                }

                // Skip weddings whose date has already passed.
                if ($wedding->event_date !== null && $wedding->event_date->isPast()) {
                    continue;
                }

                $wedding->owner->notify(new WeeklyDigest([
                    'wedding' => $wedding->name,
                    'open_tasks' => $wedding->tasks()->where('is_complete', false)->count(),
                    'pending_rsvps' => $wedding->guests()->where('rsvp_status', 'pending')->count(),
                    'open_quotes' => \App\Models\Inquiry::offersAwaiting($wedding->id),
                    'days_out' => $wedding->event_date !== null
                        ? (int) $today->diffInDays($wedding->event_date, false)
                        : null,
                ]));
                $sent++;
            }
        });

        $this->info("Sent {$sent} weekly digest(s).");

        return self::SUCCESS;
    }
}

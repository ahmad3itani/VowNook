<?php

namespace App\Console\Commands;

use App\Models\Wedding;
use App\Notifications\WeddingMilestone;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendWeddingMilestones extends Command
{
    protected $signature = 'weddings:milestones';

    protected $description = 'Email couples a countdown milestone at 100 / 30 / 7 / 1 days out';

    /** Days-before-wedding thresholds that trigger a milestone email. */
    private const THRESHOLDS = [100, 30, 7, 1];

    public function handle(): int
    {
        $today = Carbon::today();
        $sent = 0;

        Wedding::query()->whereNotNull('event_date')->with('owner')->chunkById(100, function ($weddings) use ($today, &$sent) {
            foreach ($weddings as $wedding) {
                $daysOut = (int) $today->diffInDays($wedding->event_date, false);

                if (! in_array($daysOut, self::THRESHOLDS, true)) {
                    continue;
                }

                // De-dupe: never send the same milestone twice.
                $already = $wedding->settings['milestones_sent'] ?? [];
                if (in_array($daysOut, $already, true)) {
                    continue;
                }

                $recipients = $this->recipients($wedding);
                foreach ($recipients as $user) {
                    $user->notify(new WeddingMilestone($wedding, $daysOut));
                    $sent++;
                }

                $settings = $wedding->settings ?? [];
                $settings['milestones_sent'] = [...$already, $daysOut];
                $wedding->update(['settings' => $settings]);
            }
        });

        $this->info("Sent {$sent} milestone notification(s).");

        return self::SUCCESS;
    }

    /** Owner + accepted members, de-duplicated. */
    private function recipients(Wedding $wedding)
    {
        return $wedding->members()
            ->wherePivotNotNull('accepted_at')
            ->get()
            ->push($wedding->owner)
            ->filter()
            ->unique('id');
    }
}

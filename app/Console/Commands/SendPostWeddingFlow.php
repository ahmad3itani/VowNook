<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\PromoCode;
use App\Models\Wedding;
use App\Notifications\PostWeddingThankYou;
use App\Notifications\ReviewRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SendPostWeddingFlow extends Command
{
    protected $signature = 'weddings:post-wedding';

    protected $description = 'After the wedding: thank-you + perk + vendor review requests';

    /** Premium days granted as the completion perk. */
    private const PERK_DAYS = 30;

    public function handle(): int
    {
        $today = Carbon::today();
        $sent = 0;

        Wedding::query()
            ->whereNotNull('event_date')
            ->whereDate('event_date', '<', $today)
            ->whereDate('event_date', '>=', $today->copy()->subDays(30))
            ->with('owner')
            ->chunkById(100, function ($weddings) use (&$sent) {
                foreach ($weddings as $wedding) {
                    if (($wedding->settings['post_wedding_sent'] ?? false) === true || $wedding->owner === null) {
                        continue;
                    }

                    // Thank-you + a single-use perk code.
                    $code = $this->makePerkCode();
                    $wedding->owner->notify(new PostWeddingThankYou($wedding->name, $code, self::PERK_DAYS));

                    // Review requests for booked vendors not yet reviewed.
                    $vendorNames = Booking::where('wedding_id', $wedding->id)
                        ->whereDoesntHave('review')
                        ->with('vendorProfile:id,business_name')
                        ->get()
                        ->map(fn (Booking $b) => $b->vendorProfile?->business_name)
                        ->filter()
                        ->unique()
                        ->values();

                    if ($vendorNames->isNotEmpty()) {
                        $wedding->owner->notify(new ReviewRequest($vendorNames->all()));
                    }

                    $settings = $wedding->settings ?? [];
                    $settings['post_wedding_sent'] = true;
                    $wedding->update(['settings' => $settings]);
                    $sent++;
                }
            });

        $this->info("Ran post-wedding flow for {$sent} wedding(s).");

        return self::SUCCESS;
    }

    private function makePerkCode(): string
    {
        $code = 'THANKYOU'.strtoupper(Str::random(6));

        PromoCode::create([
            'code' => $code,
            'kind' => 'comp_plan',
            'plan' => 'premium',
            'duration_days' => self::PERK_DAYS,
            'max_redemptions' => 1,
            'note' => 'Post-wedding thank-you perk.',
        ]);

        return $code;
    }
}

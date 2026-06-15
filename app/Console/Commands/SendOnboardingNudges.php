<?php

namespace App\Console\Commands;

use App\Models\Wedding;
use App\Notifications\OnboardingNudge;
use Illuminate\Console\Command;

class SendOnboardingNudges extends Command
{
    protected $signature = 'couples:onboarding-nudge';

    protected $description = 'Nudge couples who registered but haven’t completed key setup steps';

    public function handle(): int
    {
        $sent = 0;

        Wedding::query()
            ->where('created_at', '<=', now()->subDays(3))
            ->with('owner')
            ->chunkById(100, function ($weddings) use (&$sent) {
                foreach ($weddings as $wedding) {
                    // Only nudge once.
                    if (($wedding->settings['onboarding_nudged'] ?? false) === true) {
                        continue;
                    }

                    $steps = $this->missingSteps($wedding);
                    if ($steps === [] || $wedding->owner === null) {
                        continue;
                    }

                    $wedding->owner->notify(new OnboardingNudge($steps));
                    $sent++;

                    $settings = $wedding->settings ?? [];
                    $settings['onboarding_nudged'] = true;
                    $wedding->update(['settings' => $settings]);
                }
            });

        $this->info("Sent {$sent} onboarding nudge(s).");

        return self::SUCCESS;
    }

    /** @return list<array{label:string,url:string}> */
    private function missingSteps(Wedding $wedding): array
    {
        $steps = [];

        if ($wedding->guests()->count() === 0) {
            $steps[] = ['label' => 'Add your guest list', 'url' => '/guests'];
        }

        $website = $wedding->website;
        if ($website === null || ! $website->is_published) {
            $steps[] = ['label' => 'Build and publish your wedding website', 'url' => '/website'];
        }

        if ($wedding->budgetItems()->count() === 0) {
            $steps[] = ['label' => 'Set up your budget', 'url' => '/budget'];
        }

        return $steps;
    }
}

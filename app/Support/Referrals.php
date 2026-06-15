<?php

namespace App\Support;

use App\Models\User;
use App\Models\Wedding;
use App\Notifications\ReferralRewarded;

class Referrals
{
    /** Days of Premium granted to a referrer when their referral activates. */
    public const REWARD_DAYS = 30;

    /**
     * Reward the referrer when a referred couple completes a qualifying action
     * (here: publishing their wedding website). Idempotent — at most once per
     * referred user.
     */
    public static function rewardForActivation(Wedding $wedding): void
    {
        $referred = $wedding->owner;

        if ($referred === null
            || $referred->referred_by === null
            || $referred->referral_rewarded_at !== null) {
            return;
        }

        $referrer = $referred->referredBy;
        if ($referrer === null || $referrer->isPlanner()) {
            // Still mark as handled so we don't re-check every save.
            $referred->forceFill(['referral_rewarded_at' => now()])->save();

            return;
        }

        PlanComp::grant($referrer, 'premium', self::REWARD_DAYS);
        $referred->forceFill(['referral_rewarded_at' => now()])->save();
        $referrer->notify(new ReferralRewarded($referred->name, self::REWARD_DAYS));
    }
}

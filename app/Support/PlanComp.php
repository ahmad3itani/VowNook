<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Grants a temporary ("comped") plan upgrade. We have no subscription billing
 * yet, so a comp simply sets `users.plan` plus a `plan_comped_until` expiry;
 * a daily command reverts the plan once that passes.
 */
class PlanComp
{
    public static function grant(User $user, string $plan, int $days): void
    {
        // Extend from the later of now or an existing comp, so stacking works.
        $base = $user->plan_comped_until && $user->plan_comped_until->isFuture()
            ? $user->plan_comped_until
            : Carbon::now();

        $user->forceFill([
            'plan' => $plan,
            'plan_comped_until' => $base->copy()->addDays($days),
        ])->save();
    }

    /** Revert any users whose comp has lapsed back to the free tier. */
    public static function expireOverdue(): int
    {
        return User::query()
            ->whereNotNull('plan_comped_until')
            ->where('plan_comped_until', '<', Carbon::now())
            ->update([
                'plan' => config('plans.default'),
                'plan_comped_until' => null,
            ]);
    }
}

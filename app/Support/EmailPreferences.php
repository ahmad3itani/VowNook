<?php

namespace App\Support;

use App\Models\User;

/**
 * CASL-friendly per-category email opt-out. We default every category ON
 * (opt-out model) and let the user (or the signed unsubscribe link) turn any
 * category off. Transactional mail (offers, RSVPs, password resets) is NOT a
 * category here — it always sends regardless of these preferences.
 */
class EmailPreferences
{
    /** category key => human label (shown on the settings + unsubscribe pages). */
    public const CATEGORIES = [
        'product_updates' => 'Product news & announcements',
        'planning_tips'   => 'Planning tips & onboarding nudges',
        'milestones'      => 'Countdown milestones (“100 days to go!”)',
        'digest'          => 'Weekly planning digest',
    ];

    /** @return array<string,bool> normalized prefs, all categories present. */
    public static function forUser(User $user): array
    {
        $stored = $user->email_preferences ?? [];
        $out = [];

        foreach (array_keys(self::CATEGORIES) as $key) {
            // Missing = opted in (default ON).
            $out[$key] = (bool) ($stored[$key] ?? true);
        }

        return $out;
    }

    public static function wants(User $user, string $category): bool
    {
        return self::forUser($user)[$category] ?? true;
    }

    /** Normalize a submitted/partial map to the full set of boolean categories. */
    public static function normalize(array $input): array
    {
        $out = [];

        foreach (array_keys(self::CATEGORIES) as $key) {
            $out[$key] = (bool) ($input[$key] ?? false);
        }

        return $out;
    }
}

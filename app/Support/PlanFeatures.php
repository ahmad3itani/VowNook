<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Resolves which premium capabilities are available on the FREE tier.
 *
 * By default the free tier follows config/plans.php (everything paid is off).
 * Admins can override any feature for the free tier from the console — unlocking
 * a premium tool for every free couple (a "try it free" growth lever) or locking
 * one back down — without touching code. Paid tiers always follow their config.
 */
class PlanFeatures
{
    /** Setting key holding the free-tier override map (featureKey => bool). */
    private const SETTING_KEY = 'free_tier_features';

    /**
     * Every gateable feature, with a label + description for the admin UI.
     *
     * @var array<string, array{label: string, description: string}>
     */
    public const FEATURES = [
        'ai' => [
            'label' => 'AI planning assistant',
            'description' => 'Generate a starter checklist, budget and timeline with AI.',
        ],
        'website_publish' => [
            'label' => 'Publish wedding website',
            'description' => 'Take the couple’s wedding website live for guests (drafts are always free).',
        ],
        'seating' => [
            'label' => 'Seating / floor plan studio',
            'description' => 'Drag-and-drop seating chart, tables and printable place cards.',
        ],
        'registry' => [
            'label' => 'Gift & cash registry',
            'description' => 'Cash funds and gift items with contributions on the wedding site.',
        ],
        'events' => [
            'label' => 'Multiple events & itinerary',
            'description' => 'Add ceremony, reception and more, each with its own RSVP.',
        ],
        'travel' => [
            'label' => 'Travel & hotel blocks',
            'description' => 'Accommodation cards, room blocks and travel notes for guests.',
        ],
        'broadcast' => [
            'label' => 'Guest broadcasts',
            'description' => 'Email all (or a segment of) guests from the workspace.',
        ],
        'save_the_dates' => [
            'label' => 'Save-the-dates & invitations',
            'description' => 'Send branded save-the-dates and invitations with open tracking.',
        ],
        'subdomain' => [
            'label' => 'Custom web address',
            'description' => 'A free name.vownook.com address for the wedding site.',
        ],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::FEATURES);
    }

    /**
     * The admin override map for the free tier (only keys an admin has set).
     *
     * @return array<string, bool>
     */
    public static function freeTierOverrides(): array
    {
        $stored = Setting::get(self::SETTING_KEY, []);

        return is_array($stored) ? $stored : [];
    }

    /** Whether the free tier currently grants a feature (override, else config). */
    public static function freeTierGrants(string $key): bool
    {
        $overrides = self::freeTierOverrides();

        if (array_key_exists($key, $overrides)) {
            return (bool) $overrides[$key];
        }

        return (bool) config("plans.tiers.free.features.{$key}", false);
    }

    /**
     * The effective free-tier feature map (config defaults merged with overrides),
     * for rendering the admin toggles.
     *
     * @return array<string, bool>
     */
    public static function freeTierMap(): array
    {
        $map = [];

        foreach (self::keys() as $key) {
            $map[$key] = self::freeTierGrants($key);
        }

        return $map;
    }

    /** The plan config default for a feature on the free tier (the "normal" state). */
    public static function freeTierDefault(string $key): bool
    {
        return (bool) config("plans.tiers.free.features.{$key}", false);
    }

    /**
     * Persist the admin's free-tier overrides. Only known feature keys are kept,
     * coerced to booleans.
     *
     * @param  array<string, mixed>  $map
     */
    public static function save(array $map): void
    {
        $clean = [];

        foreach (self::keys() as $key) {
            if (array_key_exists($key, $map)) {
                $clean[$key] = (bool) $map[$key];
            }
        }

        Setting::put(self::SETTING_KEY, $clean, 'plans');
    }
}

<?php

namespace App\Support\Budget;

use App\Support\OntarioCities;

/**
 * The deterministic engine behind "bring your budget, we'll make it work" —
 * it splits a couple's total across the usual wedding categories, judges how
 * realistic that total is for their Ontario city + guest count, and maps each
 * category to the marketplace vendor categories used for "fits your budget"
 * matching. No AI, no paid gate: every couple gets this. All figures are
 * ESTIMATES (label them as such in the UI — Competition Act / Ontario CPA).
 */
class BudgetAllocator
{
    /**
     * The standard split. label => [share (0-1), vendor category slugs it maps
     * to]. Shares sum to 1.0; the vendor slugs match the VendorCategory enum so
     * a category's allocation becomes a marketplace price ceiling.
     *
     * @var array<string, array{0: float, 1: list<string>}>
     */
    public const SPLIT = [
        'Venue' => [0.40, ['venue']],
        'Catering & bar' => [0.22, ['catering']],
        'Photography & video' => [0.12, ['photography', 'videography']],
        'Flowers & décor' => [0.08, ['florist']],
        'Attire & beauty' => [0.06, ['attire', 'beauty']],
        'Music & entertainment' => [0.05, ['music']],
        'Cake & desserts' => [0.03, ['bakery']],
        'Officiant & transport' => [0.02, ['officiant', 'transportation']],
        'Planning & extras' => [0.02, ['planner', 'other']],
    ];

    /**
     * Hand-seeded cost multiplier per Ontario city (GTA + destination regions
     * cost more, the north + smaller towns less). Unlisted cities default to 1.0.
     *
     * @var array<string, float>
     */
    private const CITY_INDEX = [
        'toronto' => 1.20, 'oakville' => 1.15, 'niagara-on-the-lake' => 1.15, 'muskoka' => 1.20,
        'vaughan' => 1.12, 'prince-edward-county' => 1.12, 'markham' => 1.10, 'richmond-hill' => 1.10,
        'collingwood' => 1.10, 'mississauga' => 1.08, 'caledon' => 1.08, 'niagara' => 1.08,
        'burlington' => 1.05, 'milton' => 1.05, 'brampton' => 1.05, 'ottawa' => 1.05,
        'newmarket' => 1.02,
        'hamilton' => 1.00, 'kitchener-waterloo' => 1.00, 'cambridge' => 1.00, 'guelph' => 1.00,
        'barrie' => 1.00, 'kingston' => 1.00, 'st-catharines' => 1.00, 'stratford' => 1.00,
        'oshawa' => 0.98, 'whitby' => 0.98,
        'london' => 0.95, 'peterborough' => 0.95, 'orillia' => 0.95, 'kawartha-lakes' => 0.95,
        'brantford' => 0.92, 'belleville' => 0.92,
        'windsor' => 0.90, 'sarnia' => 0.90, 'woodstock' => 0.90, 'thunder-bay' => 0.90,
        'chatham' => 0.88, 'cornwall' => 0.88, 'sudbury' => 0.88, 'north-bay' => 0.88, 'sault-ste-marie' => 0.88,
    ];

    /** Fixed base cost of any wedding (venue minimum, photo, attire) in cents. */
    private const FIXED_CENTS = 800000;

    /** Variable per-guest cost (catering, rentals, favours) in cents. */
    private const PER_GUEST_CENTS = 22000;

    /**
     * Friendly bands for the capture step — the couple picks one or types an
     * exact number. `cents` is the representative amount used when only a band
     * is chosen.
     *
     * @return list<array{key: string, label: string, cents: int}>
     */
    public static function bands(): array
    {
        return [
            ['key' => 'under-15k', 'label' => 'Under $15,000', 'cents' => 1200000],
            ['key' => '15-25k', 'label' => '$15,000 – $25,000', 'cents' => 2000000],
            ['key' => '25-40k', 'label' => '$25,000 – $40,000', 'cents' => 3200000],
            ['key' => '40-60k', 'label' => '$40,000 – $60,000', 'cents' => 5000000],
            ['key' => '60k-plus', 'label' => '$60,000+', 'cents' => 7000000],
        ];
    }

    /** The representative cents for a band key (from bands()), or null if unknown. */
    public static function centsForBand(string $bandKey): ?int
    {
        return collect(self::bands())->firstWhere('key', $bandKey)['cents'] ?? null;
    }

    /** The cost multiplier for a city slug (1.0 for unknown/unlisted). */
    public function cityIndex(?string $citySlug): float
    {
        return $citySlug !== null ? (self::CITY_INDEX[$citySlug] ?? 1.0) : 1.0;
    }

    /**
     * Split a total across the standard categories.
     *
     * @return list<array{label: string, percent: float, amount_cents: int, vendor_categories: list<string>}>
     */
    public function allocate(int $totalCents): array
    {
        $totalCents = max(0, $totalCents);

        $out = [];
        foreach (self::SPLIT as $label => [$share, $categories]) {
            $out[] = [
                'label' => $label,
                'percent' => $share,
                'amount_cents' => (int) round($totalCents * $share),
                'vendor_categories' => $categories,
            ];
        }

        return $out;
    }

    /**
     * Flattens allocate() into a per-vendor-category cap — this is what powers
     * the marketplace "fits your budget" badge. Categories that share a SPLIT
     * bucket (e.g. 'attire' and 'beauty' both live under "Attire & beauty")
     * get the same cap; we don't try to split a shared bucket further.
     *
     * @return array<string, int> vendor category slug => cap in cents
     */
    public function categoryBudgetsFor(int $totalCents): array
    {
        $budgets = [];
        foreach ($this->allocate($totalCents) as $row) {
            foreach ($row['vendor_categories'] as $slug) {
                $budgets[$slug] = $row['amount_cents'];
            }
        }

        return $budgets;
    }

    /**
     * How realistic the total is for this city + guest count.
     *
     * @return array{typical_cents: int, ratio: float, verdict: string, message: string}
     */
    public function realism(int $totalCents, ?string $citySlug, int $guestCount): array
    {
        $place = ($citySlug !== null ? OntarioCities::name($citySlug) : null) ?? 'your area';

        if ($guestCount <= 0) {
            return [
                'typical_cents' => 0,
                'ratio' => 0.0,
                'verdict' => 'unknown',
                'message' => 'Add your guest count and city to see how far your budget goes.',
            ];
        }

        $typical = (int) round((self::FIXED_CENTS + self::PER_GUEST_CENTS * $guestCount) * $this->cityIndex($citySlug));
        $ratio = $typical > 0 ? $totalCents / $typical : 0.0;
        $typicalDollars = '$'.number_format($typical / 100);

        [$verdict, $message] = match (true) {
            $ratio < 0.8 => ['tight', "A wedding this size in {$place} typically runs around {$typicalDollars}. Your budget is on the tighter side — we'll help you prioritise what matters and find vendors that fit."],
            $ratio > 1.25 => ['generous', "You have room to spare for a wedding this size in {$place} (typically around {$typicalDollars}). You can invest more in the parts that matter most to you."],
            default => ['comfortable', "Your budget is a comfortable fit for a wedding this size in {$place} (typically around {$typicalDollars}). Here's how it breaks down."],
        };

        return [
            'typical_cents' => $typical,
            'ratio' => round($ratio, 2),
            'verdict' => $verdict,
            'message' => $message,
        ];
    }
}

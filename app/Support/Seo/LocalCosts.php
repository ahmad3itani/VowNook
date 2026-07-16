<?php

namespace App\Support\Seo;

use App\Enums\VendorCategory;
use App\Support\Budget\BudgetAllocator;

/**
 * Typical Ontario wedding-vendor cost ranges, adjusted per city.
 *
 * This is the unique, data-backed content that turns an otherwise-thin listing
 * page into a genuinely useful local resource — and it targets the highest-
 * intent long-tail queries ("how much do wedding photographers cost in
 * Hamilton"). Baselines are the Ontario market at cost index 1.0 (~100 guests);
 * each city's {@see BudgetAllocator} multiplier scales them up or down.
 *
 * EVERY figure is an ESTIMATE and must be labelled as such wherever shown
 * (Competition Act / Ontario Consumer Protection Act — same rule the budget
 * allocator follows).
 */
class LocalCosts
{
    /**
     * Ontario baseline ranges at city index 1.0, in cents.
     * unit: 'flat' (per wedding) or 'per_guest'. `note` adds honest nuance.
     *
     * @var array<string, array{low:int, high:int, unit:string, note:string}>
     */
    private const BASELINE = [
        'venue' => ['low' => 300000, 'high' => 1200000, 'unit' => 'flat', 'note' => 'rental / site fee — all-inclusive venues that bundle catering run higher'],
        'catering' => ['low' => 9000, 'high' => 18500, 'unit' => 'per_guest', 'note' => 'per plate, before bar and service charges'],
        'photography' => ['low' => 240000, 'high' => 450000, 'unit' => 'flat', 'note' => 'full wedding-day coverage'],
        'videography' => ['low' => 200000, 'high' => 420000, 'unit' => 'flat', 'note' => 'highlight film plus full-day coverage'],
        'florist' => ['low' => 150000, 'high' => 550000, 'unit' => 'flat', 'note' => 'bouquets, ceremony florals and centrepieces'],
        'music' => ['low' => 120000, 'high' => 280000, 'unit' => 'flat', 'note' => 'DJ; live bands typically run $3,500–$8,000'],
        'bakery' => ['low' => 50000, 'high' => 140000, 'unit' => 'flat', 'note' => 'wedding cake or dessert table'],
        'officiant' => ['low' => 30000, 'high' => 90000, 'unit' => 'flat', 'note' => 'ceremony, including rehearsal'],
        'transportation' => ['low' => 60000, 'high' => 160000, 'unit' => 'flat', 'note' => 'limo or shuttle for the day'],
        'attire' => ['low' => 180000, 'high' => 600000, 'unit' => 'flat', 'note' => 'gown or suit, including alterations'],
        'beauty' => ['low' => 35000, 'high' => 90000, 'unit' => 'flat', 'note' => 'hair and makeup for the couple; add per person for the party'],
        'planner' => ['low' => 180000, 'high' => 900000, 'unit' => 'flat', 'note' => 'month-of coordination up to full planning'],
    ];

    /**
     * The typical cost range for a category in a city (null city = Ontario
     * baseline). Returns null for categories without a baseline (e.g. Other).
     *
     * @return array{low_cents:int, high_cents:int, unit:string, note:string, display:string}|null
     */
    public function for(VendorCategory $category, ?string $citySlug = null): ?array
    {
        $base = self::BASELINE[$category->value] ?? null;

        if ($base === null) {
            return null;
        }

        $index = (new BudgetAllocator)->cityIndex($citySlug);
        $step = $base['unit'] === 'per_guest' ? 500 : 5000; // round to $5 / $50

        $low = $this->roundTo((int) round($base['low'] * $index), $step);
        $high = $this->roundTo((int) round($base['high'] * $index), $step);

        return [
            'low_cents' => $low,
            'high_cents' => $high,
            'unit' => $base['unit'],
            'note' => $base['note'],
            'display' => $this->display($low, $high, $base['unit']),
        ];
    }

    /**
     * Cost rows for every priced category — powers the all-categories hub table.
     *
     * @return list<array{category:string, noun:string, low_cents:int, high_cents:int, unit:string, note:string, display:string}>
     */
    public function table(?string $citySlug = null): array
    {
        $rows = [];

        foreach (VendorCategory::seoCases() as $cat) {
            $cost = $this->for($cat, $citySlug);

            if ($cost !== null) {
                $rows[] = ['category' => $cat->seoSlug(), 'noun' => $cat->seoNoun(), ...$cost];
            }
        }

        return $rows;
    }

    /** Plain-text range, e.g. "$2,900–$5,400" or "$95–$220 per guest". */
    public function display(int $lowCents, int $highCents, string $unit): string
    {
        $range = '$'.number_format($lowCents / 100).'–$'.number_format($highCents / 100);

        return $unit === 'per_guest' ? "{$range} per guest" : $range;
    }

    private function roundTo(int $cents, int $step): int
    {
        return (int) (round($cents / $step) * $step);
    }
}

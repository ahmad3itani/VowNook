<?php

namespace App\Support;

/**
 * The Ontario cities that get programmatic SEO pages. Launching quality-gated
 * with the major metros (per the GTM plan) — the list expands as vendor
 * inventory grows. Each entry: slug => [name, region, blurb].
 *
 * `region` matches the vendor_profiles.region value used by MarketplaceCatalog
 * filters; `name` is matched against city/service_area.
 */
class OntarioCities
{
    /** @var array<string, array{name: string, region: string, blurb: string}> */
    private const CITIES = [
        'toronto' => [
            'name' => 'Toronto',
            'region' => 'Ontario',
            'blurb' => "Canada's largest city offers everything from downtown loft venues to lakeside estates, with one of the deepest pools of wedding talent in the country.",
        ],
        'ottawa' => [
            'name' => 'Ottawa',
            'region' => 'Ontario',
            'blurb' => 'The capital pairs historic architecture and riverside settings with a thriving community of bilingual wedding professionals.',
        ],
        'mississauga' => [
            'name' => 'Mississauga',
            'region' => 'Ontario',
            'blurb' => 'Just west of Toronto, Mississauga blends grand banquet halls and waterfront parks with easy access to GTA vendors.',
        ],
        'hamilton' => [
            'name' => 'Hamilton',
            'region' => 'Ontario',
            'blurb' => 'Known for its waterfalls and restored industrial venues, Hamilton has become a favourite for couples wanting character without Toronto prices.',
        ],
        'london' => [
            'name' => 'London',
            'region' => 'Ontario',
            'blurb' => 'Southwestern Ontario\'s hub offers garden estates, downtown galleries and a tight-knit vendor community.',
        ],
        'kitchener-waterloo' => [
            'name' => 'Kitchener-Waterloo',
            'region' => 'Ontario',
            'blurb' => 'The twin cities of Waterloo Region combine rustic country barns with modern urban venues and a growing creative scene.',
        ],
        'niagara' => [
            'name' => 'Niagara',
            'region' => 'Ontario',
            'blurb' => 'Wine-country estates, vineyards and falls-view venues make the Niagara region one of Ontario\'s most sought-after wedding destinations.',
        ],
    ];

    /** @return array<string, array{name: string, region: string, blurb: string}> */
    public static function all(): array
    {
        return self::CITIES;
    }

    public static function exists(string $slug): bool
    {
        return isset(self::CITIES[$slug]);
    }

    /** @return array{name: string, region: string, blurb: string}|null */
    public static function get(string $slug): ?array
    {
        return self::CITIES[$slug] ?? null;
    }

    public static function name(string $slug): ?string
    {
        return self::CITIES[$slug]['name'] ?? null;
    }

    /** Pipe-joined slug pattern for route constraints. */
    public static function slugPattern(): string
    {
        return implode('|', array_keys(self::CITIES));
    }
}

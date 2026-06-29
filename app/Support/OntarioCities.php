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
        'brampton' => [
            'name' => 'Brampton',
            'region' => 'Ontario',
            'blurb' => 'One of the GTA\'s fastest-growing cities, Brampton is known for grand banquet halls and a vibrant multicultural wedding scene.',
        ],
        'markham' => [
            'name' => 'Markham',
            'region' => 'Ontario',
            'blurb' => 'A polished York Region city with upscale banquet venues, golf-club settings and easy reach of Toronto vendors.',
        ],
        'vaughan' => [
            'name' => 'Vaughan',
            'region' => 'Ontario',
            'blurb' => 'Home to some of the GTA\'s largest event venues and estate gardens, just north of Toronto.',
        ],
        'richmond-hill' => [
            'name' => 'Richmond Hill',
            'region' => 'Ontario',
            'blurb' => 'A leafy York Region city with elegant banquet halls, conservation-area settings and strong vendor access.',
        ],
        'newmarket' => [
            'name' => 'Newmarket',
            'region' => 'Ontario',
            'blurb' => 'A charming historic main street and country estates make this north-GTA town a relaxed wedding base.',
        ],
        'oakville' => [
            'name' => 'Oakville',
            'region' => 'Ontario',
            'blurb' => 'Lakefront estates and refined harbourside venues give Oakville an upscale, garden-party feel.',
        ],
        'burlington' => [
            'name' => 'Burlington',
            'region' => 'Ontario',
            'blurb' => 'Between Toronto and Niagara, Burlington offers waterfront halls and escarpment views with a relaxed pace.',
        ],
        'milton' => [
            'name' => 'Milton',
            'region' => 'Ontario',
            'blurb' => 'Escarpment backdrops, country barns and conservation venues draw couples wanting nature close to the GTA.',
        ],
        'caledon' => [
            'name' => 'Caledon',
            'region' => 'Ontario',
            'blurb' => 'Rolling hills, rustic barns and country estates make Caledon a favourite for outdoor and farm weddings.',
        ],
        'oshawa' => [
            'name' => 'Oshawa',
            'region' => 'Ontario',
            'blurb' => 'Durham Region\'s hub blends lakeside parks and classic banquet halls with value-friendly pricing.',
        ],
        'whitby' => [
            'name' => 'Whitby',
            'region' => 'Ontario',
            'blurb' => 'A scenic Durham town with waterfront and heritage venues, popular for relaxed lakeside celebrations.',
        ],
        'hamilton' => [
            'name' => 'Hamilton',
            'region' => 'Ontario',
            'blurb' => 'Known for its waterfalls and restored industrial venues, Hamilton has become a favourite for couples wanting character without Toronto prices.',
        ],
        'niagara' => [
            'name' => 'Niagara',
            'region' => 'Ontario',
            'blurb' => 'Wine-country estates, vineyards and falls-view venues make the Niagara region one of Ontario\'s most sought-after wedding destinations.',
        ],
        'niagara-on-the-lake' => [
            'name' => 'Niagara-on-the-Lake',
            'region' => 'Ontario',
            'blurb' => 'Storybook vineyards, historic inns and lakeside estates make NOTL one of Ontario\'s premier destination-wedding towns.',
        ],
        'st-catharines' => [
            'name' => 'St. Catharines',
            'region' => 'Ontario',
            'blurb' => 'The heart of Niagara wine country, with garden venues, wineries and waterfront settings.',
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
        'cambridge' => [
            'name' => 'Cambridge',
            'region' => 'Ontario',
            'blurb' => 'Limestone mills along the Grand River give Cambridge some of Ontario\'s most romantic heritage venues.',
        ],
        'guelph' => [
            'name' => 'Guelph',
            'region' => 'Ontario',
            'blurb' => 'A pretty university city with stone churches, river gardens and farm-to-table catering talent.',
        ],
        'brantford' => [
            'name' => 'Brantford',
            'region' => 'Ontario',
            'blurb' => 'Grand River settings and country estates make Brantford a relaxed, affordable choice in southwestern Ontario.',
        ],
        'stratford' => [
            'name' => 'Stratford',
            'region' => 'Ontario',
            'blurb' => 'Famous for its festival and riverside charm, Stratford pairs heritage venues with strong culinary vendors.',
        ],
        'woodstock' => [
            'name' => 'Woodstock',
            'region' => 'Ontario',
            'blurb' => 'Country barns and farm estates around Oxford County suit rustic, laid-back celebrations.',
        ],
        'windsor' => [
            'name' => 'Windsor',
            'region' => 'Ontario',
            'blurb' => 'Riverfront views, wineries in nearby Essex County and a warm border-city food scene.',
        ],
        'sarnia' => [
            'name' => 'Sarnia',
            'region' => 'Ontario',
            'blurb' => 'Lake Huron beaches and waterfront venues give Sarnia a breezy, blue-water wedding backdrop.',
        ],
        'chatham' => [
            'name' => 'Chatham-Kent',
            'region' => 'Ontario',
            'blurb' => 'Rural estates and lakeshore venues across Chatham-Kent suit intimate country weddings.',
        ],
        'barrie' => [
            'name' => 'Barrie',
            'region' => 'Ontario',
            'blurb' => 'On the shores of Lake Simcoe, Barrie is the gateway to cottage country with waterfront and resort venues.',
        ],
        'orillia' => [
            'name' => 'Orillia',
            'region' => 'Ontario',
            'blurb' => 'Lakeside between Simcoe and Couchiching, Orillia offers waterfront halls and a cottage-country feel.',
        ],
        'muskoka' => [
            'name' => 'Muskoka',
            'region' => 'Ontario',
            'blurb' => 'Lakeside lodges, boathouses and resort estates make Muskoka Ontario\'s iconic cottage-country wedding destination.',
        ],
        'collingwood' => [
            'name' => 'Collingwood',
            'region' => 'Ontario',
            'blurb' => 'Blue Mountain views and Georgian Bay shoreline make Collingwood a four-season destination-wedding favourite.',
        ],
        'kingston' => [
            'name' => 'Kingston',
            'region' => 'Ontario',
            'blurb' => 'Limestone heritage buildings and waterfront on Lake Ontario give Kingston timeless, historic wedding settings.',
        ],
        'prince-edward-county' => [
            'name' => 'Prince Edward County',
            'region' => 'Ontario',
            'blurb' => 'Wineries, barns and beachside estates have made "the County" one of Ontario\'s trendiest destination-wedding regions.',
        ],
        'belleville' => [
            'name' => 'Belleville',
            'region' => 'Ontario',
            'blurb' => 'A Bay of Quinte city close to Prince Edward County, with waterfront venues and country estates.',
        ],
        'peterborough' => [
            'name' => 'Peterborough',
            'region' => 'Ontario',
            'blurb' => 'Gateway to the Kawartha Lakes, Peterborough offers riverside venues and rustic lakeside settings.',
        ],
        'kawartha-lakes' => [
            'name' => 'Kawartha Lakes',
            'region' => 'Ontario',
            'blurb' => 'Cottage-country lakes, barns and resort venues make the Kawarthas a relaxed waterfront wedding region.',
        ],
        'cornwall' => [
            'name' => 'Cornwall',
            'region' => 'Ontario',
            'blurb' => 'On the St. Lawrence in eastern Ontario, Cornwall blends riverside venues with bilingual vendor talent.',
        ],
        'sudbury' => [
            'name' => 'Sudbury',
            'region' => 'Ontario',
            'blurb' => 'Northern Ontario\'s hub, with lakeside venues, science-centre settings and a close-knit vendor community.',
        ],
        'north-bay' => [
            'name' => 'North Bay',
            'region' => 'Ontario',
            'blurb' => 'On Lake Nipissing, North Bay offers waterfront and resort venues for northern Ontario couples.',
        ],
        'thunder-bay' => [
            'name' => 'Thunder Bay',
            'region' => 'Ontario',
            'blurb' => 'Lake Superior shoreline and the Sleeping Giant backdrop give Thunder Bay dramatic northwestern settings.',
        ],
        'sault-ste-marie' => [
            'name' => 'Sault Ste. Marie',
            'region' => 'Ontario',
            'blurb' => 'Waterfront on the St. Marys River and nearby wilderness suit scenic northern weddings.',
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

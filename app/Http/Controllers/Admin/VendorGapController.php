<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorCategory;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\VendorProfile;
use App\Support\MarketplaceCatalog;
use App\Support\OntarioCities;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The founder's vendor-recruitment priority list: for every (city, vendor
 * category) pair, how many REAL vendors currently serve it versus real
 * couple demand. Cities/categories with the least real supply surface
 * first — that's today's actual blocker (the catalog is still ~100%
 * fictional demo listings).
 */
class VendorGapController extends Controller
{
    /** Deliberate MVP scope limit — the full 44 cities × 13 categories grid
     * (572 rows) is too much for a recruitment list; the founder only needs
     * the top of the queue. */
    private const MAX_ROWS = 80;

    public function index(): Response
    {
        $catalog = app(MarketplaceCatalog::class);

        // One query for every real (non-demo, published, accepting) vendor
        // profile — city/service_area matching happens in PHP against the
        // 44-city list, avoiding a per-(city, category) query.
        $realVendors = VendorProfile::published()
            ->where('is_accepting_bookings', true)
            ->whereHas('user', fn ($q) => $q->where('email', 'not like', '%'.VendorProfile::DEMO_EMAIL_DOMAIN))
            ->get(['id', 'category', 'city', 'service_area']);

        // One query for demand — inquiries grouped by the inquiring wedding's
        // city slug and the vendor's category.
        $demandByKey = Inquiry::query()
            ->join('vendor_profiles', 'vendor_profiles.id', '=', 'inquiries.vendor_profile_id')
            ->join('weddings', 'weddings.id', '=', 'inquiries.wedding_id')
            ->whereNotNull('weddings.city')
            ->selectRaw('weddings.city as city_slug, vendor_profiles.category as category, count(*) as c')
            ->groupBy('weddings.city', 'vendor_profiles.category')
            ->get()
            ->keyBy(fn ($row) => "{$row->city_slug}|{$row->category}");

        $vendorsByCategory = $realVendors->groupBy(fn (VendorProfile $v) => $v->category?->value);

        $rows = [];
        $suppliedCategories = [];
        $citiesWithSupply = [];

        foreach (OntarioCities::all() as $citySlug => $cityInfo) {
            $cityHasSupply = false;

            foreach (VendorCategory::cases() as $category) {
                $candidates = $vendorsByCategory->get($category->value) ?? collect();
                $realSupply = $candidates->filter(fn (VendorProfile $v) => $catalog->cityMatches($v, $cityInfo['name']))->count();

                if ($realSupply > 0) {
                    $suppliedCategories[$category->value] = true;
                    $cityHasSupply = true;
                }

                $demand = (int) ($demandByKey->get("{$citySlug}|{$category->value}")?->c ?? 0);

                $rows[] = [
                    'city' => $citySlug,
                    'city_name' => $cityInfo['name'],
                    'category' => $category->value,
                    'category_label' => $category->label(),
                    'real_supply' => $realSupply,
                    'demand' => $demand,
                ];
            }

            if ($cityHasSupply) {
                $citiesWithSupply[$citySlug] = true;
            }
        }

        usort($rows, function (array $a, array $b) {
            return [$b['demand'], $a['real_supply'], $a['city_name']]
                <=> [$a['demand'], $b['real_supply'], $b['city_name']];
        });

        $rows = array_slice($rows, 0, self::MAX_ROWS);

        $totalCategories = count(VendorCategory::cases());
        $totalCities = count(OntarioCities::all());

        $summary = [
            'total_real_vendors' => $realVendors->count(),
            'total_categories_with_zero_supply' => $totalCategories - count($suppliedCategories),
            'total_cities_with_zero_supply' => $totalCities - count($citiesWithSupply),
        ];

        return Inertia::render('admin/vendor-gaps', [
            'rows' => array_values($rows),
            'summary' => $summary,
        ]);
    }
}

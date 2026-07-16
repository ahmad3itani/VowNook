<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Models\LocalContent;
use App\Models\VendorProfile;
use App\Support\Markdown;
use App\Support\MarketplaceCatalog;
use App\Support\OntarioCities;
use App\Support\Seo;
use App\Support\Seo\LocalCosts;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Programmatic local-SEO pages — the Ontario ranking engine.
 *
 *   /{category}                e.g. /wedding-photographers  (Ontario hub)
 *   /{category}/{city}         e.g. /wedding-photographers/toronto
 *
 * Quality gate: a city page is only indexable once it has >= 3 vendors, so we
 * never ship thin "doorway" pages that Google penalises. Below the threshold a
 * page still renders (so the URL works) but carries noindex.
 */
class PublicLocalController extends Controller
{
    private const INDEX_THRESHOLD = 3;

    public function __construct(protected MarketplaceCatalog $catalog) {}

    /** Category hub across Ontario, linking out to each city. */
    public function category(string $category): Response
    {
        $cat = VendorCategory::fromSeoSlug($category);
        abort_if($cat === null, 404);

        $vendors = $this->catalog->browse(['category' => $cat->value]);
        $noun = $cat->seoNoun();
        $content = LocalContent::forPage($cat->value, null);

        // Per-city vendor counts for the internal-linking grid — counted in PHP
        // from the already-loaded category vendors (one query, not one per city).
        $cities = collect(OntarioCities::all())->map(fn (array $data, string $slug) => [
            'slug' => $slug,
            'name' => $data['name'],
            'count' => $vendors->filter(fn ($p) => $this->catalog->cityMatches($p, $data['name']))->count(),
            'url' => route('local.city-category', [$cat->seoSlug(), $slug]),
        ])->values();

        $schemas = [
            $this->collectionSchema("{$noun} in Ontario", $vendors),
            Seo::breadcrumbs([
                'Marketplace' => route('public.marketplace'),
                $noun => route('local.category', $cat->seoSlug()),
            ]),
        ];
        if ($faq = $this->faqSchema($content?->faqs ?? [])) {
            $schemas[] = $faq;
        }

        $cost = (new LocalCosts)->for($cat, null);

        $seo = Seo::make(
            title: "{$noun} in Ontario",
            description: ($cost
                ? "Compare {$noun} across Ontario — typically {$cost['display']}. "
                : "Compare {$noun} across Ontario. ")
                .'Reviews tied to real bookings, free quotes on '.config('app.name').'.',
            canonical: route('local.category', $cat->seoSlug()),
            // Category hubs are always useful (typical-cost data, a full 42-city
            // directory and a local guide), so always indexable — the thin-content
            // gate lives at the city level below.
            index: true,
            schemas: $schemas,
        );

        return Inertia::render('public/local-category', [
            'category' => ['slug' => $cat->seoSlug(), 'noun' => $noun, 'label' => $cat->label()],
            'vendors' => $vendors->map(fn ($p) => $this->catalog->cardData($p)),
            'cities' => $cities,
            'total' => $vendors->count(),
            'cost' => $cost,
            'other_categories' => $this->otherCategoryLinks($cat, null),
            'intro_html' => $content?->intro ? Markdown::toHtml($content->intro) : null,
            'faqs' => $content?->faqs ?? [],
        ])->withViewData(['seo' => $seo]);
    }

    /** Vendors of a category in a specific Ontario city. */
    public function cityCategory(string $category, string $city): Response
    {
        $cat = VendorCategory::fromSeoSlug($category);
        abort_if($cat === null, 404);

        $cityData = OntarioCities::get($city);
        abort_if($cityData === null, 404);

        $vendors = $this->catalog->browse(['category' => $cat->value, 'city' => $cityData['name']]);
        $count = $vendors->count();
        $noun = $cat->seoNoun();
        $cityName = $cityData['name'];
        $content = LocalContent::forPage($cat->value, $city);

        $priceRange = $this->priceRange($vendors);
        $cost = (new LocalCosts)->for($cat, $city);
        $costDisplay = $cost['display'] ?? null;

        $description = $count > 0
            ? "Compare {$count} ".strtolower($noun)." in {$cityName}, Ontario"
                .($priceRange ? " from {$priceRange}" : '')
                .($costDisplay ? " (typically {$costDisplay})" : '')
                .'. Reviews tied to real bookings, free quotes.'
            : 'Looking for '.strtolower($noun)." in {$cityName}, Ontario? "
                .($costDisplay ? "Typical cost {$costDisplay}. " : '')
                .'Compare local vendors and request quotes for free on '.config('app.name').'.';

        $schemas = [
            $this->collectionSchema("{$noun} in {$cityName}", $vendors),
            Seo::breadcrumbs([
                'Marketplace' => route('public.marketplace'),
                $noun => route('local.category', $cat->seoSlug()),
                $cityName => route('local.city-category', [$cat->seoSlug(), $city]),
            ]),
        ];
        if ($faq = $this->faqSchema($content?->faqs ?? [])) {
            $schemas[] = $faq;
        }

        $seo = Seo::make(
            title: "{$noun} in {$cityName}, Ontario",
            description: $description,
            canonical: route('local.city-category', [$cat->seoSlug(), $city]),
            // Indexable once it clears the vendor gate OR carries a real local guide.
            index: $count >= self::INDEX_THRESHOLD || ($content?->isSubstantial() ?? false),
            schemas: $schemas,
        );

        // Sibling cities (same category) for internal linking.
        $otherCities = collect(OntarioCities::all())
            ->filter(fn ($d, $slug) => $slug !== $city)
            ->map(fn ($d, $slug) => [
                'name' => $d['name'],
                'url' => route('local.city-category', [$cat->seoSlug(), $slug]),
            ])->values();

        return Inertia::render('public/local-city', [
            'category' => ['slug' => $cat->seoSlug(), 'noun' => $noun, 'label' => $cat->label()],
            'city' => ['slug' => $city, 'name' => $cityName, 'blurb' => $cityData['blurb']],
            'vendors' => $vendors->map(fn ($p) => $this->catalog->cardData($p)),
            'total' => $count,
            'price_range' => $priceRange,
            'cost' => $cost,
            'other_cities' => $otherCities,
            'other_categories' => $this->otherCategoryLinks($cat, $city),
            'hub_url' => route('local.category', $cat->seoSlug()),
            'intro_html' => $content?->intro ? Markdown::toHtml($content->intro) : null,
            'faqs' => $content?->faqs ?? [],
        ])->withViewData(['seo' => $seo]);
    }

    /** All-categories hub for the head term: "Wedding Vendors in Ontario". */
    public function allVendors(): Response
    {
        return $this->renderAllVendors(null);
    }

    /** All-categories hub for a single city: "Wedding Vendors in {City}, Ontario". */
    public function allVendorsCity(string $city): Response
    {
        abort_if(OntarioCities::get($city) === null, 404);

        return $this->renderAllVendors($city);
    }

    /** Shared builder for the all-categories hub (Ontario or a city). */
    private function renderAllVendors(?string $citySlug): Response
    {
        $costs = new LocalCosts;
        $cityData = $citySlug !== null ? OntarioCities::get($citySlug) : null;
        $cityName = $cityData['name'] ?? null;
        $place = $cityName !== null ? "{$cityName}, Ontario" : 'Ontario';
        $counts = $this->categoryCounts($cityName);

        $categories = collect(VendorCategory::seoCases())->map(fn (VendorCategory $c) => [
            'slug' => $c->seoSlug(),
            'noun' => $c->seoNoun(),
            'label' => $c->label(),
            'url' => $citySlug !== null
                ? route('local.city-category', [$c->seoSlug(), $citySlug])
                : route('local.category', $c->seoSlug()),
            'cost' => $costs->for($c, $citySlug)['display'] ?? null,
            'count' => $counts[$c->value] ?? 0,
        ])->values();

        // A small cross-category sample of real published vendors as visual proof.
        $vendors = $this->catalog->browse($cityName !== null ? ['city' => $cityName] : [])
            ->take(9)
            ->map(fn ($p) => $this->catalog->cardData($p))
            ->values();

        $cities = collect(OntarioCities::all())
            ->reject(fn ($d, $slug) => $slug === $citySlug)
            ->map(fn ($d, $slug) => ['slug' => $slug, 'name' => $d['name'], 'url' => route('local.all-city', $slug)])
            ->values();

        $title = "Wedding Vendors in {$place}";
        $canonical = $citySlug !== null ? route('local.all-city', $citySlug) : route('local.all');

        $collection = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $title,
            'url' => $canonical,
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $categories->count(),
                'itemListElement' => $categories->map(fn (array $c, int $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => $c['url'],
                    'name' => "{$c['noun']} in {$place}",
                ])->all(),
            ],
        ];

        $schemas = [
            $collection,
            Seo::breadcrumbs(array_filter([
                'Marketplace' => route('public.marketplace'),
                'Wedding Vendors' => route('local.all'),
                (string) $cityName => $citySlug !== null ? route('local.all-city', $citySlug) : null,
            ])),
            Seo::faqSchema(Seo::brandFaqs()),
        ];

        $seo = Seo::make(
            title: $title,
            description: "Find and compare wedding vendors in {$place} — venues, photographers, caterers, florists, planners and more. Reviews tied to real bookings, free quotes on ".config('app.name').'.',
            canonical: $canonical,
            index: true,
            schemas: $schemas,
        );

        return Inertia::render('public/local-vendors', [
            'place' => ['name' => $place, 'city' => $cityName, 'slug' => $citySlug, 'blurb' => $cityData['blurb'] ?? null],
            'categories' => $categories,
            'cities' => $cities,
            'vendors' => $vendors,
            'faqs' => collect(Seo::brandFaqs())->map(fn (array $f) => ['question' => $f['q'], 'answer' => $f['a']])->all(),
            'total_vendors' => array_sum($counts),
        ])->withViewData(['seo' => $seo]);
    }

    // -----------------------------------------------------------------------

    /**
     * Published-vendor counts per category (optionally filtered to a city) in a
     * single grouped query — keeps the all-categories hub to one COUNT query.
     *
     * @return array<string, int>  category value => count
     */
    private function categoryCounts(?string $cityName): array
    {
        $query = VendorProfile::published();

        if ($cityName !== null) {
            $query->where(function ($w) use ($cityName) {
                $w->where('city', 'like', "%{$cityName}%")
                    ->orWhere('service_area', 'like', "%{$cityName}%");
            });
        }

        return $query->selectRaw('category, COUNT(*) as c')
            ->groupBy('category')
            ->pluck('c', 'category')
            ->all();
    }

    /** Links to the same city across other categories (or the hubs when no city). */
    private function otherCategoryLinks(VendorCategory $current, ?string $city): array
    {
        return collect(VendorCategory::seoCases())
            ->reject(fn (VendorCategory $c) => $c === $current)
            ->map(fn (VendorCategory $c) => [
                'noun' => $c->seoNoun(),
                'url' => $city
                    ? route('local.city-category', [$c->seoSlug(), $city])
                    : route('local.category', $c->seoSlug()),
            ])->values()->all();
    }

    /**
     * schema.org FAQPage from stored Q&A. Helps AI engines cite the page (rich
     * results for FAQ are restricted, but the structured data still aids GEO).
     *
     * @param  array<int, array{question:string, answer:string}>  $faqs
     */
    private function faqSchema(array $faqs): ?array
    {
        if ($faqs === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ], $faqs),
        ];
    }

    private function priceRange($vendors): ?string
    {
        $prices = $vendors->pluck('base_price_cents')->filter()->sort()->values();

        if ($prices->isEmpty()) {
            return null;
        }

        return '$'.number_format($prices->first() / 100);
    }

    /** schema.org CollectionPage wrapping an ItemList of the listed vendors. */
    private function collectionSchema(string $name, $vendors): array
    {
        $items = $vendors->values()->map(fn ($p, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => route('public.vendor.show', $p['slug']),
            'name' => $p['business_name'],
        ])->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'url' => url()->current(),
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => count($items),
                'itemListElement' => $items,
            ],
        ];
    }
}

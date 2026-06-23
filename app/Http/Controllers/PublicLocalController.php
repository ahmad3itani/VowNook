<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Models\LocalContent;
use App\Support\Markdown;
use App\Support\MarketplaceCatalog;
use App\Support\OntarioCities;
use App\Support\Seo;
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

        // Per-city vendor counts for the internal-linking grid.
        $cities = collect(OntarioCities::all())->map(function (array $data, string $slug) use ($cat) {
            return [
                'slug' => $slug,
                'name' => $data['name'],
                'count' => $this->catalog->browse(['category' => $cat->value, 'city' => $data['name']])->count(),
                'url' => route('local.city-category', [$cat->seoSlug(), $slug]),
            ];
        })->values();

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

        $seo = Seo::make(
            title: "{$noun} in Ontario",
            description: "Browse and compare {$noun} across Ontario — view portfolios, packages and verified reviews, and request quotes for free on ".config('app.name').'.',
            canonical: route('local.category', $cat->seoSlug()),
            index: $vendors->isNotEmpty(),
            schemas: $schemas,
        );

        return Inertia::render('public/local-category', [
            'category' => ['slug' => $cat->seoSlug(), 'noun' => $noun, 'label' => $cat->label()],
            'vendors' => $vendors->map(fn ($p) => $this->catalog->cardData($p)),
            'cities' => $cities,
            'total' => $vendors->count(),
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

        $description = $count > 0
            ? "Compare {$count} ".strtolower($noun)." in {$cityName}, Ontario"
                .($priceRange ? " starting from {$priceRange}" : '')
                .'. View portfolios and verified reviews, and request quotes for free.'
            : 'Looking for '.strtolower($noun)." in {$cityName}, Ontario? Browse "
                .config('app.name').' and request quotes from trusted local wedding vendors.';

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
            index: $count >= self::INDEX_THRESHOLD,
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
            'other_cities' => $otherCities,
            'other_categories' => $this->otherCategoryLinks($cat, $city),
            'hub_url' => route('local.category', $cat->seoSlug()),
            'intro_html' => $content?->intro ? Markdown::toHtml($content->intro) : null,
            'faqs' => $content?->faqs ?? [],
        ])->withViewData(['seo' => $seo]);
    }

    // -----------------------------------------------------------------------

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

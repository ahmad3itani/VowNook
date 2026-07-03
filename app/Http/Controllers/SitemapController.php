<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Models\BlogPost;
use App\Models\VendorProfile;
use App\Models\WeddingWebsite;
use App\Support\MarketplaceCatalog;
use App\Support\OntarioCities;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Plain XML sitemap for the public surface: home, the marketplace, the
 * programmatic Ontario category + city pages (only those that pass the
 * indexing quality gate), every published vendor profile, and published
 * wedding websites.
 */
class SitemapController extends Controller
{
    /** Mirror PublicLocalController's indexing threshold. */
    private const CITY_INDEX_THRESHOLD = 3;

    public function __construct(protected MarketplaceCatalog $catalog) {}

    public function __invoke(): Response
    {
        $today = now()->toAtomString();

        // Published articles drive the blog index's freshness, so fetch them first.
        $posts = BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'cover_image_path']);

        $urls = [
            ['loc' => url('/'), 'changefreq' => 'weekly', 'lastmod' => $today],
            ['loc' => route('public.marketplace'), 'changefreq' => 'daily', 'lastmod' => $today],
            // Static marketing pages — no meaningful lastmod (change only on deploy).
            ['loc' => url('/how-it-works'), 'changefreq' => 'monthly'],
            ['loc' => url('/pricing'), 'changefreq' => 'monthly'],
            ['loc' => url('/shop'), 'changefreq' => 'weekly'],
            // Shop product pages (static, clean URLs served by ShopController@product).
            ...collect(config('shop.products'))->pluck('slug')->map(
                fn (string $slug) => ['loc' => url("/shop/p/{$slug}"), 'changefreq' => 'monthly'],
            )->all(),
            ['loc' => route('blog.index'), 'changefreq' => 'weekly', 'lastmod' => $posts->max('updated_at')?->toAtomString()],
        ];

        // Published blog articles (cover image attached for Google Images).
        $posts->each(function (BlogPost $post) use (&$urls) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'images' => array_filter([$post->coverUrl()]),
            ];
        });

        // Freshness for the listing pages: the most recent published-vendor update
        // per category (one grouped query). A category hub is "fresh" when a
        // vendor in it changes.
        $categoryFreshness = VendorProfile::published()
            ->selectRaw('category, MAX(updated_at) as max_updated')
            ->groupBy('category')
            ->pluck('max_updated', 'category');

        // Programmatic local-SEO pages: every category hub, plus city pages that
        // clear the vendor-count quality gate (we never list noindex'd thin pages).
        // Vendors are loaded once per category and counted per city in PHP — so
        // this stays one query per category even with dozens of cities.
        foreach (VendorCategory::seoCases() as $category) {
            $catUpdated = $categoryFreshness[$category->value] ?? null;
            $catVendors = $this->catalog->browse(['category' => $category->value]);

            $urls[] = [
                'loc' => route('local.category', $category->seoSlug()),
                'changefreq' => 'weekly',
                'lastmod' => $catUpdated ? Carbon::parse($catUpdated)->toAtomString() : null,
            ];

            foreach (OntarioCities::all() as $citySlug => $city) {
                $cityVendors = $catVendors->filter(fn ($p) => $this->catalog->cityMatches($p, $city['name']));

                if ($cityVendors->count() >= self::CITY_INDEX_THRESHOLD) {
                    $urls[] = [
                        'loc' => route('local.city-category', [$category->seoSlug(), $citySlug]),
                        'changefreq' => 'weekly',
                        'lastmod' => $cityVendors->max('updated_at')?->toAtomString(),
                    ];
                }
            }
        }

        VendorProfile::query()
            ->published()
            ->with('media:id,vendor_profile_id')
            ->orderBy('slug')
            ->get()
            ->each(function (VendorProfile $profile) use (&$urls) {
                $images = [];
                if ($profile->cover_path) {
                    $images[] = route('public.vendor.cover', $profile->slug);
                }
                foreach ($profile->media as $m) {
                    $images[] = route('public.vendor.media', [$profile->slug, $m->id]);
                }

                $urls[] = [
                    'loc' => route('public.vendor.show', $profile->slug),
                    'lastmod' => $profile->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'images' => $images,
                ];
            });

        WeddingWebsite::query()
            ->where('is_published', true)
            ->with('wedding:id,slug')
            ->get()
            ->each(function (WeddingWebsite $website) use (&$urls) {
                if ($website->wedding === null) {
                    return;
                }

                $urls[] = [
                    'loc' => route('public.website', $website->wedding->slug),
                    'lastmod' => $website->updated_at?->toAtomString(),
                    'changefreq' => 'monthly',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
            .'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($url['loc'])."</loc>\n";

            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.e($url['lastmod'])."</lastmod>\n";
            }

            $xml .= '    <changefreq>'.$url['changefreq']."</changefreq>\n";

            foreach ($url['images'] ?? [] as $image) {
                $xml .= '    <image:image><image:loc>'.e($image)."</image:loc></image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml)->header('Content-Type', 'application/xml');
    }
}

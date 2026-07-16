<?php

namespace App\Support;

use App\Enums\VendorProfileStatus;
use App\Models\Review;
use App\Models\VendorMedia;
use App\Models\VendorProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Single source of truth for marketplace discovery — used by both the public
 * controllers (no auth) and the in-portal couple browse controller. Keeps the
 * published-vendor query + serialization in one place so the public and
 * in-portal experiences never drift apart.
 */
class MarketplaceCatalog
{
    /**
     * Published vendors matching the given filters, each with one thumbnail
     * media row eager-loaded and a services count.
     *
     * @param  array<string, mixed>  $filters  category|city|region|min_price|max_price
     * @param  string|null  $prioritizeCityName  optional soft "near you" personalization signal — the
     *                                            couple's own city display name (e.g. "Toronto"). When set, vendors
     *                                            whose city/service_area match it are sorted ahead of otherwise-equal
     *                                            vendors, but never ahead of the founding/featured promotion tiers.
     *                                            Left null (the default), ordering is byte-for-byte unchanged from
     *                                            before this parameter existed — required for the public marketplace.
     * @return Collection<int, VendorProfile>
     */
    public function browse(array $filters, ?string $prioritizeCityName = null): Collection
    {
        $query = VendorProfile::published()
            ->with(['media' => fn ($q) => $q->orderBy('sort_order')->limit(1), 'user:id,email'])
            ->withCount('services');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['city'])) {
            $city = $filters['city'];
            $query->where(function ($q) use ($city) {
                $q->where('city', 'like', "%{$city}%")
                    ->orWhere('service_area', 'like', "%{$city}%");
            });
        }

        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->where('base_price_cents', '>=', (int) $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->where('base_price_cents', '<=', (int) $filters['max_price']);
        }

        // Vendors who can't take a booking right now sink to the bottom — a
        // couple browsing shouldn't be led to inquire with someone who can't
        // respond. Ranking within each group (accepting vs. not) is unchanged.
        // Founding & currently-featured vendors surface first — that paid/earned
        // placement is never overridden by the "near you" personalization tier,
        // which only breaks ties among otherwise-equal vendors below it.
        return $query
            ->orderByDesc('is_accepting_bookings')
            ->orderByRaw('CASE WHEN featured_until IS NOT NULL AND featured_until > ? THEN 1 ELSE 0 END DESC', [now()])
            ->orderByDesc('is_founding')
            ->when($prioritizeCityName !== null, fn ($q) => $q->orderByRaw(
                'CASE WHEN city LIKE ? OR service_area LIKE ? THEN 1 ELSE 0 END DESC',
                ["%{$prioritizeCityName}%", "%{$prioritizeCityName}%"],
            ))
            ->orderByDesc('rating_avg')
            ->orderBy('business_name')
            ->get();
    }

    /**
     * Whether a vendor serves a city — mirrors the `browse()` city filter
     * (city OR service_area contains the name) so callers can count per-city in
     * PHP from one already-loaded collection instead of a query per city. This
     * is what keeps the category-hub and sitemap fast as the city list grows.
     */
    public function cityMatches(VendorProfile $p, string $city): bool
    {
        $needle = mb_strtolower($city);

        return str_contains(mb_strtolower((string) $p->city), $needle)
            || str_contains(mb_strtolower((string) $p->service_area), $needle);
    }

    /** Find a single published profile by slug (404 if not published). */
    public function findPublished(string $slug): VendorProfile
    {
        return VendorProfile::where('slug', $slug)
            ->where('status', VendorProfileStatus::Published->value)
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'media', 'user:id,email'])
            ->firstOrFail();
    }

    /**
     * Compact card representation for the browse grid.
     *
     * @param  array{category_budgets?: array<string, int>, city_name?: string|null}  $context  optional
     *         personalization context (couple's own budget caps + city). Left empty (the default), the
     *         output is byte-for-byte unchanged from before context existed — required for the public
     *         marketplace, which never passes it.
     */
    public function cardData(VendorProfile $p, array $context = []): array
    {
        /** @var VendorMedia|null $thumb */
        $thumb = $p->media->first();

        $data = [
            'id' => $p->id,
            'slug' => $p->slug,
            'business_name' => $p->business_name,
            'category' => $p->category?->value,
            'category_label' => $p->category?->label(),
            'tagline' => $p->tagline,
            'city' => $p->city,
            'region' => $p->region,
            'base_price_cents' => $p->base_price_cents,
            'price_unit' => $p->price_unit,
            'rating_avg' => (float) $p->rating_avg,
            'rating_count' => $p->rating_count,
            'response_hours' => $this->responseHours($p),
            'services_count' => $p->services_count,
            'is_accepting_bookings' => $p->is_accepting_bookings,
            'is_demo' => $p->is_demo,
            'is_founding' => (bool) $p->is_founding,
            'is_featured' => $p->featured_until !== null && $p->featured_until->isFuture(),
            'is_verified' => $p->verified_at !== null,
            'thumb_url' => $thumb ? route('public.vendor.media', [$p->slug, $thumb->id]) : null,
        ];

        return array_merge($data, $this->personalizationFields($p, $context));
    }

    /**
     * Full profile representation for the vendor detail page.
     *
     * @param  array{category_budgets?: array<string, int>, city_name?: string|null}  $context  see cardData()
     */
    public function profileData(VendorProfile $p, array $context = []): array
    {
        return array_merge([
            'id' => $p->id,
            'slug' => $p->slug,
            'business_name' => $p->business_name,
            'category' => $p->category?->value,
            'category_label' => $p->category?->label(),
            'tagline' => $p->tagline,
            'description' => $p->description,
            'city' => $p->city,
            'region' => $p->region,
            'country' => $p->country,
            'service_area' => $p->service_area,
            'base_price_cents' => $p->base_price_cents,
            'price_unit' => $p->price_unit,
            'website' => $p->website,
            'video_url' => $p->video_url,
            'brochure_url' => $p->brochure_path && Storage::exists($p->brochure_path)
                ? route('public.vendor.brochure', $p->slug)
                : null,
            'phone' => $p->phone,
            'email' => $p->email,
            'socials' => $p->socials ?? [],
            'rating_avg' => (float) $p->rating_avg,
            'rating_count' => $p->rating_count,
            'response_hours' => $this->responseHours($p),
            'is_accepting_bookings' => $p->is_accepting_bookings,
            'is_demo' => $p->is_demo,
            'is_verified' => $p->verified_at !== null,
            'logo_url' => $p->logo_path && Storage::exists($p->logo_path)
                ? route('public.vendor.logo', $p->slug)
                : null,
            'cover_url' => $p->cover_path && Storage::exists($p->cover_path)
                ? route('public.vendor.cover', $p->slug)
                : null,
            'media' => $p->media->sortBy('sort_order')->map(fn (VendorMedia $m) => [
                'id' => $m->id,
                'url' => route('public.vendor.media', [$p->slug, $m->id]),
                'caption' => $m->caption,
                'alt' => $m->alt_text ?: $m->caption ?: $p->business_name,
            ])->values()->all(),
            'services' => $p->services->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'price_cents' => $s->price_cents,
                'price_unit' => $s->price_unit,
                'price_type' => $s->price_type,
            ])->all(),
            'reviews' => $p->reviews()
                ->with('coupleUser')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Review $r) => [
                    'id' => $r->id,
                    'rating' => $r->rating,
                    'body' => $r->body,
                    'vendor_response' => $r->vendor_response,
                    'author' => $r->authorDisplayName(),
                    'created_at' => $r->created_at?->toDateString(),
                ])->all(),
        ], $this->personalizationFields($p, $context));
    }

    /**
     * The "fits your budget" / "near you" personalization fields shared by
     * cardData() and profileData(). Keys are OMITTED entirely (not set to
     * false/null) when not computable from the given context, so an empty
     * context — the public marketplace's default — leaves callers with no new
     * keys at all rather than false placeholders.
     *
     * @param  array{category_budgets?: array<string, int>, city_name?: string|null}  $context
     * @return array{fits_budget?: bool, near_you?: bool}
     */
    private function personalizationFields(VendorProfile $p, array $context): array
    {
        $fields = [];

        $cap = $context['category_budgets'][$p->category?->value] ?? null;
        if ($cap !== null && $p->base_price_cents !== null) {
            $fields['fits_budget'] = $p->base_price_cents <= $cap;
        }

        if (! empty($context['city_name'])) {
            $fields['near_you'] = $this->cityMatches($p, $context['city_name']);
        }

        return $fields;
    }

    /**
     * Median first-response time in hours — only shown once the vendor has
     * answered enough inquiries for the number to mean something.
     */
    private function responseHours(VendorProfile $p): ?int
    {
        return $p->response_count >= 3 ? $p->response_hours : null;
    }

    /** Service options for an inquiry form's service dropdown. */
    public function serviceOptions(VendorProfile $p): array
    {
        return $p->services->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
        ])->all();
    }

    /** schema.org LocalBusiness JSON-LD for SEO on the public profile. */
    public function jsonLd(VendorProfile $p): array
    {
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $p->business_name,
            'url' => url("/marketplace/{$p->slug}"),
        ];

        if ($p->tagline) {
            $ld['description'] = $p->tagline;
        }

        if ($p->city || $p->region) {
            $ld['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => $p->city,
                'addressRegion' => $p->region,
                'addressCountry' => $p->country ?? 'CA',
            ];
        }

        if ($p->phone) {
            $ld['telephone'] = $p->phone;
        }

        // Cover + gallery images help Google understand and show the listing.
        $images = [];
        if ($p->cover_path && Storage::exists($p->cover_path)) {
            $images[] = route('public.vendor.cover', $p->slug);
        }
        foreach ($p->media as $m) {
            $images[] = route('public.vendor.media', [$p->slug, $m->id]);
        }
        if ($images !== []) {
            $ld['image'] = $images;
        }

        // sameAs: website + social profiles.
        $sameAs = array_values(array_filter([
            $p->website,
            $p->socials['instagram'] ?? null,
            $p->socials['facebook'] ?? null,
            $p->socials['tiktok'] ?? null,
        ]));
        if ($sameAs !== []) {
            $ld['sameAs'] = $sameAs;
        }

        if ($p->base_price_cents) {
            $ld['priceRange'] = '$'.number_format($p->base_price_cents / 100, 0).'+';
        }

        if ($p->rating_count > 0) {
            $ld['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $p->rating_avg,
                'reviewCount' => $p->rating_count,
            ];
        }

        return $ld;
    }
}

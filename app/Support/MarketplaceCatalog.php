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
     * @return Collection<int, VendorProfile>
     */
    public function browse(array $filters): Collection
    {
        $query = VendorProfile::published()
            ->with(['media' => fn ($q) => $q->orderBy('sort_order')->limit(1)])
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

        // Founding & currently-featured vendors surface first.
        return $query
            ->orderByRaw('CASE WHEN featured_until IS NOT NULL AND featured_until > ? THEN 1 ELSE 0 END DESC', [now()])
            ->orderByDesc('is_founding')
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
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'media'])
            ->firstOrFail();
    }

    /** Compact card representation for the browse grid. */
    public function cardData(VendorProfile $p): array
    {
        /** @var VendorMedia|null $thumb */
        $thumb = $p->media->first();

        return [
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
            'is_founding' => (bool) $p->is_founding,
            'is_featured' => $p->featured_until !== null && $p->featured_until->isFuture(),
            'is_verified' => $p->verified_at !== null,
            'thumb_url' => $thumb ? route('public.vendor.media', [$p->slug, $thumb->id]) : null,
        ];
    }

    /** Full profile representation for the vendor detail page. */
    public function profileData(VendorProfile $p): array
    {
        return [
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
        ];
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

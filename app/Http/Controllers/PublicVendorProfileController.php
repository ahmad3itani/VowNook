<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Inquiry;
use App\Models\VendorMedia;
use App\Models\VendorProfile;
use App\Support\CurrentWedding;
use App\Support\MarketplaceCatalog;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicVendorProfileController extends Controller
{
    public function __construct(
        protected CurrentWedding $wedding,
        protected MarketplaceCatalog $catalog,
    ) {}

    public function show(string $slug): Response
    {
        $profile = $this->catalog->findPublished($slug);

        // Pass auth context so the page can show an inquiry form for couples.
        $user = Auth::user();
        $isCouple = $user && !$user->isVendor() && !$user->is_admin;
        $weddingId = $this->wedding->id();

        $existingInquiry = null;
        if ($isCouple && $weddingId) {
            $existingInquiry = Inquiry::where('wedding_id', $weddingId)
                ->where('vendor_profile_id', $profile->id)
                ->whereIn('status', [InquiryStatus::Requested->value, InquiryStatus::Offered->value])
                ->first();
        }

        return Inertia::render('public/vendor-profile', [
            'profile'           => $this->catalog->profileData($profile),
            'auth_context'      => [
                'is_couple'         => $isCouple,
                'has_wedding'       => (bool) $weddingId,
                'existing_inquiry'  => $existingInquiry ? $existingInquiry->id : null,
            ],
            'services_for_select' => $this->catalog->serviceOptions($profile),
        ])->withViewData(['seo' => $this->seoFor($profile)]);
    }

    /** Server-rendered SEO + LocalBusiness/Breadcrumb structured data for a vendor. */
    private function seoFor(VendorProfile $p): array
    {
        $location = collect([$p->city, $p->region])->filter()->implode(', ');
        $category = $p->category?->label();

        $title = $p->business_name
            .($category ? ' · '.$category : '')
            .($location ? ' in '.$location : ' in Ontario');

        $description = $p->tagline
            ?: Str::limit(strip_tags((string) $p->description), 155)
            ?: "{$p->business_name} — a wedding ".strtolower($category ?? 'vendor')
                .($location ? " in {$location}, Ontario" : ' in Ontario')
                .'. View packages, photos and reviews, and request a quote.';

        $image = $p->cover_path
            ? route('public.vendor.cover', $p->slug)
            : ($p->logo_path ? route('public.vendor.logo', $p->slug) : null);

        return Seo::make(
            title: $title,
            description: $description,
            canonical: route('public.vendor.show', $p->slug),
            image: $image,
            type: 'profile',
            schemas: [
                $this->catalog->jsonLd($p),
                Seo::breadcrumbs([
                    'Marketplace' => route('public.marketplace'),
                    $p->business_name => route('public.vendor.show', $p->slug),
                ]),
            ],
        );
    }

    public function serveMedia(string $slug, VendorMedia $media): StreamedResponse
    {
        $profile = VendorProfile::where('slug', $slug)
            ->where('status', VendorProfileStatus::Published->value)
            ->firstOrFail();

        abort_unless($media->vendor_profile_id === $profile->id, 404);
        abort_unless(Storage::exists($media->path), 404);

        return Storage::response($media->path, $media->original_name, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function serveLogo(string $slug): StreamedResponse
    {
        $profile = VendorProfile::where('slug', $slug)
            ->where('status', VendorProfileStatus::Published->value)
            ->firstOrFail();

        abort_if(blank($profile->logo_path), 404);
        abort_unless(Storage::exists($profile->logo_path), 404);

        return Storage::response($profile->logo_path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function serveCover(string $slug): StreamedResponse
    {
        $profile = VendorProfile::where('slug', $slug)
            ->where('status', VendorProfileStatus::Published->value)
            ->firstOrFail();

        abort_if(blank($profile->cover_path), 404);
        abort_unless(Storage::exists($profile->cover_path), 404);

        return Storage::response($profile->cover_path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function serveBrochure(string $slug): StreamedResponse
    {
        $profile = VendorProfile::where('slug', $slug)
            ->where('status', VendorProfileStatus::Published->value)
            ->firstOrFail();

        abort_if(blank($profile->brochure_path), 404);
        abort_unless(Storage::exists($profile->brochure_path), 404);

        return Storage::response($profile->brochure_path, Str::slug($profile->business_name).'-brochure.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

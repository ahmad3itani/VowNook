<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Enums\VendorProfileStatus;
use App\Models\VendorMedia;
use App\Support\CurrentVendorProfile;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorProfileController extends Controller
{
    public function __construct(protected CurrentVendorProfile $current) {}

    public function edit(): Response
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $profile->load(['media']);

        return Inertia::render('vendor/profile', [
            'profile' => $this->serializeProfile($profile),
            'categories' => collect(VendorCategory::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category' => ['required', 'string', 'in:' . implode(',', VendorCategory::values())],
            'city' => ['nullable', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:2'],
            'service_area' => ['nullable', 'string', 'max:160'],
            'base_price_cents' => ['nullable', 'integer', 'min:0'],
            'price_unit' => ['nullable', 'string', 'in:per_event,per_hour,per_person'],
            'website' => ['nullable', 'url', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'socials' => ['nullable', 'array'],
            'socials.instagram' => ['nullable', 'string', 'max:255'],
            'socials.facebook' => ['nullable', 'string', 'max:255'],
            'socials.tiktok' => ['nullable', 'string', 'max:255'],
            'is_accepting_bookings' => ['boolean'],
        ]);

        // Regenerate slug if business name changed
        if ($data['business_name'] !== $profile->business_name) {
            $profile->slug = \App\Models\VendorProfile::uniqueSlug($data['business_name']);
        }

        $profile->fill($data)->save();

        return back()->with('status', 'profile-updated');
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $request->validate(['logo' => ['required', 'image', 'max:5120']]);

        if ($profile->logo_path) {
            Storage::delete($profile->logo_path);
        }

        $path = ImageOptimizer::store($request->file('logo'), "vendor-profiles/{$profile->id}/logo", 800);
        $profile->update(['logo_path' => $path]);

        return back()->with('status', 'logo-updated');
    }

    public function uploadCover(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $request->validate(['cover' => ['required', 'image', 'max:10240']]);

        if ($profile->cover_path) {
            Storage::delete($profile->cover_path);
        }

        $path = ImageOptimizer::store($request->file('cover'), "vendor-profiles/{$profile->id}/cover", 2400);
        $profile->update(['cover_path' => $path]);

        return back()->with('status', 'cover-updated');
    }

    public function uploadMedia(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $request->validate([
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => ['image', 'max:10240'],
        ]);

        $order = $profile->media()->max('sort_order') ?? 0;

        foreach ($request->file('photos') as $file) {
            $path = ImageOptimizer::store($file, "vendor-profiles/{$profile->id}/gallery", 2000);

            VendorMedia::create([
                'vendor_profile_id' => $profile->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => Storage::mimeType($path) ?: $file->getMimeType(),
                'size' => Storage::size($path),
                'sort_order' => ++$order,
            ]);
        }

        return back()->with('status', 'media-uploaded');
    }

    /** Edit a gallery image's caption + SEO alt text. */
    public function updateMedia(Request $request, VendorMedia $media): RedirectResponse
    {
        $this->authorizeTenant($media);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($data);

        return back()->with('status', 'media-updated');
    }

    /** Persist a new gallery order from drag-and-drop. */
    public function reorderMedia(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($data['items'] as $item) {
            VendorMedia::where('id', $item['id'])
                ->where('vendor_profile_id', $profile->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('status', 'media-reordered');
    }

    public function destroyMedia(VendorMedia $media): RedirectResponse
    {
        $this->authorizeTenant($media);

        Storage::delete($media->path);
        $media->delete();

        return back()->with('status', 'media-deleted');
    }

    public function uploadBrochure(Request $request): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        $request->validate(['brochure' => ['required', 'file', 'mimetypes:application/pdf', 'max:10240']]);

        if ($profile->brochure_path) {
            Storage::delete($profile->brochure_path);
        }

        $path = $request->file('brochure')->store("vendor-profiles/{$profile->id}/brochure");
        $profile->update(['brochure_path' => $path]);

        return back()->with('status', 'brochure-uploaded');
    }

    public function removeBrochure(): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);

        if ($profile->brochure_path) {
            Storage::delete($profile->brochure_path);
        }
        $profile->update(['brochure_path' => null]);

        return back()->with('status', 'brochure-removed');
    }

    public function serveLogo(): StreamedResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null || blank($profile->logo_path), 404);
        abort_unless(Storage::exists($profile->logo_path), 404);

        return Storage::response($profile->logo_path);
    }

    public function serveCover(): StreamedResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null || blank($profile->cover_path), 404);
        abort_unless(Storage::exists($profile->cover_path), 404);

        return Storage::response($profile->cover_path);
    }

    public function serveMediaFile(VendorMedia $media): StreamedResponse
    {
        $this->authorizeTenant($media);
        abort_unless(Storage::exists($media->path), 404);

        return Storage::response($media->path, $media->original_name, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function serveBrochure(): StreamedResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null || blank($profile->brochure_path), 404);
        abort_unless(Storage::exists($profile->brochure_path), 404);

        return Storage::response($profile->brochure_path, 'brochure.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function submit(): RedirectResponse
    {
        $profile = $this->current->get();
        abort_if($profile === null, 403);
        abort_unless($profile->status === VendorProfileStatus::Draft, 422);

        // Submitting = accepting the Vendor Agreement (recorded for trust/audit).
        $profile->update([
            'status' => VendorProfileStatus::PendingReview->value,
            'agreement_accepted_at' => now(),
        ]);

        // Notify admins so the moderation queue gets actioned promptly.
        \Illuminate\Support\Facades\Notification::send(
            \App\Models\User::where('is_admin', true)->get(),
            new \App\Notifications\VendorSubmittedForReview($profile),
        );

        return back()->with('status', 'submitted-for-review');
    }

    // -----------------------------------------------------------------------

    private function authorizeTenant(VendorMedia $media): void
    {
        abort_unless($media->vendor_profile_id === $this->current->id(), 404);
    }

    private function serializeProfile(\App\Models\VendorProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'business_name' => $profile->business_name,
            'slug' => $profile->slug,
            'category' => $profile->category?->value,
            'tagline' => $profile->tagline,
            'description' => $profile->description,
            'logo_url' => $profile->logo_path ? route('vendor.profile.logo') : null,
            'cover_url' => $profile->cover_path ? route('vendor.profile.cover') : null,
            'city' => $profile->city,
            'region' => $profile->region,
            'country' => $profile->country,
            'service_area' => $profile->service_area,
            'base_price_cents' => $profile->base_price_cents,
            'price_unit' => $profile->price_unit,
            'website' => $profile->website,
            'video_url' => $profile->video_url,
            'brochure_url' => $profile->brochure_path ? route('vendor.profile.brochure') : null,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'socials' => $profile->socials ?? [],
            'is_accepting_bookings' => $profile->is_accepting_bookings,
            'status' => $profile->status?->value,
            'status_label' => $profile->status?->label(),
            'is_published' => $profile->status === VendorProfileStatus::Published,
            'can_submit' => $profile->status === VendorProfileStatus::Draft,
            'media' => $profile->media()->orderBy('sort_order')->get()->map(fn ($m) => [
                'id' => $m->id,
                'url' => route('vendor.media.file', $m),
                'caption' => $m->caption,
                'alt_text' => $m->alt_text,
                'sort_order' => $m->sort_order,
                'original_name' => $m->original_name,
            ])->all(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\VendorProfile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VendorModerationController extends Controller
{
    public function index(): Response
    {
        $profiles = VendorProfile::with('user:id,name,email')
            ->withCount(['services', 'media'])
            ->latest()
            ->get()
            ->map(fn (VendorProfile $p) => [
                'id' => $p->id,
                'business_name' => $p->business_name,
                'slug' => $p->slug,
                'category' => $p->category?->value,
                'category_label' => $p->category?->label(),
                'status' => $p->status?->value,
                'status_label' => $p->status?->label(),
                'is_founding' => (bool) $p->is_founding,
                'owner_name' => $p->user?->name,
                'owner_email' => $p->user?->email,
                'services_count' => $p->services_count,
                'media_count' => $p->media_count,
                'created_at' => $p->created_at?->toDateString(),
            ]);

        $stats = [
            'draft' => VendorProfile::where('status', VendorProfileStatus::Draft->value)->count(),
            'pending_review' => VendorProfile::where('status', VendorProfileStatus::PendingReview->value)->count(),
            'published' => VendorProfile::where('status', VendorProfileStatus::Published->value)->count(),
            'suspended' => VendorProfile::where('status', VendorProfileStatus::Suspended->value)->count(),
        ];

        return Inertia::render('admin/vendors', [
            'profiles' => $profiles,
            'stats' => $stats,
        ]);
    }

    public function approve(VendorProfile $profile): RedirectResponse
    {
        abort_unless(
            in_array($profile->status, [VendorProfileStatus::PendingReview, VendorProfileStatus::Suspended]),
            422,
        );

        $profile->update(['status' => VendorProfileStatus::Published->value]);

        return back()->with('status', 'vendor-approved');
    }

    public function suspend(VendorProfile $profile): RedirectResponse
    {
        abort_unless($profile->status === VendorProfileStatus::Published, 422);

        $profile->update(['status' => VendorProfileStatus::Suspended->value]);

        return back()->with('status', 'vendor-suspended');
    }

    public function toggleFounding(VendorProfile $profile): RedirectResponse
    {
        $profile->update(['is_founding' => ! $profile->is_founding]);

        return back()->with('status', 'vendor-founding-toggled');
    }

    public function toggleVerified(VendorProfile $profile): RedirectResponse
    {
        $profile->update(['verified_at' => $profile->verified_at ? null : now()]);

        return back()->with('status', 'vendor-verified-toggled');
    }
}

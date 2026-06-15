<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin oversight of every wedding on the platform, with a support entry point
 * to open any workspace (see AdminSupportController).
 */
class WeddingController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        $weddings = Wedding::withCount(['guests', 'vendors', 'tasks'])
            ->with('owner:id,name,email,plan,account_type')
            ->latest('created_at')
            ->get()
            ->map(fn (Wedding $w) => [
                'id' => $w->id,
                'slug' => $w->slug,
                'name' => $w->name,
                'event_date' => $w->event_date?->toDateString(),
                'days_until' => $w->event_date ? (int) $today->diffInDays($w->event_date, false) : null,
                'owner_name' => $w->owner?->name,
                'owner_email' => $w->owner?->email,
                'owner_plan' => $w->owner?->plan,
                'guest_count' => $w->guests_count,
                'vendor_count' => $w->vendors_count,
                'task_count' => $w->tasks_count,
                'created_at' => $w->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/weddings', ['weddings' => $weddings]);
    }

    public function show(Wedding $wedding): Response
    {
        $wedding->load(['owner:id,name,email,plan', 'members:id,name,email']);

        $vendors = Vendor::query()
            ->where('wedding_id', $wedding->id)
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'status'])
            ->map(fn (Vendor $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'category' => $v->category->label(),
                'status' => $v->status->label(),
            ]);

        $quotes = Inquiry::query()
            ->where('wedding_id', $wedding->id)
            ->with('vendorProfile:id,business_name')
            ->latest()
            ->get()
            ->map(fn (Inquiry $i) => [
                'id' => $i->id,
                'vendor_name' => $i->vendorProfile?->business_name,
                'status' => $i->status->label(),
                'created_at' => $i->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/wedding-show', [
            'wedding' => [
                'id' => $wedding->id,
                'slug' => $wedding->slug,
                'name' => $wedding->name,
                'event_date' => $wedding->event_date?->toDateString(),
                'owner' => [
                    'name' => $wedding->owner?->name,
                    'email' => $wedding->owner?->email,
                    'plan' => $wedding->owner?->plan,
                ],
                'members' => $wedding->members->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'role' => $m->pivot->role,
                ])->values(),
            ],
            'vendors' => $vendors,
            'quotes' => $quotes,
        ]);
    }
}

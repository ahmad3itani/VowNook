<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\TimelineEvent;
use App\Models\Vendor;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vendor-facing portal: shows the vendor their own booking details, timeline
 * schedule, payment status, and allows them to update their status and notes.
 */
class VendorPortalController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();
        $wedding = $this->current->get();

        if (! $wedding) {
            return Inertia::render('vendor-portal/index', ['vendor' => null]);
        }

        // Must be a vendor-role member.
        $role = $wedding->roleFor($user);
        if ($role !== Role::Vendor) {
            return redirect()->route('dashboard');
        }

        // Find the vendor record linked to this user.
        $vendor = Vendor::where('wedding_id', $wedding->id)
            ->where('vendor_user_id', $user->id)
            ->first();

        // Timeline events (couple's schedule — visible to vendors so they know their slot).
        $events = TimelineEvent::query()
            ->where('wedding_id', $wedding->id)
            ->orderBy('start_time')
            ->get(['id', 'title', 'event_type', 'start_time', 'end_time', 'location', 'notes'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'event_type' => $e->event_type->value,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'location' => $e->location,
                'notes' => $e->notes,
            ]);

        return Inertia::render('vendor-portal/index', [
            'wedding' => [
                'name' => $wedding->name,
                'event_date' => $wedding->event_date?->toDateString(),
                'slug' => $wedding->slug,
            ],
            'vendor' => $vendor ? [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'category' => $vendor->category->label(),
                'status' => $vendor->status->value,
                'status_label' => $vendor->status->label(),
                'contact_name' => $vendor->contact_name,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'cost' => $vendor->cost_cents ? $vendor->cost_cents / 100 : null,
                'paid' => $vendor->paid_cents ? $vendor->paid_cents / 100 : null,
                'notes' => $vendor->notes,
                'contract_status' => $vendor->contract_status,
                'coi_status' => $vendor->coi_status,
                'follow_up_at' => $vendor->follow_up_at?->toDateString(),
            ] : null,
            'timeline' => $events,
        ]);
    }

    /** Vendor updates their own status or notes. */
    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $user = auth()->user();
        $wedding = $this->current->get();

        // Only the linked vendor user may update.
        abort_unless(
            $wedding && $vendor->wedding_id === $wedding->id && $vendor->vendor_user_id === $user->id,
            403,
        );

        $data = $request->validate([
            'status' => ['sometimes', 'string'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'contract_status' => ['sometimes', 'nullable', 'in:pending,received,signed'],
            'coi_status' => ['sometimes', 'nullable', 'in:pending,received,on_file'],
        ]);

        $vendor->update($data);

        return back()->with('status', 'vendor-updated');
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\RsvpStatus;
use App\Models\Inquiry;
use App\Models\Task;
use App\Models\Wedding;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The planner HQ — a portfolio view across every wedding the planner can
 * access (owned client weddings + ones they were invited into), with a
 * cross-wedding "needs attention" feed.
 */
class PlannerDashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        abort_unless($user->isPlanner(), 403);

        $weddings = $user->accessibleWeddings()
            ->sortBy(fn (Wedding $w) => $w->event_date?->timestamp ?? PHP_INT_MAX)
            ->values();

        $today = Carbon::today();
        $weddingIds = $weddings->pluck('id')->all();

        // Cross-wedding aggregates in three queries, not three per wedding.
        $guests = collect($weddingIds)->isEmpty() ? collect() : \App\Models\Guest::query()
            ->whereIn('wedding_id', $weddingIds)
            ->get(['wedding_id', 'rsvp_status']);

        $budgets = collect($weddingIds)->isEmpty() ? collect() : \App\Models\BudgetItem::query()
            ->whereIn('wedding_id', $weddingIds)
            ->get(['wedding_id', 'estimated_cents', 'paid_cents']);

        $tasks = collect($weddingIds)->isEmpty() ? collect() : Task::query()
            ->whereIn('wedding_id', $weddingIds)
            ->where('is_complete', false)
            ->get(['id', 'wedding_id', 'title', 'priority', 'due_date']);

        $openOffers = collect($weddingIds)->isEmpty() ? collect() : Inquiry::query()
            ->whereIn('wedding_id', $weddingIds)
            ->where('status', InquiryStatus::Offered->value)
            ->with('vendorProfile:id,business_name')
            ->get();

        $cards = $weddings->map(function (Wedding $w) use ($guests, $budgets, $tasks, $openOffers, $today, $user) {
            $g = $guests->where('wedding_id', $w->id);
            $b = $budgets->where('wedding_id', $w->id);
            $t = $tasks->where('wedding_id', $w->id);

            return [
                'id' => $w->id,
                'slug' => $w->slug,
                'name' => $w->name,
                'event_date' => $w->event_date?->toDateString(),
                'days_until' => $w->event_date ? (int) $today->diffInDays($w->event_date, false) : null,
                'role' => $w->roleFor($user)?->value,
                'guests' => [
                    'total' => $g->count(),
                    'attending' => $g->where('rsvp_status', RsvpStatus::Attending)->count(),
                    'pending' => $g->where('rsvp_status', RsvpStatus::Pending)->count(),
                ],
                'budget' => [
                    'estimated_cents' => (int) $b->sum('estimated_cents'),
                    'paid_cents' => (int) $b->sum('paid_cents'),
                ],
                'tasks_outstanding' => $t->count(),
                'tasks_overdue' => $t->filter(fn (Task $task) => $task->due_date !== null && $task->due_date->lt($today))->count(),
                'offers_awaiting' => $openOffers->where('wedding_id', $w->id)->count(),
            ];
        })->values();

        $byId = $weddings->keyBy('id');

        // The feed: what needs the planner today, across every client.
        $attention = [
            'overdue_tasks' => $tasks
                ->filter(fn (Task $t) => $t->due_date !== null && $t->due_date->lt($today))
                ->sortBy('due_date')
                ->take(10)
                ->map(fn (Task $t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'priority' => $t->priority->value,
                    'days_overdue' => (int) $today->diffInDays($t->due_date),
                    'wedding_id' => $t->wedding_id,
                    'wedding_name' => $byId[$t->wedding_id]?->name ?? '',
                    'wedding_slug' => $byId[$t->wedding_id]?->slug ?? '',
                ])->values(),
            'due_this_week' => $tasks
                ->filter(fn (Task $t) => $t->due_date !== null
                    && $t->due_date->gte($today)
                    && $t->due_date->lte($today->copy()->addDays(7)))
                ->sortBy('due_date')
                ->take(10)
                ->map(fn (Task $t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'priority' => $t->priority->value,
                    'due_date' => $t->due_date->toDateString(),
                    'wedding_id' => $t->wedding_id,
                    'wedding_name' => $byId[$t->wedding_id]?->name ?? '',
                    'wedding_slug' => $byId[$t->wedding_id]?->slug ?? '',
                ])->values(),
            'offers_awaiting' => $openOffers
                ->take(10)
                ->map(fn (Inquiry $i) => [
                    'id' => $i->id,
                    'vendor_name' => $i->vendorProfile?->business_name,
                    'wedding_id' => $i->wedding_id,
                    'wedding_name' => $byId[$i->wedding_id]?->name ?? '',
                    'wedding_slug' => $byId[$i->wedding_id]?->slug ?? '',
                ])->values(),
        ];

        // Optional public marketplace listing (a VendorProfile, category=planner).
        $profile = $user->vendorProfile;
        $listing = [
            'exists' => $profile !== null,
            'status' => $profile?->status?->value,
            'status_label' => $profile?->status?->label(),
            'edit_url' => $profile ? route('vendor.profile.edit') : null,
            'public_url' => $profile && $profile->status === \App\Enums\VendorProfileStatus::Published
                ? route('public.vendor.show', $profile->slug)
                : null,
        ];

        return Inertia::render('planner/dashboard', [
            'weddings' => $cards,
            'attention' => $attention,
            'listing' => $listing,
            'totals' => [
                'weddings' => $weddings->count(),
                'upcoming' => $cards->filter(fn ($c) => ($c['days_until'] ?? -1) >= 0)->count(),
                'overdue_tasks' => $attention['overdue_tasks']->count(),
                'offers_awaiting' => $openOffers->count(),
            ],
        ]);
    }
}

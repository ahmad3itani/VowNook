<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\Role;
use App\Enums\RsvpStatus;
use App\Enums\VendorStatus;
use App\Models\BudgetItem;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\SeatingTable;
use App\Models\Task;
use App\Models\TimelineEvent;
use App\Models\Vendor;
use App\Models\WeddingWebsite;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();

        // Platform admin → dedicated admin console, unless actively in a support
        // session for a specific wedding (then fall through to that workspace).
        if ($user->is_admin && ! session()->has('support_wedding_id')) {
            return redirect()->route('admin.dashboard');
        }

        // Marketplace vendor account → vendor business dashboard.
        if ($user->isVendor()) {
            return redirect()->route('vendor.dashboard');
        }

        // Planner account → portfolio HQ across all client weddings.
        // ?workspace=1 (from an HQ wedding card) opens the per-wedding
        // overview below instead.
        if ($user->isPlanner() && ! request()->boolean('workspace')) {
            return redirect()->route('planner.dashboard');
        }

        $wedding = $this->current->get();

        // Vendor-role members see the vendor portal instead.
        if ($wedding && $wedding->roleFor($user) === Role::Vendor) {
            return redirect()->route('vendor-portal.index');
        }

        if ($wedding === null) {
            return Inertia::render('dashboard', ['summary' => null]);
        }

        $weddingId = $wedding->id;
        $today = Carbon::today();
        $eventDate = $wedding->event_date;

        $guests = Guest::query()->forWedding($weddingId)->get(['rsvp_status', 'table_id', 'meal_choice']);
        $budget = BudgetItem::query()->forWedding($weddingId)->get(['estimated_cents', 'actual_cents', 'paid_cents']);
        $allTasks = Task::query()->forWedding($weddingId)->get(['id', 'title', 'priority', 'is_complete', 'due_date']);

        $attending = $guests->where('rsvp_status', RsvpStatus::Attending);

        // Overdue incomplete tasks, high priority first
        $overdueTasks = $allTasks
            ->where('is_complete', false)
            ->filter(fn (Task $t) => $t->due_date !== null && $t->due_date->lt($today))
            ->sortByDesc(fn (Task $t) => match ($t->priority->value) {
                'high' => 3, 'medium' => 2, default => 1,
            })
            ->take(5)
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'priority' => $t->priority->value,
                'days_overdue' => $today->diffInDays($t->due_date),
            ])
            ->values();

        // Upcoming tasks due in the next 14 days
        $upcomingTasks = $allTasks
            ->where('is_complete', false)
            ->filter(fn (Task $t) => $t->due_date !== null
                && $t->due_date->gte($today)
                && $t->due_date->lte($today->copy()->addDays(14)))
            ->sortBy('due_date')
            ->take(6)
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'priority' => $t->priority->value,
                'due_date' => $t->due_date->toDateString(),
            ])
            ->values();

        // Vendors not yet booked or declined
        $unbookedVendors = Vendor::query()
            ->forWedding($weddingId)
            ->whereNotIn('status', [VendorStatus::Booked->value, VendorStatus::Declined->value])
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'status'])
            ->map(fn (Vendor $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'category' => $v->category->label(),
                'status' => $v->status->label(),
            ])
            ->values();

        // Marketplace quotes the couple has open (and offers needing a decision).
        $openInquiries = Inquiry::query()
            ->forWedding($weddingId)
            ->whereIn('status', [InquiryStatus::Requested->value, InquiryStatus::Offered->value])
            ->with('vendorProfile:id,business_name')
            ->latest()
            ->get();

        $offersAwaiting = $openInquiries->where('status', InquiryStatus::Offered);

        // The planning journey, in order — drives the hero progress ring and the
        // "next step" nudge. Each milestone is derived from data already loaded
        // (plus two cheap exists/value queries).
        $websitePublished = (bool) WeddingWebsite::where('wedding_id', $weddingId)->value('is_published');
        $vendorBooked = Vendor::query()->forWedding($weddingId)
            ->where('status', VendorStatus::Booked->value)
            ->exists();

        $milestones = [
            ['key' => 'guests', 'label' => 'Add your guest list', 'done' => $guests->isNotEmpty(), 'href' => '/guests'],
            ['key' => 'budget', 'label' => 'Start your budget', 'done' => $budget->isNotEmpty(), 'href' => '/budget'],
            ['key' => 'checklist', 'label' => 'Build your checklist', 'done' => $allTasks->isNotEmpty(), 'href' => '/checklist'],
            ['key' => 'website', 'label' => 'Publish your website', 'done' => $websitePublished, 'href' => '/website'],
            ['key' => 'vendor', 'label' => 'Book your first vendor', 'done' => $vendorBooked, 'href' => '/vendors/marketplace'],
            ['key' => 'seating', 'label' => 'Seat your guests', 'done' => $guests->whereNotNull('table_id')->isNotEmpty(), 'href' => '/seating'],
        ];

        return Inertia::render('dashboard', [
            'milestones' => $milestones,
            'summary' => [
                'name' => $wedding->name,
                'event_date' => $eventDate?->toDateString(),
                'days_until' => $eventDate ? (int) $today->diffInDays($eventDate, false) : null,
            ],
            'guests' => [
                'total' => $guests->count(),
                'attending' => $attending->count(),
                'declined' => $guests->where('rsvp_status', RsvpStatus::Declined)->count(),
                'maybe' => $guests->where('rsvp_status', RsvpStatus::Maybe)->count(),
                'pending' => $guests->where('rsvp_status', RsvpStatus::Pending)->count(),
            ],
            'budget' => [
                'estimated' => $budget->sum('estimated_cents') / 100,
                'actual' => $budget->sum('actual_cents') / 100,
                'paid' => $budget->sum('paid_cents') / 100,
            ],
            'tasks' => [
                'total' => $allTasks->count(),
                'completed' => $allTasks->where('is_complete', true)->count(),
                'outstanding' => $allTasks->where('is_complete', false)->count(),
                'overdue' => $overdueTasks->count(),
            ],
            'counts' => [
                'vendors' => Vendor::query()->forWedding($weddingId)->count(),
                'events' => TimelineEvent::query()->forWedding($weddingId)->count(),
                'tables' => SeatingTable::query()->forWedding($weddingId)->count(),
                'seated' => $guests->whereNotNull('table_id')->count(),
            ],
            'attention' => [
                'overdue_tasks' => $overdueTasks,
                'upcoming_tasks' => $upcomingTasks,
                'unbooked_vendors' => $unbookedVendors,
                'no_meal_count' => $attending->whereNull('meal_choice')->count(),
                'unseated_count' => $attending->whereNull('table_id')->count(),
            ],
            'quotes' => [
                'open' => $openInquiries->count(),
                'offers_awaiting' => $offersAwaiting->count(),
                'items' => $offersAwaiting->take(5)->map(fn (Inquiry $i) => [
                    'id' => $i->id,
                    'vendor_name' => $i->vendorProfile?->business_name,
                ])->values(),
            ],
        ]);
    }
}

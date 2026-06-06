<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Models\BudgetItem;
use App\Models\Guest;
use App\Models\SeatingTable;
use App\Models\Task;
use App\Models\TimelineEvent;
use App\Models\Vendor;
use App\Support\CurrentWedding;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();

        if ($wedding === null) {
            return Inertia::render('dashboard', ['summary' => null]);
        }

        $weddingId = $wedding->id;

        $guests = Guest::query()->forWedding($weddingId)->get(['rsvp_status', 'table_id']);
        $budget = BudgetItem::query()->forWedding($weddingId)->get(['estimated_cents', 'actual_cents', 'paid_cents']);
        $tasks = Task::query()->forWedding($weddingId)->get(['is_complete', 'due_date']);

        $today = Carbon::today();
        $eventDate = $wedding->event_date;

        return Inertia::render('dashboard', [
            'summary' => [
                'name' => $wedding->name,
                'event_date' => $eventDate?->toDateString(),
                'days_until' => $eventDate ? $today->diffInDays($eventDate, false) : null,
            ],
            'guests' => [
                'total' => $guests->count(),
                'attending' => $guests->where('rsvp_status', RsvpStatus::Attending)->count(),
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
                'total' => $tasks->count(),
                'completed' => $tasks->where('is_complete', true)->count(),
                'outstanding' => $tasks->where('is_complete', false)->count(),
                'overdue' => $tasks
                    ->where('is_complete', false)
                    ->filter(fn (Task $t) => $t->due_date !== null && $t->due_date->lt($today))
                    ->count(),
            ],
            'counts' => [
                'vendors' => Vendor::query()->forWedding($weddingId)->count(),
                'events' => TimelineEvent::query()->forWedding($weddingId)->count(),
                'tables' => SeatingTable::query()->forWedding($weddingId)->count(),
                'seated' => $guests->whereNotNull('table_id')->count(),
            ],
        ]);
    }
}

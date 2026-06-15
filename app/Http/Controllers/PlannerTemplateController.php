<?php

namespace App\Http\Controllers;

use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\PlannerTemplate;
use App\Models\Task;
use App\Models\Wedding;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reusable checklist/budget templates. A planner saves any wedding's
 * checklist or budget as a blueprint, then applies it to a new client —
 * checklist due dates are recomputed from each wedding's event date.
 */
class PlannerTemplateController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->isPlanner(), 403);

        $templates = PlannerTemplate::forUser($user->id)
            ->latest()
            ->get()
            ->map(fn (PlannerTemplate $t) => [
                'id' => $t->id,
                'type' => $t->type,
                'name' => $t->name,
                'item_count' => count($t->items ?? []),
                'created_at' => $t->created_at->toDateString(),
            ]);

        $weddings = $user->accessibleWeddings()->map(fn (Wedding $w) => [
            'id' => $w->id,
            'name' => $w->name,
            'event_date' => $w->event_date?->toDateString(),
        ])->values();

        return Inertia::render('planner/templates', [
            'templates' => $templates,
            'weddings' => $weddings,
        ]);
    }

    /** Capture the active wedding's checklist or budget as a template. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['checklist', 'budget'])],
            'wedding_id' => ['required', 'integer'],
        ]);

        $wedding = $this->accessibleWedding($request, (int) $data['wedding_id']);

        $items = $data['type'] === 'checklist'
            ? $this->checklistItems($wedding)
            : $this->budgetItems($wedding);

        if (empty($items)) {
            return back()->withErrors(['type' => "That wedding has no {$data['type']} items to save."]);
        }

        PlannerTemplate::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'name' => $data['name'],
            'items' => $items,
        ]);

        return back()->with('status', 'template-saved');
    }

    /** Apply a template to a wedding, offsetting checklist dates from its event date. */
    public function apply(Request $request, PlannerTemplate $template): RedirectResponse
    {
        $user = $request->user();

        abort_unless($template->user_id === $user->id, 403);

        $data = $request->validate([
            'wedding_id' => ['required', 'integer'],
        ]);

        $wedding = $this->accessibleWedding($request, (int) $data['wedding_id']);

        if ($template->type === 'checklist') {
            $this->applyChecklist($template, $wedding);
        } else {
            $this->applyBudget($template, $wedding);
        }

        return back()->with('status', 'template-applied');
    }

    public function destroy(Request $request, PlannerTemplate $template): RedirectResponse
    {
        abort_unless($template->user_id === $request->user()->id, 403);

        $template->delete();

        return back()->with('status', 'template-deleted');
    }

    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    protected function checklistItems(Wedding $wedding): array
    {
        $eventDate = $wedding->event_date;

        return Task::forWedding($wedding->id)
            ->orderBy('due_date')
            ->get(['title', 'category', 'priority', 'due_date', 'notes'])
            ->map(fn (Task $t) => [
                'title' => $t->title,
                'category' => $t->category?->value,
                'priority' => $t->priority->value,
                // Days relative to the event date; null when either date is unknown.
                'offset_days' => ($eventDate && $t->due_date)
                    ? (int) $eventDate->diffInDays($t->due_date, false)
                    : null,
                'notes' => $t->notes,
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function budgetItems(Wedding $wedding): array
    {
        return BudgetItem::forWedding($wedding->id)
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (BudgetItem $i) => [
                'name' => $i->name,
                'category' => $i->category?->name,
                'estimated_cents' => $i->estimated_cents,
                'notes' => $i->notes,
            ])->all();
    }

    protected function applyChecklist(PlannerTemplate $template, Wedding $wedding): void
    {
        $eventDate = $wedding->event_date;
        $priorities = array_map(fn ($c) => $c->value, TaskPriority::cases());
        $categories = array_map(fn ($c) => $c->value, TaskCategory::cases());

        foreach ($template->items as $item) {
            $due = null;

            if ($eventDate && isset($item['offset_days'])) {
                $due = $eventDate->copy()->addDays((int) $item['offset_days']);

                // Never schedule template work in the past.
                if ($due->lt(Carbon::today())) {
                    $due = Carbon::today();
                }
            }

            Task::create([
                'wedding_id' => $wedding->id,
                'title' => (string) ($item['title'] ?? 'Untitled task'),
                'category' => in_array($item['category'] ?? null, $categories, true)
                    ? $item['category']
                    : null,
                'priority' => in_array($item['priority'] ?? null, $priorities, true)
                    ? $item['priority']
                    : TaskPriority::Medium->value,
                'due_date' => $due,
                'is_complete' => false,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function applyBudget(PlannerTemplate $template, Wedding $wedding): void
    {
        $categories = [];

        foreach ($template->items as $item) {
            $categoryId = null;

            if (! empty($item['category'])) {
                $name = (string) $item['category'];

                $categories[$name] ??= BudgetCategory::firstOrCreate(
                    ['wedding_id' => $wedding->id, 'name' => $name],
                )->id;

                $categoryId = $categories[$name];
            }

            BudgetItem::create([
                'wedding_id' => $wedding->id,
                'category_id' => $categoryId,
                'name' => (string) ($item['name'] ?? 'Untitled item'),
                'estimated_cents' => (int) ($item['estimated_cents'] ?? 0),
                'paid_cents' => 0,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    /** The wedding must be one the user can access (owner or member). */
    protected function accessibleWedding(Request $request, int $weddingId): Wedding
    {
        $userId = $request->user()->id;

        $wedding = Wedding::whereKey($weddingId)
            ->where(fn ($q) => $q
                ->where('owner_id', $userId)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $userId)))
            ->first();

        abort_unless($wedding instanceof Wedding, 403);

        return $wedding;
    }
}

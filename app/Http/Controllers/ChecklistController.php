<?php

namespace App\Http\Controllers;

use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ChecklistController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $tasks = Task::query()
            ->forWedding($weddingId)
            ->with('assignee:id,name')
            ->orderBy('is_complete')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->get();

        return Inertia::render('checklist/index', [
            'tasks' => $tasks->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'category' => $t->category->value,
                'priority' => $t->priority->value,
                'due_date' => $t->due_date?->toDateString(),
                'is_complete' => $t->is_complete,
                'notes' => $t->notes,
                'assigned_to' => $t->assigned_to,
                'assignee_name' => $t->assignee?->name,
            ]),
            'stats' => $this->stats($tasks),
            'options' => $this->options(),
            'members' => $this->current->get()?->members()
                ->orderBy('name')->get(['users.id', 'name']) ?? [],
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $task = new Task($this->fromRequest($request));
        $task->wedding_id = $this->current->id();
        $task->save();

        return back()->with('status', 'task-created');
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorizeTenant($task);

        $task->update($this->fromRequest($request, $task));

        return back()->with('status', 'task-updated');
    }

    public function toggle(Task $task): RedirectResponse
    {
        $this->authorizeTenant($task);

        $complete = ! $task->is_complete;
        $task->update([
            'is_complete' => $complete,
            'completed_at' => $complete ? now() : null,
        ]);

        return back()->with('status', 'task-toggled');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorizeTenant($task);

        $task->delete();

        return back()->with('status', 'task-deleted');
    }

    protected function authorizeTenant(Task $task): void
    {
        abort_unless($task->wedding_id === $this->current->id(), 404);
    }

    protected function fromRequest(TaskRequest $request, ?Task $task = null): array
    {
        $data = $request->validated();
        $complete = (bool) ($data['is_complete'] ?? false);

        return [
            'title' => $data['title'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'due_date' => $data['due_date'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_complete' => $complete,
            'completed_at' => $complete
                ? ($task?->completed_at ?? now())
                : null,
        ];
    }

    /** @param Collection<int, Task> $tasks */
    protected function stats(Collection $tasks): array
    {
        $today = Carbon::today();

        return [
            'total' => $tasks->count(),
            'completed' => $tasks->where('is_complete', true)->count(),
            'outstanding' => $tasks->where('is_complete', false)->count(),
            'overdue' => $tasks
                ->filter(fn (Task $t) => ! $t->is_complete
                    && $t->due_date !== null
                    && $t->due_date->lt($today))
                ->count(),
        ];
    }

    protected function options(): array
    {
        $map = fn (array $cases) => array_map(
            fn ($c) => ['value' => $c->value, 'label' => $c->label()],
            $cases,
        );

        return [
            'categories' => $map(TaskCategory::cases()),
            'priorities' => $map(TaskPriority::cases()),
        ];
    }
}

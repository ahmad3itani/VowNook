<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Http\Requests\TimelineEventRequest;
use App\Models\TimelineEvent;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $events = TimelineEvent::query()
            ->forWedding($weddingId)
            ->with('vendor:id,name')
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('timeline/index', [
            'events' => $events->map(fn (TimelineEvent $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->type->value,
                'starts_at' => $e->starts_at?->toIso8601String(),
                'ends_at' => $e->ends_at?->toIso8601String(),
                'location' => $e->location,
                'notes' => $e->notes,
                'vendor_id' => $e->vendor_id,
                'vendor_name' => $e->vendor?->name,
            ]),
            'stats' => $this->stats($events),
            'options' => $this->options(),
            'vendors' => $this->current->get()?->vendors()
                ->orderBy('name')->get(['id', 'name']) ?? [],
        ]);
    }

    public function store(TimelineEventRequest $request): RedirectResponse
    {
        $event = new TimelineEvent($this->fromRequest($request));
        $event->wedding_id = $this->current->id();
        $event->save();

        return back()->with('status', 'timeline-event-created');
    }

    public function update(TimelineEventRequest $request, TimelineEvent $event): RedirectResponse
    {
        $this->authorizeTenant($event);

        $event->update($this->fromRequest($request));

        return back()->with('status', 'timeline-event-updated');
    }

    public function destroy(TimelineEvent $event): RedirectResponse
    {
        $this->authorizeTenant($event);

        $event->delete();

        return back()->with('status', 'timeline-event-deleted');
    }

    protected function authorizeTenant(TimelineEvent $event): void
    {
        abort_unless($event->wedding_id === $this->current->id(), 404);
    }

    protected function fromRequest(TimelineEventRequest $request): array
    {
        $data = $request->validated();

        return [
            'title' => $data['title'],
            'type' => $data['type'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'location' => $data['location'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /** @param Collection<int, TimelineEvent> $events */
    protected function stats(Collection $events): array
    {
        return [
            'total' => $events->count(),
            'linked' => $events->whereNotNull('vendor_id')->count(),
            'locations' => $events->pluck('location')->filter()->unique()->count(),
            'days' => $events
                ->map(fn (TimelineEvent $e) => $e->starts_at?->toDateString())
                ->filter()
                ->unique()
                ->count(),
        ];
    }

    protected function options(): array
    {
        return [
            'types' => array_map(
                fn (EventType $t) => ['value' => $t->value, 'label' => $t->label()],
                EventType::cases(),
            ),
        ];
    }
}

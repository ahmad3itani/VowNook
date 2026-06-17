<?php

namespace App\Http\Controllers;

use App\Models\WeddingEvent;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Couple-side editor for the celebration schedule — multiple events
 * (rehearsal dinner, welcome party, ceremony, reception, brunch …), each
 * optionally RSVP-able on the public wedding site.
 */
class WeddingEventController extends Controller
{
    /** Allowed event types — drive the editor's select + a small icon map. */
    public const TYPES = ['ceremony', 'reception', 'rehearsal', 'welcome', 'brunch', 'party', 'other'];

    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();

        $events = WeddingEvent::forWedding($wedding->id)->ordered()
            ->withCount(['guests as attending_count' => fn ($q) => $q->wherePivot('rsvp_status', 'attending')])
            ->get();

        return Inertia::render('events/index', [
            'events' => $events->map(fn (WeddingEvent $e) => $this->eventData($e)),
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEvent($request);
        $wedding = $this->current->get();

        $data['wedding_id'] = $wedding->id;
        $data['sort_order'] = (int) WeddingEvent::forWedding($wedding->id)->max('sort_order') + 1;

        WeddingEvent::create($data);

        return back()->with('status', 'event-saved');
    }

    public function update(Request $request, WeddingEvent $event): RedirectResponse
    {
        $this->authorizeOwn($event->wedding_id);

        $event->update($this->validateEvent($request));

        return back()->with('status', 'event-saved');
    }

    public function destroy(WeddingEvent $event): RedirectResponse
    {
        $this->authorizeOwn($event->wedding_id);

        $event->delete();

        return back()->with('status', 'event-deleted');
    }

    /** Persist a new ordering from the drag-and-drop editor. */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $owned = WeddingEvent::forWedding($this->current->id())->pluck('id')->all();

        foreach ($data['ids'] as $position => $id) {
            if (in_array((int) $id, $owned, true)) {
                WeddingEvent::where('id', $id)->update(['sort_order' => $position]);
            }
        }

        return back()->with('status', 'event-saved');
    }

    /** @return array<string,mixed> */
    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'event_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'string', 'max:20'],
            'end_time' => ['nullable', 'string', 'max:20'],
            'venue_name' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:300'],
            'dress_code' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_rsvpable' => ['boolean'],
        ]);
    }

    private function authorizeOwn(int $weddingId): void
    {
        abort_unless($weddingId === $this->current->id(), 404);
    }

    /** @return array<string,mixed> */
    private function eventData(WeddingEvent $e): array
    {
        return [
            'id' => $e->id,
            'name' => $e->name,
            'type' => $e->type,
            'event_date' => $e->event_date?->toDateString(),
            'start_time' => $e->start_time,
            'end_time' => $e->end_time,
            'venue_name' => $e->venue_name,
            'address' => $e->address,
            'dress_code' => $e->dress_code,
            'description' => $e->description,
            'is_rsvpable' => $e->is_rsvpable,
            'attending_count' => (int) ($e->attending_count ?? 0),
        ];
    }
}

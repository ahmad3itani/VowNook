<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\Wedding;
use App\Models\WeddingEvent;
use App\Notifications\RsvpReceived;
use App\Support\MealOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public, unauthenticated wedding RSVP site, reachable at /w/{slug}.
 * Guests look themselves up by name and submit their reply. No guest list is
 * ever exposed wholesale — only name-matched results are returned.
 */
class PublicRsvpController extends Controller
{
    /**
     * The RSVP page. Search is a GET query (?name=) on this same route so the
     * URL stays refresh-safe and shareable — no separate POST endpoint that
     * would rewrite the browser URL to a non-navigable path.
     */
    public function show(Wedding $wedding, Request $request): Response
    {
        $term = trim((string) $request->query('name', ''));
        $searched = mb_strlen($term) >= 2;

        // RSVP-able extra events the guest can reply to per-event.
        $events = WeddingEvent::forWedding($wedding->id)->where('is_rsvpable', true)->ordered()->get();
        $eventList = $events->map(fn (WeddingEvent $e) => [
            'id' => $e->id,
            'name' => $e->name,
            'type' => $e->type,
            'date' => $e->event_date?->translatedFormat('l, F j'),
            'time' => $e->start_time,
            'venue_name' => $e->venue_name,
        ])->values();

        $matches = collect();

        if ($searched) {
            $guests = Guest::query()
                ->forWedding($wedding->id)
                ->where(function ($query) use ($term) {
                    $query->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                })
                ->orderBy('first_name')
                ->limit(15)
                ->get(['id', 'first_name', 'last_name', 'rsvp_status', 'meal_choice', 'appetizer_choice', 'dessert_choice', 'dietary_notes']);

            // Each matched guest's existing per-event replies, keyed by event id.
            $replies = collect();
            if ($events->isNotEmpty() && $guests->isNotEmpty()) {
                $replies = DB::table('event_guest')
                    ->whereIn('guest_id', $guests->pluck('id'))
                    ->whereIn('wedding_event_id', $events->pluck('id'))
                    ->get()
                    ->groupBy('guest_id');
            }

            $matches = $guests->map(fn (Guest $g) => [
                'id' => $g->id,
                'name' => trim($g->first_name.' '.($g->last_name ?? '')),
                'rsvp_status' => $g->rsvp_status->value,
                'meal_choice' => $g->meal_choice,
                'appetizer_choice' => $g->appetizer_choice,
                'dessert_choice' => $g->dessert_choice,
                'dietary_notes' => $g->dietary_notes,
                'event_rsvps' => ($replies->get($g->id)
                    ?->mapWithKeys(fn ($r) => [(string) $r->wedding_event_id => $r->rsvp_status])->all()) ?: (object) [],
            ]);
        }

        // Only the courses the couple turned on, with their choices.
        $config = MealOptions::forWedding($wedding);
        $meals = [];
        foreach (MealOptions::enabledCourses($wedding) as $course) {
            $meals[] = [
                'course' => $course,
                'label' => MealOptions::LABELS[$course],
                'options' => $config[$course]['options'],
            ];
        }

        return Inertia::render('public/rsvp', [
            'wedding' => $this->weddingPayload($wedding),
            'matches' => $matches->values(),
            'searched' => $searched,
            'query' => $term,
            'meals' => $meals,
            'events' => $eventList,
        ])->withViewData(['seo' => \App\Support\Seo::make(
            title: "RSVP — {$wedding->name}",
            description: 'Reply to your invitation.',
            canonical: route('public.rsvp', $wedding->slug),
            index: false, // private guest utility page
        )]);
    }

    /** course => guest column. */
    private const COURSE_COLUMNS = [
        'appetizer' => 'appetizer_choice',
        'main' => 'meal_choice',
        'dessert' => 'dessert_choice',
    ];

    public function respond(Wedding $wedding, Request $request): RedirectResponse
    {
        $config = MealOptions::forWedding($wedding);

        // Each enabled course's choice must be null or one of its options.
        $rules = [
            'guest_id' => ['required', 'integer'],
            'rsvp_status' => ['required', Rule::in([
                RsvpStatus::Attending->value,
                RsvpStatus::Declined->value,
                RsvpStatus::Maybe->value,
            ])],
            'dietary_notes' => ['nullable', 'string', 'max:500'],
            'events' => ['nullable', 'array'],
            'events.*' => [Rule::in(['attending', 'declined', 'pending'])],
        ];

        foreach (self::COURSE_COLUMNS as $course => $column) {
            if (! $config[$course]['enabled']) {
                continue;
            }
            $options = $config[$course]['options'];
            $rules[$column] = $options === []
                ? ['nullable', 'string', 'max:120']
                : ['nullable', Rule::in($options)];
        }

        $data = $request->validate($rules);

        $guest = Guest::query()
            ->forWedding($wedding->id)
            ->findOrFail($data['guest_id']);

        $update = [
            'rsvp_status' => $data['rsvp_status'],
            'dietary_notes' => $data['dietary_notes'] ?? null,
        ];
        // Only write columns for courses that are actually on the form.
        foreach (self::COURSE_COLUMNS as $course => $column) {
            if ($config[$course]['enabled']) {
                $update[$column] = $data[$column] ?? null;
            }
        }

        $guest->update($update);

        // Per-event replies — only for the wedding's own rsvpable events.
        if (! empty($data['events'])) {
            $valid = WeddingEvent::forWedding($wedding->id)->where('is_rsvpable', true)->pluck('id')->all();
            $sync = [];
            foreach ($data['events'] as $eventId => $status) {
                if (in_array((int) $eventId, $valid, true)) {
                    $sync[(int) $eventId] = ['rsvp_status' => $status];
                }
            }
            if ($sync !== []) {
                $guest->events()->syncWithoutDetaching($sync);
            }
        }

        $wedding->owner?->notify(new RsvpReceived($wedding, $guest->fresh()));

        return back()->with('status', 'rsvp-received');
    }

    protected function weddingPayload(Wedding $wedding): array
    {
        return [
            'name' => $wedding->name,
            'slug' => $wedding->slug,
            'event_date' => $wedding->event_date?->toIso8601String(),
        ];
    }
}

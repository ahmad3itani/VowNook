<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function show(Wedding $wedding): Response
    {
        return Inertia::render('public/rsvp', [
            'wedding' => $this->weddingPayload($wedding),
            'matches' => [],
            'searched' => false,
        ]);
    }

    public function lookup(Wedding $wedding, Request $request): Response
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $term = trim($data['name']);

        $matches = Guest::query()
            ->forWedding($wedding->id)
            ->where(function ($query) use ($term) {
                $query->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            })
            ->orderBy('first_name')
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'rsvp_status', 'meal_choice', 'dietary_notes']);

        return Inertia::render('public/rsvp', [
            'wedding' => $this->weddingPayload($wedding),
            'matches' => $matches->map(fn (Guest $g) => [
                'id' => $g->id,
                'name' => trim($g->first_name.' '.($g->last_name ?? '')),
                'rsvp_status' => $g->rsvp_status->value,
                'meal_choice' => $g->meal_choice,
                'dietary_notes' => $g->dietary_notes,
            ]),
            'searched' => true,
        ]);
    }

    public function respond(Wedding $wedding, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['required', 'integer'],
            'rsvp_status' => ['required', Rule::in([
                RsvpStatus::Attending->value,
                RsvpStatus::Declined->value,
                RsvpStatus::Maybe->value,
            ])],
            'meal_choice' => ['nullable', 'string', 'max:120'],
            'dietary_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $guest = Guest::query()
            ->forWedding($wedding->id)
            ->findOrFail($data['guest_id']);

        $guest->update([
            'rsvp_status' => $data['rsvp_status'],
            'meal_choice' => $data['meal_choice'] ?? null,
            'dietary_notes' => $data['dietary_notes'] ?? null,
        ]);

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

<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public, unauthenticated seat finder, reachable at /w/{slug}/seats.
 * Designed to back a printed QR code at the venue: a guest scans it, looks
 * themselves up by name, and is shown their table — and who shares it. Only
 * name-matched results are returned; the full guest list is never exposed.
 */
class PublicSeatingController extends Controller
{
    /**
     * The seat finder. Search is a GET query (?name=) on this same route so the
     * URL stays refresh-safe — there is no separate POST endpoint that would
     * rewrite the browser URL to a non-navigable path.
     */
    public function show(Wedding $wedding, Request $request): Response
    {
        $term = trim((string) $request->query('name', ''));
        $searched = mb_strlen($term) >= 2;

        $matches = collect();

        if ($searched) {
            $guests = Guest::query()
                ->forWedding($wedding->id)
                ->whereNotNull('table_id')
                ->where(function ($query) use ($term) {
                    $query->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                })
                ->with('seatingTable:id,name')
                ->orderBy('first_name')
                ->limit(15)
                ->get(['id', 'first_name', 'last_name', 'table_id']);

            $matches = $guests->map(function (Guest $g) use ($wedding) {
                $tablemates = Guest::query()
                    ->forWedding($wedding->id)
                    ->where('table_id', $g->table_id)
                    ->where('id', '!=', $g->id)
                    ->orderBy('first_name')
                    ->get(['first_name', 'last_name'])
                    ->map(fn (Guest $m) => trim($m->first_name.' '.($m->last_name ?? '')))
                    ->values();

                return [
                    'id' => $g->id,
                    'name' => trim($g->first_name.' '.($g->last_name ?? '')),
                    'table' => $g->seatingTable?->name,
                    'tablemates' => $tablemates,
                ];
            });
        }

        return Inertia::render('public/seats', [
            'wedding' => $this->weddingPayload($wedding),
            'matches' => $matches->values(),
            'searched' => $searched,
            'query' => $term,
        ]);
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

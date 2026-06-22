<?php

namespace App\Http\Controllers;

use App\Models\HoneymoonPlan;
use App\Support\Affiliates\TravelAffiliates;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Couple-side honeymoon planner — destination, dates, a simple budget, plus an
 * affiliate hotel map and flight search to the destination (so bookings earn
 * commission). Atelier-gated via the route's plan.feature:travel middleware.
 */
class HoneymoonController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();
        $plan = HoneymoonPlan::forWedding($wedding->id)->first();
        $affiliates = app(TravelAffiliates::class);

        return Inertia::render('honeymoon/index', [
            'plan' => [
                'destination' => $plan?->destination,
                'airport' => $plan?->airport,
                'start_date' => $plan?->start_date?->toDateString(),
                'end_date' => $plan?->end_date?->toDateString(),
                'budget_items' => $plan?->budget_items ?? [],
                'notes' => $plan?->notes,
            ],
            'stays_url' => $plan?->destination
                ? $affiliates->stay22DestinationUrl($plan->destination, $plan->start_date, $plan->end_date)
                : null,
            'flights_url' => $plan?->airport
                ? $affiliates->aviasalesRangeUrl($plan->airport, $plan->start_date, $plan->end_date)
                : null,
            'affiliate_partner' => TravelAffiliates::PARTNER,
            'flights_partner' => TravelAffiliates::FLIGHTS_PARTNER,
            'affiliate_enabled' => $affiliates->isConfigured(),
            'flights_enabled' => $affiliates->flightsConfigured(),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();

        $data = $request->validate([
            'destination' => ['nullable', 'string', 'max:160'],
            'airport' => ['nullable', 'string', 'max:60'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'budget_items' => ['nullable', 'array', 'max:30'],
            'budget_items.*.label' => ['required_with:budget_items.*', 'string', 'max:120'],
            'budget_items.*.amount_cents' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
        ]);

        HoneymoonPlan::updateOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'destination' => $data['destination'] ?? null,
                'airport' => isset($data['airport']) ? trim($data['airport']) : null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'budget_items' => collect($data['budget_items'] ?? [])
                    ->map(fn ($i) => ['label' => (string) $i['label'], 'amount_cents' => (int) ($i['amount_cents'] ?? 0)])
                    ->values()
                    ->all(),
            ],
        );

        return back()->with('status', 'honeymoon-saved');
    }
}

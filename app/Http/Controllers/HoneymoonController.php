<?php

namespace App\Http\Controllers;

use App\Models\HoneymoonPlan;
use App\Models\Wedding;
use App\Support\Affiliates\TravelAffiliates;
use App\Support\Ai\AiException;
use App\Support\Ai\AiService;
use App\Support\CurrentWedding;
use Illuminate\Http\JsonResponse;
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
            // "Plan it with AI" — a paid perk; free couples just fill it themselves.
            'ai_enabled' => app(AiService::class)->isConfigured() && request()->user()->canUseAi(),
        ]);
    }

    /**
     * Draft a honeymoon plan from the couple's preferences — a specific
     * destination + airport, a budget breakdown, and a warm highlights blurb.
     * Returns it for the couple to review and save (never a silent write).
     * Gated to paid plans; degrades gracefully when AI isn't configured.
     */
    public function aiPlan(Request $request, AiService $ai): JsonResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        if (! $request->user()->canUseAi()) {
            return response()->json([
                'message' => 'AI assistance is a paid feature. Upgrade your plan to unlock it.',
            ], 403);
        }

        $data = $request->validate([
            'preferences' => ['nullable', 'string', 'max:1000'],
            'destination' => ['nullable', 'string', 'max:160'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'departure' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        if (! $ai->isConfigured()) {
            return response()->json(['available' => false]);
        }

        $tool = [
            'name' => 'propose_honeymoon',
            'description' => 'Propose a honeymoon plan for the couple.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'destination' => ['type' => 'string', 'description' => 'One specific destination, e.g. "Maui, Hawaii".'],
                    'airport' => ['type' => 'string', 'description' => 'The 3-letter IATA code of the nearest airport, e.g. "OGG".'],
                    'highlights' => ['type' => 'string', 'description' => 'A warm 2-4 sentence overview: why it fits, top things to do, a tip. No headings, no markdown.'],
                    'budget_items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'label' => ['type' => 'string', 'description' => 'e.g. "Flights", "Resort (7 nights)", "Activities".'],
                                'amount_dollars' => ['type' => 'number', 'description' => 'Estimated cost in CAD dollars.'],
                            ],
                            'required' => ['label', 'amount_dollars'],
                        ],
                    ],
                ],
                'required' => ['destination', 'airport', 'highlights', 'budget_items'],
            ],
        ];

        try {
            $result = $ai->generateStructured($this->aiSystem(), $this->aiContext($wedding, $data), $tool);
        } catch (AiException $e) {
            return response()->json(['available' => true, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'available' => true,
            'destination' => (string) ($result['destination'] ?? ''),
            'airport' => strtoupper(trim((string) ($result['airport'] ?? ''))),
            'notes' => (string) ($result['highlights'] ?? ''),
            'budget_items' => collect($result['budget_items'] ?? [])
                ->take(30)
                ->map(fn ($i) => [
                    'label' => (string) ($i['label'] ?? ''),
                    'amount_cents' => (int) round(max(0, (float) ($i['amount_dollars'] ?? 0)) * 100),
                ])
                ->filter(fn ($i) => $i['label'] !== '')
                ->values()
                ->all(),
        ]);
    }

    private function aiSystem(): string
    {
        return 'You are a thoughtful honeymoon planner. From the couple\'s preferences, propose ONE specific '
            .'destination with its nearest airport (3-letter IATA code), a warm short highlights/tips overview, '
            .'and a realistic budget breakdown in Canadian dollars that roughly fits their stated total (if any). '
            .'Consider the season if dates are given. Keep it tasteful and concrete — real places, real prices.';
    }

    /** @param  array<string,mixed>  $data */
    private function aiContext(Wedding $wedding, array $data): string
    {
        $parts = ["The couple is {$wedding->name}."];

        if (filled($data['preferences'] ?? null)) {
            $parts[] = 'What they want: '.trim($data['preferences']).'.';
        }
        if (filled($data['destination'] ?? null)) {
            $parts[] = 'They are leaning toward: '.trim($data['destination']).' (refine or confirm this).';
        }
        if (filled($data['departure'] ?? null)) {
            $parts[] = 'They are travelling from '.trim($data['departure']).'.';
        }
        if (isset($data['budget']) && (float) $data['budget'] > 0) {
            $parts[] = 'Total budget: about CAD $'.number_format((float) $data['budget']).'.';
        }
        if (filled($data['start_date'] ?? null) && filled($data['end_date'] ?? null)) {
            $parts[] = "Dates: {$data['start_date']} to {$data['end_date']}.";
        }

        return implode(' ', $parts);
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

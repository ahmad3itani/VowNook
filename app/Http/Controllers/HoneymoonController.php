<?php

namespace App\Http\Controllers;

use App\Models\HoneymoonPlan;
use App\Models\Wedding;
use App\Support\Affiliates\TravelAffiliates;
use App\Support\Affiliates\TravelPricing;
use App\Support\Ai\AiException;
use App\Support\Ai\AiService;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AI honeymoon concierge. From the couple's vibe + budget + dates, it designs
 * THREE all-inclusive, budget-tiered packages (Essential / Signature / Dream),
 * each with a destination, a "why this one", a day-by-day plan with a daily
 * spend, and a cost breakdown. The couple compares, chooses one, then books the
 * flight + hotel through our affiliate partners. Atelier-gated by the route.
 */
class HoneymoonController extends Controller
{
    public const TIERS = ['essential', 'signature', 'dream'];

    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $wedding = $this->current->get();
        $plan = HoneymoonPlan::forWedding($wedding->id)->first();
        $affiliates = app(TravelAffiliates::class);

        $staysUrl = null;
        $flightsUrl = null;
        $live = null;
        $chosen = $plan && $plan->chosen_tier
            ? collect($plan->packages ?? [])->firstWhere('tier', $plan->chosen_tier)
            : null;

        if ($chosen) {
            $staysUrl = $affiliates->stay22DestinationUrl($chosen['destination'] ?? null, $plan->start_date, $plan->end_date);
            $flightsUrl = $affiliates->aviasalesRangeUrl($chosen['airport'] ?? null, $plan->start_date, $plan->end_date);

            // Live prices for the package they're about to book (cached, graceful).
            $pricing = app(TravelPricing::class);
            $start = $plan->start_date?->toDateString();
            $end = $plan->end_date?->toDateString();
            $live = [
                'configured' => $pricing->configured(),
                'flight' => $pricing->flightPrice($chosen['origin_airport'] ?? null, $chosen['airport'] ?? null, $start, $end),
                'hotel' => $pricing->hotelPrice($chosen['destination'] ?? null, $start, $end),
            ];
        }

        return Inertia::render('honeymoon/index', [
            'preferences' => $plan?->preferences ?? [],
            'dates' => [
                'start' => $plan?->start_date?->toDateString(),
                'end' => $plan?->end_date?->toDateString(),
            ],
            'packages' => $plan?->packages ?? [],
            'chosen_tier' => $plan?->chosen_tier,
            'stays_url' => $staysUrl,
            'flights_url' => $flightsUrl,
            'live' => $live,
            'affiliate_partner' => TravelAffiliates::PARTNER,
            'flights_partner' => TravelAffiliates::FLIGHTS_PARTNER,
            'ai_enabled' => app(AiService::class)->isConfigured() && request()->user()->canUseAi(),
        ]);
    }

    /** Craft three budget-tiered honeymoon packages from the couple's brief. */
    public function generate(Request $request, AiService $ai): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        $data = $request->validate([
            'vibe' => ['nullable', 'string', 'max:1000'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'departure' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'interests' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $request->user()->canUseAi()) {
            return back()->withErrors(['ai' => 'The honeymoon concierge is an Atelier (paid) feature.']);
        }

        if (! $ai->isConfigured()) {
            return back()->withErrors(['ai' => 'AI isn’t configured on this server yet.']);
        }

        try {
            $packages = $this->craftPackages($ai, $wedding, $data);
        } catch (AiException $e) {
            return back()->withErrors(['ai' => $e->getMessage()]);
        }

        if ($packages === []) {
            return back()->withErrors(['ai' => 'The concierge couldn’t craft a plan just now — please try again.']);
        }

        HoneymoonPlan::updateOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'preferences' => [
                    'vibe' => $data['vibe'] ?? null,
                    'budget' => isset($data['budget']) ? (float) $data['budget'] : null,
                    'departure' => $data['departure'] ?? null,
                    'interests' => $data['interests'] ?? null,
                ],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'packages' => $packages,
                'chosen_tier' => null,
            ],
        );

        return back()->with('status', 'honeymoon-crafted');
    }

    /** Lock in one of the three tiers; copies its details onto the plan for booking. */
    public function choose(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        $data = $request->validate(['tier' => ['required', Rule::in(self::TIERS)]]);

        $plan = HoneymoonPlan::forWedding($wedding->id)->first();
        abort_unless($plan !== null, 404);

        $pkg = collect($plan->packages ?? [])->firstWhere('tier', $data['tier']);
        abort_unless($pkg !== null, 404);

        $plan->update([
            'chosen_tier' => $data['tier'],
            'destination' => $pkg['destination'] ?? null,
            'airport' => $pkg['airport'] ?? null,
            'notes' => $pkg['why'] ?? null,
            'budget_items' => $this->budgetFromPackage($pkg),
        ]);

        return back()->with('status', 'honeymoon-chosen');
    }

    /** Clear the crafted packages so the couple can start a fresh brief. */
    public function startOver(): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        HoneymoonPlan::forWedding($wedding->id)->update(['packages' => null, 'chosen_tier' => null]);

        return back();
    }

    // ── AI ───────────────────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed>  $data
     * @return array<int, array<string,mixed>>
     */
    private function craftPackages(AiService $ai, Wedding $wedding, array $data): array
    {
        $tool = [
            'name' => 'propose_honeymoons',
            'description' => 'Return three budget-tiered honeymoon packages.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'packages' => [
                        'type' => 'array',
                        'description' => 'Exactly three packages: one per tier (essential, signature, dream).',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'tier' => ['type' => 'string', 'enum' => self::TIERS],
                                'destination' => ['type' => 'string', 'description' => 'A specific destination, e.g. "Maui, Hawaii".'],
                                'airport' => ['type' => 'string', 'description' => 'Nearest airport IATA code, e.g. "OGG".'],
                                'origin_airport' => ['type' => 'string', 'description' => 'IATA code of the couple’s departure airport (from their home city), e.g. "YYZ".'],
                                'why' => ['type' => 'string', 'description' => 'A warm 1-2 sentence pitch: why this fits them.'],
                                'hotel_name' => ['type' => 'string', 'description' => 'A specific, real, well-regarded hotel or resort.'],
                                'flight_estimate_dollars' => ['type' => 'number', 'description' => 'Round-trip flights for two, CAD.'],
                                'hotel_estimate_dollars' => ['type' => 'number', 'description' => 'The whole stay, CAD.'],
                                'activities_estimate_dollars' => ['type' => 'number', 'description' => 'Activities/experiences for the trip, CAD.'],
                                'food_estimate_dollars' => ['type' => 'number', 'description' => 'Food & dining for the trip, CAD.'],
                                'days' => [
                                    'type' => 'array',
                                    'description' => 'A day-by-day plan, one entry per day.',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'title' => ['type' => 'string', 'description' => 'Short day title, e.g. "Arrival & sunset dinner".'],
                                            'plan' => ['type' => 'string', 'description' => 'One sentence on the day’s plan.'],
                                            'spend_dollars' => ['type' => 'number', 'description' => 'Suggested spend that day, CAD.'],
                                        ],
                                        'required' => ['title', 'plan', 'spend_dollars'],
                                    ],
                                ],
                            ],
                            'required' => ['tier', 'destination', 'airport', 'why', 'hotel_name', 'flight_estimate_dollars', 'hotel_estimate_dollars', 'days'],
                        ],
                    ],
                ],
                'required' => ['packages'],
            ],
        ];

        $result = $ai->generateStructured($this->conciergeSystem(), $this->context($wedding, $data), $tool);

        return $this->normalizePackages($result['packages'] ?? []);
    }

    private function conciergeSystem(): string
    {
        return 'You are an elite honeymoon concierge. Design EXACTLY THREE all-inclusive honeymoon packages at '
            .'three budget tiers: "essential" (comfortably under their budget), "signature" (right at their budget '
            .'and the best fit for their vibe), and "dream" (an aspirational stretch). For each: a specific real '
            .'destination with its nearest airport IATA code, a warm short "why this one", a specific real hotel or '
            .'resort, realistic Canadian-dollar estimates (flights for two, the whole hotel stay, activities, food), '
            .'and a day-by-day plan — one line and a suggested daily spend per day, for the full number of nights. '
            .'Be concrete and tasteful: real places, real-ish prices, genuinely different tiers.';
    }

    /** @param  array<string,mixed>  $data */
    private function context(Wedding $wedding, array $data): string
    {
        $parts = ["The couple is {$wedding->name}."];

        if (filled($data['vibe'] ?? null)) {
            $parts[] = 'They want: '.trim($data['vibe']).'.';
        }
        if (filled($data['interests'] ?? null)) {
            $parts[] = 'Interests: '.trim($data['interests']).'.';
        }
        if (filled($data['departure'] ?? null)) {
            $parts[] = 'Flying from '.trim($data['departure']).'.';
        }
        if (isset($data['budget']) && (float) $data['budget'] > 0) {
            $parts[] = 'Total budget: about CAD $'.number_format((float) $data['budget']).'.';
        }

        $nights = $this->nights($data);
        if ($nights !== null) {
            $parts[] = "Trip length: {$nights} nights (".$data['start_date'].' to '.$data['end_date'].').';
        } else {
            $parts[] = 'Plan for about 7 nights.';
        }

        return implode(' ', $parts);
    }

    /** @param  array<string,mixed>  $data */
    private function nights(array $data): ?int
    {
        if (! filled($data['start_date'] ?? null) || ! filled($data['end_date'] ?? null)) {
            return null;
        }

        $n = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date']));

        return $n > 0 ? min($n, 30) : null;
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return array<int, array<string,mixed>>
     */
    private function normalizePackages(array $raw): array
    {
        return collect($raw)
            ->map(function ($p) {
                if (! is_array($p) || ! in_array($p['tier'] ?? null, self::TIERS, true)) {
                    return null;
                }

                $flight = $this->cents($p['flight_estimate_dollars'] ?? 0);
                $hotel = $this->cents($p['hotel_estimate_dollars'] ?? 0);
                $activities = $this->cents($p['activities_estimate_dollars'] ?? 0);
                $food = $this->cents($p['food_estimate_dollars'] ?? 0);

                $days = collect($p['days'] ?? [])
                    ->take(30)
                    ->map(fn ($d) => [
                        'title' => (string) ($d['title'] ?? ''),
                        'plan' => (string) ($d['plan'] ?? ''),
                        'spend_cents' => $this->cents($d['spend_dollars'] ?? 0),
                    ])
                    ->filter(fn ($d) => $d['title'] !== '')
                    ->values()
                    ->all();

                return [
                    'tier' => $p['tier'],
                    'destination' => (string) ($p['destination'] ?? ''),
                    'airport' => strtoupper(trim((string) ($p['airport'] ?? ''))),
                    'origin_airport' => strtoupper(trim((string) ($p['origin_airport'] ?? ''))),
                    'why' => (string) ($p['why'] ?? ''),
                    'hotel_name' => (string) ($p['hotel_name'] ?? ''),
                    'flight_cents' => $flight,
                    'hotel_cents' => $hotel,
                    'activities_cents' => $activities,
                    'food_cents' => $food,
                    'total_cents' => $flight + $hotel + $activities + $food,
                    'days' => $days,
                ];
            })
            ->filter(fn ($p) => $p !== null && $p['destination'] !== '')
            ->unique('tier')
            ->sortBy(fn ($p) => array_search($p['tier'], self::TIERS, true))
            ->values()
            ->all();
    }

    /** @return array<int, array{label:string, amount_cents:int}> */
    private function budgetFromPackage(array $pkg): array
    {
        $map = [
            'flight_cents' => 'Flights',
            'hotel_cents' => 'Hotel',
            'activities_cents' => 'Activities',
            'food_cents' => 'Food & dining',
        ];

        $items = [];
        foreach ($map as $key => $label) {
            if ((int) ($pkg[$key] ?? 0) > 0) {
                $items[] = ['label' => $label, 'amount_cents' => (int) $pkg[$key]];
            }
        }

        return $items;
    }

    private function cents(mixed $dollars): int
    {
        return (int) round(max(0, (float) $dollars) * 100);
    }
}

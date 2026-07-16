<?php

namespace App\Http\Controllers;

use App\Enums\WeddingSeason;
use App\Enums\WeddingVibe;
use App\Support\Budget\BudgetAllocator;
use App\Support\CurrentWedding;
use App\Support\OntarioCities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The shared onboarding-enrichment screen — one lightweight, low-friction
 * capture (city, budget band, aesthetic "vibe", season) shown once to a
 * newly-registered couple right after they create their first wedding. Every
 * field is optional and the couple can skip outright; the signal feeds
 * CoupleSegments personalization without three separate captures. Shown at
 * most once: idempotent on `settings['onboarding_enrichment_done']`.
 */
class OnboardingController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function show(): Response|RedirectResponse
    {
        $wedding = $this->current->get();

        if ($wedding === null || ($wedding->settings['onboarding_enrichment_done'] ?? false) === true) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding/wedding-details', [
            'wedding' => ['name' => $wedding->name],
            'bands' => BudgetAllocator::bands(),
            'cities' => collect(OntarioCities::all())
                ->map(fn (array $c, string $slug) => ['slug' => $slug, 'name' => $c['name']])
                ->values()
                ->all(),
            'vibes' => collect(WeddingVibe::cases())
                ->map(fn (WeddingVibe $v) => ['key' => $v->value, 'label' => $v->label()])
                ->all(),
            'seasons' => collect(WeddingSeason::cases())
                ->map(fn (WeddingSeason $s) => ['key' => $s->value, 'label' => $s->label()])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        $data = $request->validate([
            'band' => ['nullable', 'string', Rule::in(array_column(BudgetAllocator::bands(), 'key'))],
            'city' => ['nullable', 'string', Rule::in(array_keys(OntarioCities::all()))],
            'vibe' => ['nullable', 'string', Rule::in(WeddingVibe::values())],
            'season' => ['nullable', 'string', Rule::in(WeddingSeason::values())],
            'skip' => ['nullable', 'boolean'],
        ]);

        $settings = $wedding->settings ?? [];

        if (! empty($data['vibe'])) {
            $settings['vibe'] = $data['vibe'];
        }

        if (! empty($data['season'])) {
            $settings['season'] = $data['season'];
        }

        $settings['onboarding_enrichment_done'] = true;

        $update = ['settings' => $settings];

        if (! empty($data['city'])) {
            $update['city'] = $data['city'];
        }

        if (! empty($data['band'])) {
            $update['total_budget_cents'] = BudgetAllocator::centsForBand($data['band']);
        }

        $wedding->update($update);

        return redirect()->route('dashboard')->with('status', 'onboarding-enrichment-saved');
    }
}

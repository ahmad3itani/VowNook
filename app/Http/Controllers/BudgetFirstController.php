<?php

namespace App\Http\Controllers;

use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Support\Budget\BudgetAllocator;
use App\Support\CurrentWedding;
use App\Support\OntarioCities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Bring your budget, we'll make it work" — the couple gives a total (a band or
 * an exact number) and their Ontario city; VowNook splits it across categories,
 * tells them how realistic it is for their city + guest count, and can seed the
 * budget tracker in one click. Deterministic and free for every couple; the AI
 * "make it work" advisor is a separate paid layer.
 */
class BudgetFirstController extends Controller
{
    public function __construct(
        protected CurrentWedding $current,
        protected BudgetAllocator $allocator,
    ) {}

    public function show(): Response
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        $total = $wedding->total_budget_cents;
        $city = $wedding->city;
        $guestCount = $wedding->guests()->count();

        return Inertia::render('budget/plan', [
            'wedding' => [
                'total_budget_cents' => $total,
                'city' => $city,
                'city_name' => $city !== null ? OntarioCities::name($city) : null,
                'guest_count' => $guestCount,
            ],
            'bands' => BudgetAllocator::bands(),
            'cities' => collect(OntarioCities::all())
                ->map(fn (array $c, string $slug) => ['slug' => $slug, 'name' => $c['name']])
                ->values()
                ->all(),
            'allocation' => $total !== null ? $this->allocator->allocate($total) : null,
            'realism' => $total !== null ? $this->allocator->realism($total, $city, $guestCount) : null,
            'has_budget_items' => BudgetItem::forWedding($wedding->id)->exists(),
        ]);
    }

    /** Save the couple's total budget (band or exact) + city onto the wedding. */
    public function store(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        $data = $request->validate([
            'band' => ['nullable', 'string', Rule::in(array_column(BudgetAllocator::bands(), 'key'))],
            'exact_dollars' => ['nullable', 'numeric', 'min:1000', 'max:1000000'],
            'city' => ['nullable', 'string', Rule::in(array_keys(OntarioCities::all()))],
        ]);

        // Exact number wins; otherwise the chosen band's representative amount.
        $totalCents = null;
        if (! empty($data['exact_dollars'])) {
            $totalCents = (int) round((float) $data['exact_dollars'] * 100);
        } elseif (! empty($data['band'])) {
            $totalCents = BudgetAllocator::centsForBand($data['band']);
        }

        abort_if($totalCents === null, 422, 'Choose a budget range or enter an amount.');

        $wedding->update([
            'total_budget_cents' => $totalCents,
            'city' => $data['city'] ?? $wedding->city,
        ]);

        return back()->with('status', 'budget-plan-saved');
    }

    /**
     * Seed the budget tracker from the allocation — one category + a starter
     * "Estimated budget" item per line. Idempotent: re-applying never duplicates
     * the starter item, so the couple can safely refine and re-run.
     */
    public function apply(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');
        abort_if($wedding->total_budget_cents === null, 422, 'Set your budget first.');

        $rows = $this->allocator->allocate($wedding->total_budget_cents);

        $count = DB::transaction(function () use ($wedding, $rows) {
            $existing = BudgetCategory::where('wedding_id', $wedding->id)->pluck('id', 'name');
            $nextSort = (int) BudgetCategory::where('wedding_id', $wedding->id)->max('sort_order');
            $applied = 0;

            foreach ($rows as $row) {
                $name = $row['label'];

                if (! isset($existing[$name])) {
                    $category = BudgetCategory::create([
                        'wedding_id' => $wedding->id,
                        'name' => $name,
                        'sort_order' => ++$nextSort,
                    ]);
                    $existing[$name] = $category->id;
                }

                $alreadySeeded = BudgetItem::where('category_id', $existing[$name])
                    ->where('name', 'Estimated budget')
                    ->exists();

                if (! $alreadySeeded) {
                    BudgetItem::create([
                        'wedding_id' => $wedding->id,
                        'category_id' => $existing[$name],
                        'name' => 'Estimated budget',
                        'estimated_cents' => $row['amount_cents'],
                    ]);
                    $applied++;
                }
            }

            return $applied;
        });

        return redirect()->route('budget.index')->with('status', "budget-plan-applied-{$count}");
    }
}

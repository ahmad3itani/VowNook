<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetItemRequest;
use App\Models\BudgetItem;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $items = BudgetItem::query()
            ->forWedding($weddingId)
            ->with('category:id,name')
            ->orderBy('name')
            ->get();

        return Inertia::render('budget/index', [
            'items' => $items->map(fn (BudgetItem $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'estimated' => $i->estimated_cents / 100,
                'actual' => $i->actual_cents !== null ? $i->actual_cents / 100 : null,
                'paid' => $i->paid_cents / 100,
                'due_date' => $i->due_date?->toDateString(),
                'notes' => $i->notes,
                'category_id' => $i->category_id,
                'category_name' => $i->category?->name,
            ]),
            'categories' => $this->current->get()?->budgetCategories()
                ->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name']) ?? [],
            'stats' => $this->stats($items),
        ]);
    }

    public function store(BudgetItemRequest $request): RedirectResponse
    {
        $item = new BudgetItem($this->fromRequest($request));
        $item->wedding_id = $this->current->id();
        $item->save();

        return back()->with('status', 'budget-item-created');
    }

    public function update(BudgetItemRequest $request, BudgetItem $item): RedirectResponse
    {
        $this->authorizeTenant($item);

        $item->update($this->fromRequest($request));

        return back()->with('status', 'budget-item-updated');
    }

    public function destroy(BudgetItem $item): RedirectResponse
    {
        $this->authorizeTenant($item);

        $item->delete();

        return back()->with('status', 'budget-item-deleted');
    }

    protected function authorizeTenant(BudgetItem $item): void
    {
        abort_unless($item->wedding_id === $this->current->id(), 404);
    }

    /** Map validated dollar amounts to the integer-cents columns. */
    protected function fromRequest(BudgetItemRequest $request): array
    {
        $data = $request->validated();

        return [
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'estimated_cents' => $this->toCents($data['estimated_amount']),
            'actual_cents' => isset($data['actual_amount']) && $data['actual_amount'] !== null
                ? $this->toCents($data['actual_amount'])
                : null,
            'paid_cents' => $this->toCents($data['paid_amount']),
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    protected function toCents(int|float|string $dollars): int
    {
        return (int) round(((float) $dollars) * 100);
    }

    /** @param Collection<int, BudgetItem> $items */
    protected function stats(Collection $items): array
    {
        $estimated = $items->sum('estimated_cents');
        $projected = $items->sum(fn (BudgetItem $i) => $i->actual_cents ?? $i->estimated_cents);
        $paid = $items->sum('paid_cents');

        return [
            'estimated' => $estimated / 100,
            'projected' => $projected / 100,
            'paid' => $paid / 100,
            'outstanding' => ($projected - $paid) / 100,
        ];
    }
}

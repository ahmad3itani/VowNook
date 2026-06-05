<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetCategoryRequest;
use App\Models\BudgetCategory;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;

class BudgetCategoryController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function store(BudgetCategoryRequest $request): RedirectResponse
    {
        $category = new BudgetCategory($request->validated());
        $category->wedding_id = $this->current->id();
        $category->save();

        return back()->with('status', 'budget-category-created');
    }

    public function update(BudgetCategoryRequest $request, BudgetCategory $category): RedirectResponse
    {
        $this->authorizeTenant($category);

        $category->update($request->validated());

        return back()->with('status', 'budget-category-updated');
    }

    public function destroy(BudgetCategory $category): RedirectResponse
    {
        $this->authorizeTenant($category);

        $category->delete();

        return back()->with('status', 'budget-category-deleted');
    }

    protected function authorizeTenant(BudgetCategory $category): void
    {
        abort_unless($category->wedding_id === $this->current->id(), 404);
    }
}

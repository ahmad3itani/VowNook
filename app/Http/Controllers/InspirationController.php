<?php

namespace App\Http\Controllers;

use App\Enums\InspirationCategory;
use App\Http\Requests\InspirationItemRequest;
use App\Models\InspirationItem;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InspirationController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $items = InspirationItem::query()
            ->forWedding($weddingId)
            ->latest()
            ->get();

        return Inertia::render('inspiration/index', [
            'items' => $items->map(fn (InspirationItem $i) => [
                'id' => $i->id,
                'title' => $i->title,
                'category' => $i->category->value,
                'image_url' => $i->image_url,
                'link_url' => $i->link_url,
                'notes' => $i->notes,
            ]),
            'stats' => [
                'total' => $items->count(),
                'with_image' => $items->whereNotNull('image_url')->count(),
                'categories' => $items->pluck('category')->unique()->count(),
            ],
            'options' => [
                'categories' => array_map(
                    fn (InspirationCategory $c) => ['value' => $c->value, 'label' => $c->label()],
                    InspirationCategory::cases(),
                ),
            ],
        ]);
    }

    public function store(InspirationItemRequest $request): RedirectResponse
    {
        $item = new InspirationItem($request->validated());
        $item->wedding_id = $this->current->id();
        $item->save();

        return back()->with('status', 'inspiration-created');
    }

    public function update(InspirationItemRequest $request, InspirationItem $item): RedirectResponse
    {
        $this->authorizeTenant($item);

        $item->update($request->validated());

        return back()->with('status', 'inspiration-updated');
    }

    public function destroy(InspirationItem $item): RedirectResponse
    {
        $this->authorizeTenant($item);

        $item->delete();

        return back()->with('status', 'inspiration-deleted');
    }

    protected function authorizeTenant(InspirationItem $item): void
    {
        abort_unless($item->wedding_id === $this->current->id(), 404);
    }
}

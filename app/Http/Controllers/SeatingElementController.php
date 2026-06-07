<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeatingElementRequest;
use App\Models\SeatingElement;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SeatingElementController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function store(SeatingElementRequest $request): RedirectResponse
    {
        $element = new SeatingElement($request->validated());
        $element->wedding_id = $this->current->id();
        $element->save();

        return back()->with('status', 'element-created');
    }

    public function update(SeatingElementRequest $request, SeatingElement $element): RedirectResponse
    {
        $this->authorizeTenant($element);

        $element->update($request->validated());

        return back()->with('status', 'element-updated');
    }

    public function destroy(SeatingElement $element): RedirectResponse
    {
        $this->authorizeTenant($element);

        $element->delete();

        return back()->with('status', 'element-deleted');
    }

    /** Persist a dragged/resized element without a full form round-trip. */
    public function move(Request $request, SeatingElement $element): RedirectResponse
    {
        $this->authorizeTenant($element);

        $data = $request->validate([
            'position_x' => ['required', 'integer', 'min:0', 'max:100'],
            'position_y' => ['required', 'integer', 'min:0', 'max:100'],
            'width' => ['nullable', 'integer', 'min:4', 'max:100'],
            'height' => ['nullable', 'integer', 'min:4', 'max:100'],
            'rotation' => ['nullable', 'integer', 'min:0', 'max:359'],
        ]);

        $element->update(array_filter($data, fn ($v) => $v !== null));

        return back()->with('status', 'element-moved');
    }

    protected function authorizeTenant(SeatingElement $element): void
    {
        abort_unless($element->wedding_id === $this->current->id(), 404);
    }
}

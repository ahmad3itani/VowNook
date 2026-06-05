<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuestGroupRequest;
use App\Models\GuestGroup;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;

class GuestGroupController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function store(GuestGroupRequest $request): RedirectResponse
    {
        $group = new GuestGroup($request->validated());
        $group->wedding_id = $this->current->id();
        $group->save();

        return back()->with('status', 'group-created');
    }

    public function update(GuestGroupRequest $request, GuestGroup $group): RedirectResponse
    {
        $this->authorizeTenant($group);

        $group->update($request->validated());

        return back()->with('status', 'group-updated');
    }

    public function destroy(GuestGroup $group): RedirectResponse
    {
        $this->authorizeTenant($group);

        $group->delete();

        return back()->with('status', 'group-deleted');
    }

    protected function authorizeTenant(GuestGroup $group): void
    {
        abort_unless($group->wedding_id === $this->current->id(), 404);
    }
}

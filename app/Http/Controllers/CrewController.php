<?php

namespace App\Http\Controllers;

use App\Enums\CrewRole;
use App\Http\Requests\CrewMemberRequest;
use App\Models\CrewMember;
use App\Support\CurrentWedding;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CrewController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function index(): Response
    {
        $weddingId = $this->current->id();

        $members = CrewMember::query()
            ->forWedding($weddingId)
            ->orderBy('name')
            ->get();

        return Inertia::render('crew/index', [
            'members' => $members->map(fn (CrewMember $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'role' => $m->role->value,
                'email' => $m->email,
                'phone' => $m->phone,
                'notes' => $m->notes,
            ]),
            'stats' => [
                'total' => $members->count(),
                'roles' => $members->pluck('role')->unique()->count(),
                'with_contact' => $members
                    ->filter(fn (CrewMember $m) => $m->email !== null || $m->phone !== null)
                    ->count(),
            ],
            'options' => [
                'roles' => array_map(
                    fn (CrewRole $r) => ['value' => $r->value, 'label' => $r->label()],
                    CrewRole::cases(),
                ),
            ],
        ]);
    }

    public function store(CrewMemberRequest $request): RedirectResponse
    {
        $member = new CrewMember($request->validated());
        $member->wedding_id = $this->current->id();
        $member->save();

        return back()->with('status', 'crew-created');
    }

    public function update(CrewMemberRequest $request, CrewMember $member): RedirectResponse
    {
        $this->authorizeTenant($member);

        $member->update($request->validated());

        return back()->with('status', 'crew-updated');
    }

    public function destroy(CrewMember $member): RedirectResponse
    {
        $this->authorizeTenant($member);

        $member->delete();

        return back()->with('status', 'crew-deleted');
    }

    protected function authorizeTenant(CrewMember $member): void
    {
        abort_unless($member->wedding_id === $this->current->id(), 404);
    }
}

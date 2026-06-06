<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Support\CurrentWedding;
use App\Support\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages who can access the active wedding and with what role. The wedding
 * owner is immutable here — their role can neither be changed nor revoked.
 */
class CollaboratorController extends Controller
{
    public function __construct(
        protected CurrentWedding $current,
        protected PlanLimits $limits,
    ) {}

    public function index(Request $request): Response
    {
        $wedding = $this->current->get();
        $authId = $request->user()->id;

        $members = $wedding->members()->orderBy('name')->get();

        return Inertia::render('collaborators/index', [
            'members' => $members->map(function (User $m) use ($wedding, $authId) {
                $isOwner = $m->id === $wedding->owner_id;

                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'role' => $isOwner ? Role::Owner->value : $m->pivot->role,
                    'is_owner' => $isOwner,
                    'is_self' => $m->id === $authId,
                ];
            })->values(),
            'options' => [
                'roles' => array_map(
                    fn (Role $r) => [
                        'value' => $r->value,
                        'label' => $r->label(),
                        'description' => $r->description(),
                    ],
                    Role::assignable(),
                ),
            ],
            'plan' => [
                'used' => $this->limits->collaboratorCount($wedding),
                'limit' => $this->limits->limit($wedding, 'max_collaborators_per_wedding'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();

        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in($this->assignableValues())],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No account exists with that email address.',
            ]);
        }

        if ($user->id === $wedding->owner_id || $wedding->members()->whereKey($user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'That person already has access to this wedding.',
            ]);
        }

        $this->limits->enforceCollaborators($wedding);

        $wedding->members()->attach($user->id, [
            'role' => $data['role'],
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        return back()->with('status', 'collaborator-added');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $wedding = $this->current->get();
        $this->ensureManageableMember($user);

        $data = $request->validate([
            'role' => ['required', Rule::in($this->assignableValues())],
        ]);

        $wedding->members()->updateExistingPivot($user->id, ['role' => $data['role']]);

        return back()->with('status', 'collaborator-updated');
    }

    public function destroy(User $user): RedirectResponse
    {
        $wedding = $this->current->get();
        $this->ensureManageableMember($user);

        $wedding->members()->detach($user->id);

        if ($user->current_wedding_id === $wedding->id) {
            $user->forceFill(['current_wedding_id' => null])->save();
        }

        return back()->with('status', 'collaborator-removed');
    }

    /** The target must be a member of this wedding and must not be the owner. */
    protected function ensureManageableMember(User $user): void
    {
        $wedding = $this->current->get();

        abort_if($user->id === $wedding->owner_id, 403, 'The owner cannot be modified.');
        abort_unless($wedding->members()->whereKey($user->id)->exists(), 404);
    }

    /** @return list<string> */
    protected function assignableValues(): array
    {
        return array_map(fn (Role $r) => $r->value, Role::assignable());
    }
}

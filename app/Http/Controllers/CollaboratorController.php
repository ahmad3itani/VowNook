<?php

namespace App\Http\Controllers;

use App\Enums\PermissionLevel;
use App\Enums\Role;
use App\Enums\Section;
use App\Models\User;
use App\Models\WeddingInvitation;
use App\Notifications\WeddingInvitationNotification;
use App\Support\CollaboratorAccess;
use App\Support\CurrentWedding;
use App\Support\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages who can access the active wedding and with what role + per-section
 * access. People are brought in by email invitation (they need not have an
 * account yet); the owner is immutable here.
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

        $pending = WeddingInvitation::query()
            ->where('wedding_id', $wedding->id)
            ->pending()
            ->latest()
            ->get();

        return Inertia::render('collaborators/index', [
            'members' => $members->map(function (User $m) use ($wedding, $authId) {
                $isOwner = $m->id === $wedding->owner_id;
                // Cast guards against a null/blank pivot role (tryFrom(null) is a TypeError).
                $role = $isOwner ? Role::Owner : (Role::tryFrom((string) $m->pivot->role) ?? Role::Collaborator);
                $override = $this->decodeOverride($m->pivot->permissions);

                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'role' => $role->value,
                    'is_owner' => $isOwner,
                    'is_self' => $m->id === $authId,
                    'access' => CollaboratorAccess::effective($role, $override),
                ];
            })->values(),
            'invitations' => $pending->map(fn (WeddingInvitation $i) => [
                'id' => $i->id,
                'email' => $i->email,
                'role' => $i->role->value,
                'access' => CollaboratorAccess::effective($i->role, $i->permissions),
                'invited_at' => $i->created_at?->toDateString(),
                'expired' => ! $i->isAcceptable(),
            ])->values(),
            'options' => $this->options(),
            'plan' => [
                'used' => $this->limits->collaboratorCount($wedding),
                'limit' => $this->limits->limit($wedding, 'max_collaborators_per_wedding'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();

        $data = $this->validateInvite($request);
        $role = Role::from($data['role']);
        $email = mb_strtolower($data['email']);

        // Already a member?
        $existing = $wedding->members()->where('email', $email)->exists()
            || ($wedding->owner && mb_strtolower($wedding->owner->email) === $email);
        if ($existing) {
            throw ValidationException::withMessages(['email' => 'That person already has access to this wedding.']);
        }

        if (WeddingInvitation::where('wedding_id', $wedding->id)->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'An invitation has already been sent to that email.']);
        }

        $this->limits->enforceCollaborators($wedding);

        $invitation = WeddingInvitation::create([
            'wedding_id' => $wedding->id,
            'email' => $email,
            'role' => $role->value,
            'permissions' => CollaboratorAccess::diff($role, $data['permissions'] ?? []),
            'invited_by' => $request->user()->id,
        ]);

        $this->sendInvite($invitation);

        return back()->with('status', 'invitation-sent');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $wedding = $this->current->get();
        $this->ensureManageableMember($user);

        $data = $request->validate([
            'role' => ['required', Rule::in($this->assignableValues())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(PermissionLevel::values())],
        ]);
        $role = Role::from($data['role']);
        $override = CollaboratorAccess::diff($role, $data['permissions'] ?? []);

        $wedding->members()->updateExistingPivot($user->id, [
            'role' => $role->value,
            // The pivot column is not model-cast, so JSON-encode the override.
            'permissions' => $override !== null ? json_encode($override) : null,
        ]);

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

    public function resend(WeddingInvitation $invitation): RedirectResponse
    {
        $this->ensureOwnInvitation($invitation);

        $invitation->forceFill([
            'token' => WeddingInvitation::freshToken(),
            'expires_at' => now()->addDays(14),
        ])->save();

        $this->sendInvite($invitation);

        return back()->with('status', 'invitation-resent');
    }

    public function revoke(WeddingInvitation $invitation): RedirectResponse
    {
        $this->ensureOwnInvitation($invitation);

        $invitation->delete();

        return back()->with('status', 'invitation-revoked');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function sendInvite(WeddingInvitation $invitation): void
    {
        $invitation->loadMissing('wedding', 'inviter');

        try {
            Notification::route('mail', $invitation->email)->notify(
                new WeddingInvitationNotification(
                    $invitation,
                    $invitation->inviter?->name ?? 'A couple',
                    $invitation->role->label(),
                ),
            );
        } catch (\Throwable $e) {
            // A mail-transport failure (e.g. an unverified Resend domain) must
            // not 500 the page. The invitation is saved; surface a clear,
            // actionable error instead of crashing.
            report($e);

            throw ValidationException::withMessages([
                'email' => 'The invitation was saved, but the email could not be sent. Verify your sending domain at resend.com/domains, then resend.',
            ]);
        }
    }

    /** @return array<string,mixed> */
    protected function validateInvite(Request $request): array
    {
        return $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in($this->assignableValues())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(PermissionLevel::values())],
        ]);
    }

    /** Decode a pivot permissions value that may arrive as a JSON string. */
    protected function decodeOverride(mixed $permissions): ?array
    {
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        return is_array($permissions) ? $permissions : null;
    }

    protected function ensureManageableMember(User $user): void
    {
        $wedding = $this->current->get();

        abort_if($user->id === $wedding->owner_id, 403, 'The owner cannot be modified.');
        abort_unless($wedding->members()->whereKey($user->id)->exists(), 404);
    }

    protected function ensureOwnInvitation(WeddingInvitation $invitation): void
    {
        abort_unless($invitation->wedding_id === $this->current->id(), 404);
    }

    /** @return list<string> */
    protected function assignableValues(): array
    {
        return array_map(fn (Role $r) => $r->value, Role::assignable());
    }

    /** @return array<string,mixed> */
    protected function options(): array
    {
        $roleDefaults = [];
        foreach (Role::assignable() as $role) {
            $roleDefaults[$role->value] = CollaboratorAccess::effective($role, null);
        }

        return [
            'roles' => array_map(
                fn (Role $r) => ['value' => $r->value, 'label' => $r->label(), 'description' => $r->description()],
                Role::assignable(),
            ),
            'sections' => array_map(
                fn (Section $s) => ['value' => $s->value, 'label' => $s->label()],
                Section::cases(),
            ),
            'levels' => array_map(
                fn (PermissionLevel $l) => $l->value,
                PermissionLevel::cases(),
            ),
            'role_defaults' => $roleDefaults,
        ];
    }
}

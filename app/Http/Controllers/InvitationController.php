<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\WeddingInvitation;
use App\Support\CollaboratorAccess;
use App\Support\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The invitee's side of a collaboration invite: a public landing page that
 * explains the invitation, and an authenticated accept action that joins the
 * wedding. The link binds to the invited email, so it can't be reused by a
 * different account.
 */
class InvitationController extends Controller
{
    public function __construct(protected PlanLimits $limits) {}

    /** Public landing page for an invitation token. */
    public function show(Request $request, string $token): Response
    {
        $invitation = WeddingInvitation::with(['wedding', 'inviter'])
            ->where('token', $token)
            ->first();

        $user = $request->user();

        // For a logged-out invitee, set the intended URL so Fortify's
        // redirect()->intended() returns them here after they sign in / up.
        if ($user === null && $invitation && $invitation->isAcceptable()) {
            $request->session()->put('url.intended', url('/invitations/'.$token));
        }

        $role = $invitation?->role ?? Role::Collaborator;

        return Inertia::render('invitations/accept', [
            'invitation' => $invitation ? [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'wedding_name' => $invitation->wedding?->name,
                'inviter_name' => $invitation->inviter?->name,
                'access' => CollaboratorAccess::effective($role, $invitation->permissions),
                'acceptable' => $invitation->isAcceptable(),
                'accepted' => $invitation->accepted_at !== null,
            ] : null,
            'sections' => array_map(
                fn ($s) => ['value' => $s->value, 'label' => $s->label()],
                \App\Enums\Section::cases(),
            ),
            'auth_email' => $user?->email,
            'email_matches' => $user !== null && $invitation !== null
                && mb_strtolower($user->email) === mb_strtolower($invitation->email),
        ]);
    }

    /** Accept the invitation (authenticated, email must match). */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = WeddingInvitation::where('token', $token)->firstOrFail();
        $user = $request->user();

        abort_unless($invitation->isAcceptable(), 410, 'This invitation is no longer valid.');
        abort_unless(
            mb_strtolower($user->email) === mb_strtolower($invitation->email),
            403,
            'This invitation was sent to a different email address.',
        );

        $wedding = $invitation->wedding;

        // Already a member (e.g. accepted twice) — just switch to it.
        if (! $wedding->members()->whereKey($user->id)->exists() && $user->id !== $wedding->owner_id) {
            $this->limits->enforceCollaborators($wedding);

            $wedding->members()->attach($user->id, [
                'role' => $invitation->role->value,
                // The pivot column is not model-cast, so JSON-encode the override.
                'permissions' => $invitation->permissions ? json_encode($invitation->permissions) : null,
                'invited_at' => $invitation->created_at,
                'accepted_at' => now(),
            ]);
        }

        $invitation->forceFill(['accepted_at' => now()])->save();

        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return redirect()->route('dashboard')->with('status', 'invitation-accepted');
    }
}

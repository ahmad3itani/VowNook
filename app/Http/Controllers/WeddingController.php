<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Models\Wedding;
use App\Notifications\CollaboratorAdded;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Creating a wedding workspace. Used by couples (first wedding from the empty
 * dashboard) and planners (new client wedding from the HQ, optionally
 * inviting the couple straight away).
 */
class WeddingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Vendor business accounts don't own wedding workspaces.
        abort_if($user->isVendor(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'event_date' => ['nullable', 'date', 'after:today'],
            'couple_email' => ['nullable', 'email'],
        ]);

        // Plan cap: owners are limited by max_weddings (null = unlimited).
        $max = $user->planLimit('max_weddings');
        $owned = $user->ownedWeddings()->count();

        if ($max !== null && $owned >= $max) {
            throw ValidationException::withMessages([
                'name' => "Your plan is limited to {$max} wedding".($max === 1 ? '' : 's').'. Upgrade to add more.',
            ]);
        }

        $couple = null;

        if (! empty($data['couple_email'])) {
            $couple = User::where('email', $data['couple_email'])->first();

            if (! $couple) {
                throw ValidationException::withMessages([
                    'couple_email' => 'No account exists with that email address. Ask your client to register first.',
                ]);
            }

            if ($couple->id === $user->id) {
                throw ValidationException::withMessages([
                    'couple_email' => 'That is your own email address.',
                ]);
            }
        }

        $wedding = Wedding::create([
            'owner_id' => $user->id,
            'name' => $data['name'],
            'event_date' => $data['event_date'] ?? null,
        ]);

        if ($couple) {
            $wedding->members()->attach($couple->id, [
                'role' => Role::Partner->value,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            $couple->notify(new CollaboratorAdded($wedding, Role::Partner->value));
        }

        // Make it the active workspace and land inside it (planners land
        // back on the HQ where the new card appears first).
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return redirect()
            ->route($user->isPlanner() ? 'planner.dashboard' : 'dashboard')
            ->with('status', 'wedding-created');
    }
}

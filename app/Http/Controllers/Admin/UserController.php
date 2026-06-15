<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin oversight of every account on the platform, with the ability to change
 * a user's plan (e.g. to comp or upgrade for support).
 */
class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->withCount('ownedWeddings')
            ->latest()
            ->get(['id', 'name', 'email', 'account_type', 'plan', 'is_admin', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'account_type' => $u->account_type->value,
                'plan' => $u->plan,
                'is_admin' => (bool) $u->is_admin,
                'weddings_count' => $u->owned_weddings_count,
                'created_at' => $u->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/users', [
            'users' => $users,
            'plans' => array_map(
                fn (string $key, array $tier) => ['value' => $key, 'label' => $tier['name']],
                array_keys(config('plans.tiers')),
                array_values(config('plans.tiers')),
            ),
        ]);
    }

    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('plans.tiers')))],
        ]);

        $user->forceFill(['plan' => $data['plan']])->save();

        return back()->with('status', 'user-plan-updated');
    }
}

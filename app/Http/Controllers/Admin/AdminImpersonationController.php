<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "View as user" — lets a platform admin sign in as any non-admin account
 * (couple, vendor or planner) to provide support, seeing exactly what that user
 * sees. The admin's own id is stashed in the session so they can switch back.
 * Every start/stop is audited and surfaced via a persistent banner.
 */
class AdminImpersonationController extends Controller
{
    /** Begin impersonating the given user. */
    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        // Never impersonate another admin, and never nest impersonation.
        abort_if($user->is_admin, 403, 'Admins cannot be impersonated.');
        abort_if($request->session()->has('impersonator_id'), 409);

        // Leaving any couple support session avoids two contexts at once.
        $request->session()->forget('support_wedding_id');
        $request->session()->put('impersonator_id', $admin->id);

        ActivityLogger::log('admin.impersonate.start', $user, actor: $admin);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /** Stop impersonating and return to the admin account. */
    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect()->route('dashboard');
        }

        $impersonated = $request->user();
        $admin = User::find($impersonatorId);

        if (! $admin) {
            Auth::logout();

            return redirect()->route('login');
        }

        Auth::login($admin);

        ActivityLogger::log('admin.impersonate.stop', $impersonated, actor: $admin);

        return $impersonated
            ? redirect()->route('admin.users.show', $impersonated)
            : redirect()->route('admin.users.index');
    }
}

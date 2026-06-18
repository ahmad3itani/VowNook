<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out and blocks any user whose account an admin has suspended. An admin
 * impersonating a suspended user (for investigation) is exempt — that session
 * carries an `impersonator_id`.
 */
class EnsureNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isSuspended() && ! $request->session()->has('impersonator_id')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('suspended');
        }

        return $next($request);
    }
}

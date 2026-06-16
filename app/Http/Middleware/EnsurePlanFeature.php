<?php

namespace App\Http\Middleware;

use App\Support\CurrentWedding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind a paid plan capability (e.g. `seating`). Entitlement
 * follows the active wedding's OWNER plan, so collaborators on a Premium
 * wedding inherit access. Admins (incl. support mode) always pass. Free users
 * are redirected to the plan page (GET) or get a 403 (mutations) so the
 * front-end can prompt an upgrade.
 */
class EnsurePlanFeature
{
    public function __construct(protected CurrentWedding $current) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user?->is_admin) {
            return $next($request);
        }

        $owner = $this->current->get()?->owner;

        if ($owner && $owner->canUseFeature($feature)) {
            return $next($request);
        }

        if ($request->isMethod('get') && ! $request->expectsJson()) {
            return redirect()->route('plan.edit');
        }

        abort(403, 'This is a paid-plan feature. Upgrade to unlock it.');
    }
}

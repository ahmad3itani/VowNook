<?php

namespace App\Http\Middleware;

use App\Models\Wedding;
use App\Support\CurrentWedding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's active wedding and binds it into the
 * request-scoped CurrentWedding singleton. The active wedding is whichever the
 * user last selected (users.current_wedding_id); if that is missing or no
 * longer accessible, it falls back to their first accessible wedding.
 */
class SetCurrentWedding
{
    public function __construct(protected CurrentWedding $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $accessible = $user->accessibleWeddings();

            $wedding = null;

            if ($user->current_wedding_id) {
                $wedding = $accessible->firstWhere('id', $user->current_wedding_id);
            }

            if (! $wedding) {
                $wedding = $accessible->first();

                if ($wedding && $user->current_wedding_id !== $wedding->id) {
                    $user->forceFill(['current_wedding_id' => $wedding->id])->saveQuietly();
                }
            }

            $this->current->set($wedding instanceof Wedding ? $wedding : null);
        }

        return $next($request);
    }
}

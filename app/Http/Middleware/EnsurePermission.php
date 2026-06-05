<?php

namespace App\Http\Middleware;

use App\Enums\PermissionLevel;
use App\Enums\Section;
use App\Services\PermissionService;
use App\Support\CurrentWedding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware guarding a section of the active wedding.
 *
 * Usage: ->middleware('permission:budget,write')  (level defaults to read)
 */
class EnsurePermission
{
    public function __construct(
        protected CurrentWedding $current,
        protected PermissionService $permissions,
    ) {}

    public function handle(Request $request, Closure $next, string $section, string $level = 'read'): Response
    {
        $user = $request->user();
        $wedding = $this->current->get();

        abort_unless($user && $wedding, 403, 'No active wedding.');

        $sectionEnum = Section::tryFrom($section);
        $levelEnum = PermissionLevel::tryFrom($level);

        abort_if($sectionEnum === null || $levelEnum === null, 500, 'Invalid permission definition.');

        abort_unless(
            $this->permissions->allows($user, $wedding, $sectionEnum, $levelEnum),
            403,
            'You do not have permission to access this section.',
        );

        return $next($request);
    }
}

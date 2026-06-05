<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use App\Support\CurrentWedding;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $current = app(CurrentWedding::class)->get();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'isAdmin' => (bool) $user?->is_admin,
            ],
            'wedding' => [
                'active' => $current ? [
                    'id' => $current->id,
                    'name' => $current->name,
                    'slug' => $current->slug,
                    'event_date' => $current->event_date?->toDateString(),
                ] : null,
                'list' => $user
                    ? $user->accessibleWeddings()
                        ->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'slug' => $w->slug])
                        ->values()
                    : [],
                'permissions' => ($user && $current)
                    ? app(PermissionService::class)->mapFor($user, $current)
                    : (object) [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

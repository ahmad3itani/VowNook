<?php

namespace App\Http\Middleware;

use App\Models\Translation;
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

        // Admin "support mode" — viewing another couple's workspace. Drives the
        // support banner in the couple chrome; couples never see this.
        $support = ($user?->is_admin && $request->session()->has('support_wedding_id') && $current)
            ? ['active' => true, 'wedding' => ['name' => $current->name]]
            : null;

        // Admin "view as user" — the session belongs to an admin signed in as
        // this user. Drives the impersonation banner shown in every role's chrome.
        $impersonation = ($user && $request->session()->has('impersonator_id'))
            ? ['active' => true, 'user' => [
                'name' => $user->name,
                'account_type' => $user->account_type?->value ?? 'couple',
            ]]
            : null;

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
            'support' => $support,
            'impersonation' => $impersonation,
            'notifications' => $user ? [
                'unread' => $user->unreadNotifications()->count(),
                'items' => $user->notifications()->latest()->limit(8)->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'read' => $n->read_at !== null,
                    'title' => $n->data['title'] ?? 'Notification',
                    'body' => $n->data['body'] ?? null,
                    'url' => $n->data['url'] ?? null,
                    'icon' => $n->data['icon'] ?? null,
                    'created_at' => $n->created_at?->toIso8601String(),
                ])->values(),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            'locales' => Translation::LOCALES,
            'translations' => Translation::forLocale(app()->getLocale()),
        ];
    }
}

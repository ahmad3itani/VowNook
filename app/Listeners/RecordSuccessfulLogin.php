<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Login;

/**
 * Stamps last-login metadata and writes a login audit entry on every real
 * sign-in. Admin impersonation swaps the auth user via the same Login event, so
 * those internal swaps (the impersonate start/stop routes) are excluded.
 */
class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if (request()?->routeIs('admin.users.impersonate', 'impersonate.stop')) {
            return;
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()?->ip(),
        ])->saveQuietly();

        ActivityLogger::log('auth.login', $user, actor: $user);
    }
}

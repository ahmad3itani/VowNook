<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureNotSuspended;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PersistConversionFlash;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetCurrentVendorProfile;
use App\Http\Middleware\SetCurrentWedding;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Stripe signs webhooks itself; CSRF doesn't apply to server-to-server POSTs.
        // The shop checkout is POSTed by a static page (no session/token) — it
        // creates nothing sensitive (a pending order + a Stripe redirect) and is
        // hard-throttled on the route.
        $middleware->validateCsrfTokens(except: ['stripe/webhook', 'api/shop/checkout', 'api/shop/newsletter']);

        $middleware->web(append: [
            HandleAppearance::class,
            SecurityHeaders::class,
            SetLocale::class,
            EnsureNotSuspended::class,
            SetCurrentWedding::class,
            SetCurrentVendorProfile::class,
            HandleInertiaRequests::class,
            PersistConversionFlash::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'permission' => EnsurePermission::class,
            'admin' => EnsureAdmin::class,
            'plan.feature' => EnsurePlanFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\Translation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the admin-configured application locale for the request, falling back
 * to the framework default when none is set or the value is unsupported.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Setting::get('app_locale', config('app.locale'));

        if (! array_key_exists($locale, Translation::LOCALES)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}

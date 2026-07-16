<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a flashed `conversion` event (see App\Support\Conversions) alive across
 * redirect chains — e.g. register → dashboard → email/verify — so the
 * client-side tracker still receives it on the final rendered page. Laravel flash
 * survives only one request, which a multi-hop redirect would otherwise swallow
 * before the browser ever sees it. Only `conversion` is kept, so status/error
 * flashes still expire normally.
 */
class PersistConversionFlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isRedirect()
            && $request->hasSession()
            && $request->session()->has('conversion')) {
            $request->session()->keep(['conversion']);
        }

        return $response;
    }
}

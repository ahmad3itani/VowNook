<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds defensive HTTP response headers on every web response. These mitigate
 * clickjacking, MIME-sniffing and referrer leakage, and (over HTTPS) instruct
 * browsers to stay on HTTPS. Kept deliberately conservative so it never breaks
 * the Inertia/Vite app — no Content-Security-Policy is set here because a CSP
 * tight enough to matter needs per-asset nonces; that is tracked as a
 * post-launch hardening item.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Disallow framing entirely — this app is never embedded.
            'X-Frame-Options' => 'DENY',
            // Stop browsers from MIME-sniffing a response away from its type.
            'X-Content-Type-Options' => 'nosniff',
            // Don't leak full URLs (which can contain slugs) to other origins.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // Drop powerful features the app doesn't use.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(self)',
        ];

        // HSTS only makes sense — and is only safe — over HTTPS.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}

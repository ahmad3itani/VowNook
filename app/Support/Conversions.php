<?php

namespace App\Support;

/**
 * Flashes a conversion event for the client-side tracker (in app.tsx) to fire on
 * the next page load — once, into both GA4 (`gtag('event', ga, params)`) and the
 * Meta Pixel (`fbq('track', meta, params)`).
 *
 * Consent-gated by design: fbq/gtag are only defined (and only actually send)
 * after the visitor accepts cookies, so flashing here is always safe.
 *
 * Monetary `value` is in dollars (not cents) and `currency` is a 3-letter code —
 * the shapes GA4 and Meta both expect; each ignores params it doesn't use.
 */
class Conversions
{
    public static function flash(string $ga, string $meta, array $params = []): void
    {
        session()->flash('conversion', [
            'ga' => $ga,
            'meta' => $meta,
            'params' => (object) $params,
        ]);
    }
}

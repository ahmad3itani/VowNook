<?php

namespace App\Support\Affiliates;

use Carbon\CarbonInterface;

/**
 * Builds affiliate links for the wedding-website "Travel & Stays" section.
 *
 * Currently backed by Stay22 — an event-focused engine that renders a map of
 * hotels and short-stays near a venue, aggregating Booking.com, Expedia, Airbnb
 * and others, with commission tracked against our account. Disclosure is shown
 * on the public page; the block degrades to nothing when no id is configured.
 */
class TravelAffiliates
{
    /** The partner shown to guests in the disclosure line. */
    public const PARTNER = 'Stay22';

    public function isConfigured(): bool
    {
        return filled(config('affiliates.stay22.id'));
    }

    /**
     * The Stay22 map embed URL for stays near a venue, or null when the affiliate
     * isn't configured or there's no location to search. Stay22 geocodes the
     * address itself, so no maps API of our own is needed.
     */
    public function stay22EmbedUrl(?string $venueName, ?string $address, ?CarbonInterface $eventDate = null): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // Stay22 needs somewhere to search. Prefer the address; fall back to name.
        $location = trim(implode(', ', array_filter([$venueName, $address])));

        if ($location === '') {
            return null;
        }

        // aid is recommended first; http_build_query preserves insertion order.
        $params = ['aid' => config('affiliates.stay22.id')];
        $params['address'] = $location;

        if (filled($venueName)) {
            // Overrides the widget title with the couple's venue name.
            $params['venue'] = $venueName;
        }

        if ($eventDate) {
            // Guests usually need the night of the wedding — show live rates for it.
            $params['checkin'] = $eventDate->toDateString();
            $params['checkout'] = $eventDate->copy()->addDay()->toDateString();
        }

        $params['maincolor'] = ltrim((string) config('affiliates.stay22.maincolor'), '#');
        $params['campaign'] = (string) config('affiliates.stay22.campaign');

        return rtrim((string) config('affiliates.stay22.embed_base'), '?').'?'.http_build_query($params);
    }
}

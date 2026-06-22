<?php

namespace App\Support\Affiliates;

use Carbon\CarbonInterface;

/**
 * Builds affiliate links for travel — the wedding-website "Travel & Stays"
 * section and the couple's honeymoon planner.
 *
 *  - Stay22 renders a map of hotels/short-stays near a location (Booking, Expedia,
 *    Airbnb …), commission tracked against our AID.
 *  - Travelpayouts/Aviasales powers a flight search to a destination airport,
 *    tracked against our marker.
 *
 * Each is inert until its key is configured; callers get null and skip the block.
 */
class TravelAffiliates
{
    /** The partner shown to guests in the disclosure line. */
    public const PARTNER = 'Stay22';

    /** The flight-search partner shown in the disclosure line. */
    public const FLIGHTS_PARTNER = 'Aviasales';

    /** The experiences partner shown in the disclosure line. */
    public const EXPERIENCES_PARTNER = 'GetYourGuide';

    /**
     * A GetYourGuide search deep link for an experience at a destination, with
     * our partner id when configured. Always returns a usable search URL.
     */
    public function experienceUrl(string $name, ?string $destination = null): string
    {
        $query = trim($name.' '.($destination ?? ''));

        $params = ['q' => $query];

        if (filled(config('affiliates.getyourguide.partner_id'))) {
            $params['partner_id'] = config('affiliates.getyourguide.partner_id');
        }

        return rtrim((string) config('affiliates.getyourguide.search_base'), '?').'?'.http_build_query($params);
    }

    public function isConfigured(): bool
    {
        return filled(config('affiliates.stay22.id'));
    }

    public function flightsConfigured(): bool
    {
        return filled(config('affiliates.travelpayouts.marker'));
    }

    // ── Stay22 (hotels) ──────────────────────────────────────────────────────

    /**
     * Stays near a wedding venue for the wedding weekend (check-in the wedding
     * night, check-out the next day).
     */
    public function stay22EmbedUrl(?string $venueName, ?string $address, ?CarbonInterface $eventDate = null): ?string
    {
        $location = trim(implode(', ', array_filter([$venueName, $address])));

        return $this->buildStay22Url($venueName, $location, $eventDate, $eventDate?->copy()->addDay());
    }

    /** Stays at a honeymoon destination across the couple's chosen date range. */
    public function stay22DestinationUrl(?string $place, ?CarbonInterface $checkin = null, ?CarbonInterface $checkout = null): ?string
    {
        return $this->buildStay22Url($place, trim((string) $place), $checkin, $checkout);
    }

    /**
     * The Stay22 map embed URL, or null when the affiliate isn't configured or
     * there's no location to search. Stay22 geocodes the location itself, so no
     * maps API of our own is needed.
     */
    private function buildStay22Url(?string $title, ?string $location, ?CarbonInterface $checkin, ?CarbonInterface $checkout): ?string
    {
        if (! $this->isConfigured() || $location === null || trim($location) === '') {
            return null;
        }

        // aid is recommended first; http_build_query preserves insertion order.
        $params = ['aid' => config('affiliates.stay22.id'), 'address' => trim($location)];

        if (filled($title)) {
            // Overrides the widget title with our location name.
            $params['venue'] = $title;
        }

        if ($checkin) {
            $params['checkin'] = $checkin->toDateString();
        }
        if ($checkout) {
            $params['checkout'] = $checkout->toDateString();
        }

        $params['maincolor'] = ltrim((string) config('affiliates.stay22.maincolor'), '#');
        $params['campaign'] = (string) config('affiliates.stay22.campaign');

        return rtrim((string) config('affiliates.stay22.embed_base'), '?').'?'.http_build_query($params);
    }

    // ── Aviasales (flights) ──────────────────────────────────────────────────

    /**
     * A flight search to the nearest airport for the wedding weekend (depart the
     * day before, return the day after). Travellers fill in their own origin.
     */
    public function aviasalesSearchUrl(?string $airport, ?CarbonInterface $eventDate = null): ?string
    {
        return $this->buildAviasalesUrl($airport, $eventDate?->copy()->subDay(), $eventDate?->copy()->addDay());
    }

    /** A flight search to a honeymoon destination across the chosen date range. */
    public function aviasalesRangeUrl(?string $airport, ?CarbonInterface $depart = null, ?CarbonInterface $return = null): ?string
    {
        return $this->buildAviasalesUrl($airport, $depart, $return);
    }

    private function buildAviasalesUrl(?string $airport, ?CarbonInterface $depart, ?CarbonInterface $return): ?string
    {
        if (! $this->flightsConfigured()) {
            return null;
        }

        $iata = strtoupper(trim((string) $airport));

        if ($iata === '') {
            return null;
        }

        $params = [
            'marker' => config('affiliates.travelpayouts.marker'),
            'destination_iata' => $iata,
            'adults' => 1,
            'trip_class' => 0,
            'locale' => (string) config('affiliates.travelpayouts.locale', 'en'),
        ];

        if ($depart) {
            $params['depart_date'] = $depart->toDateString();
        }
        if ($return) {
            $params['return_date'] = $return->toDateString();
        }

        return rtrim((string) config('affiliates.travelpayouts.aviasales_base'), '?').'?'.http_build_query($params);
    }
}

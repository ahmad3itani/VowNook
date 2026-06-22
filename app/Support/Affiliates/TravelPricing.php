<?php

namespace App\Support\Affiliates;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Live travel prices for the honeymoon concierge, via the Travelpayouts data
 * APIs (Aviasales cheap-flight prices + Hotellook hotel prices). Cached and
 * fully graceful — when there's no API token, the partner is slow, or the route
 * has no data, callers get a "not found" status and fall back to AI estimates.
 */
class TravelPricing
{
    public function configured(): bool
    {
        return filled(config('affiliates.travelpayouts.api_token'));
    }

    /**
     * Cheapest round-trip flight for a route + month, in CAD cents.
     *
     * @return array{found: bool, price_cents: int|null, currency: string}
     */
    public function flightPrice(?string $origin, ?string $destination, ?string $departDate, ?string $returnDate): array
    {
        $none = ['found' => false, 'price_cents' => null, 'currency' => 'CAD'];

        if (! $this->configured()) {
            return $none;
        }

        $origin = strtoupper(trim((string) $origin));
        $destination = strtoupper(trim((string) $destination));

        if ($origin === '' || $destination === '') {
            return $none;
        }

        $departMonth = $this->month($departDate);
        $returnMonth = $this->month($returnDate);

        return Cache::remember("tp.flight.{$origin}.{$destination}.{$departMonth}.{$returnMonth}", now()->addHours(6),
            function () use ($origin, $destination, $departMonth, $returnMonth, $none) {
                $params = array_filter([
                    'origin' => $origin,
                    'destination' => $destination,
                    'depart_date' => $departMonth,
                    'return_date' => $returnMonth,
                    'currency' => 'cad',
                    'token' => config('affiliates.travelpayouts.api_token'),
                ]);

                try {
                    $response = Http::acceptJson()->timeout(12)
                        ->get(rtrim((string) config('affiliates.travelpayouts.api_base'), '/').'/v1/prices/cheap', $params);
                } catch (Throwable $e) {
                    Log::warning('Flight price request failed', ['message' => $e->getMessage()]);

                    return $none;
                }

                if ($response->failed()) {
                    return $none;
                }

                $min = $this->lowestPrice((array) $response->json('data'));

                return $min !== null
                    ? ['found' => true, 'price_cents' => (int) round($min * 100), 'currency' => 'CAD']
                    : $none;
            });
    }

    /**
     * Cheapest hotel for the stay at a destination, in CAD cents.
     *
     * @return array{found: bool, price_cents: int|null, currency: string}
     */
    public function hotelPrice(?string $location, ?string $checkIn, ?string $checkOut): array
    {
        $none = ['found' => false, 'price_cents' => null, 'currency' => 'CAD'];

        if (! $this->configured()) {
            return $none;
        }

        $location = trim((string) $location);

        if ($location === '' || ! filled($checkIn) || ! filled($checkOut)) {
            return $none;
        }

        return Cache::remember('tp.hotel.'.md5("{$location}|{$checkIn}|{$checkOut}"), now()->addHours(6),
            function () use ($location, $checkIn, $checkOut, $none) {
                try {
                    $response = Http::acceptJson()->timeout(12)->get(
                        rtrim((string) config('affiliates.travelpayouts.hotellook_base'), '/').'/api/v2/cache.json',
                        [
                            'location' => $location,
                            'checkIn' => $checkIn,
                            'checkOut' => $checkOut,
                            'currency' => 'cad',
                            'limit' => 10,
                            'token' => config('affiliates.travelpayouts.api_token'),
                        ],
                    );
                } catch (Throwable $e) {
                    Log::warning('Hotel price request failed', ['message' => $e->getMessage()]);

                    return $none;
                }

                if ($response->failed()) {
                    return $none;
                }

                $min = null;
                foreach ((array) $response->json() as $hotel) {
                    foreach (['priceFrom', 'priceAvg', 'price'] as $field) {
                        $value = is_array($hotel) ? ($hotel[$field] ?? null) : null;
                        if (is_numeric($value) && (float) $value > 0) {
                            $min = $min === null ? (float) $value : min($min, (float) $value);
                            break;
                        }
                    }
                }

                return $min !== null
                    ? ['found' => true, 'price_cents' => (int) round($min * 100), 'currency' => 'CAD']
                    : $none;
            });
    }

    /** Lowest value under any "price" key in the (nested) cheap-prices payload. */
    private function lowestPrice(array $data): ?float
    {
        $min = null;

        array_walk_recursive($data, function ($value, $key) use (&$min) {
            if ($key === 'price' && is_numeric($value) && (float) $value > 0) {
                $min = $min === null ? (float) $value : min($min, (float) $value);
            }
        });

        return $min;
    }

    private function month(?string $date): ?string
    {
        if (! filled($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m');
        } catch (Throwable) {
            return null;
        }
    }
}

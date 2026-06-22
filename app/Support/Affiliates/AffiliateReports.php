<?php

namespace App\Support\Affiliates;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pulls live earnings from the travel affiliate partners for the admin
 * dashboard. Currently: the Travelpayouts (flights) account balance via the
 * Finance API. Cached and wrapped so a slow or failing partner never breaks the
 * page — callers always get a structured status to render.
 *
 * Stay22's Hub Data Reporting API isn't publicly documented, so hotel earnings
 * are surfaced via a deep link to the Hub until that endpoint is available.
 */
class AffiliateReports
{
    /**
     * @return array{connected: bool, amount: float|null, currency: string|null, error: string|null}
     */
    public function travelpayoutsBalance(): array
    {
        $token = config('affiliates.travelpayouts.api_token');

        if (! filled($token)) {
            return ['connected' => false, 'amount' => null, 'currency' => null, 'error' => null];
        }

        return Cache::remember('affiliates.travelpayouts.balance', now()->addMinutes(10), function () use ($token) {
            try {
                $response = Http::withHeaders(['X-Access-Token' => $token])
                    ->acceptJson()
                    ->timeout(15)
                    ->get(rtrim((string) config('affiliates.travelpayouts.api_base'), '/').'/finance/v2/get_user_balance');
            } catch (Throwable $e) {
                Log::warning('Travelpayouts balance request failed', ['message' => $e->getMessage()]);

                return ['connected' => true, 'amount' => null, 'currency' => null, 'error' => 'Could not reach Travelpayouts.'];
            }

            if ($response->failed()) {
                return [
                    'connected' => true,
                    'amount' => null,
                    'currency' => null,
                    'error' => 'Travelpayouts returned an error ('.$response->status().').',
                ];
            }

            $body = (array) $response->json();

            return [
                'connected' => true,
                'amount' => isset($body['amount']) ? (float) $body['amount'] : null,
                'currency' => isset($body['currency']) ? strtoupper((string) $body['currency']) : null,
                'error' => null,
            ];
        });
    }
}

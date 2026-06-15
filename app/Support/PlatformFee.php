<?php

namespace App\Support;

/**
 * Computes the platform commission for a booking using the tiered
 * structure in config/marketplace.php.
 */
class PlatformFee
{
    public static function for(int $totalCents): int
    {
        $config = config('marketplace.commission');

        $threshold = (int) $config['threshold_cents'];
        $cap = (int) $config['cap_cents'];

        $base = min($totalCents, $threshold);
        $above = max(0, $totalCents - $threshold);

        $fee = (int) round($base * (float) $config['rate'] + $above * (float) $config['rate_above']);

        return min($fee, $cap);
    }
}

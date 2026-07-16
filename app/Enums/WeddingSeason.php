<?php

namespace App\Enums;

/**
 * The season the couple expects (or dreams) to marry in — captured on the
 * onboarding-enrichment screen, independent of any exact event_date.
 */
enum WeddingSeason: string
{
    case Spring = 'spring';
    case Summer = 'summer';
    case Fall = 'fall';
    case Winter = 'winter';

    public function label(): string
    {
        return match ($this) {
            self::Spring => 'Spring',
            self::Summer => 'Summer',
            self::Fall => 'Fall',
            self::Winter => 'Winter',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

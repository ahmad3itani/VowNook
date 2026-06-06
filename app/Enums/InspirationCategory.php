<?php

namespace App\Enums;

/**
 * Themes for an inspiration / mood-board item.
 */
enum InspirationCategory: string
{
    case Venue = 'venue';
    case Decor = 'decor';
    case Flowers = 'flowers';
    case Attire = 'attire';
    case Cake = 'cake';
    case Stationery = 'stationery';
    case Beauty = 'beauty';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Venue => 'Venue',
            self::Decor => 'Decor',
            self::Flowers => 'Flowers',
            self::Attire => 'Attire',
            self::Cake => 'Cake',
            self::Stationery => 'Stationery',
            self::Beauty => 'Beauty',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

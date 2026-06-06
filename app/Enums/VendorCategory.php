<?php

namespace App\Enums;

/**
 * The type of service a vendor provides. "Other" is a catch-all so the list
 * never blocks adding an unusual vendor.
 */
enum VendorCategory: string
{
    case Venue = 'venue';
    case Catering = 'catering';
    case Photography = 'photography';
    case Videography = 'videography';
    case Florist = 'florist';
    case Music = 'music';
    case Bakery = 'bakery';
    case Officiant = 'officiant';
    case Transportation = 'transportation';
    case Attire = 'attire';
    case Beauty = 'beauty';
    case Planner = 'planner';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Venue => 'Venue',
            self::Catering => 'Catering',
            self::Photography => 'Photography',
            self::Videography => 'Videography',
            self::Florist => 'Florist',
            self::Music => 'Music & DJ',
            self::Bakery => 'Bakery',
            self::Officiant => 'Officiant',
            self::Transportation => 'Transportation',
            self::Attire => 'Attire',
            self::Beauty => 'Hair & Beauty',
            self::Planner => 'Planner',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

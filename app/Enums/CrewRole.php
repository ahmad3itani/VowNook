<?php

namespace App\Enums;

/**
 * Roles within the wedding party and day-of crew.
 */
enum CrewRole: string
{
    case MaidOfHonour = 'maid_of_honour';
    case BestMan = 'best_man';
    case Bridesmaid = 'bridesmaid';
    case Groomsman = 'groomsman';
    case Officiant = 'officiant';
    case Usher = 'usher';
    case FlowerGirl = 'flower_girl';
    case RingBearer = 'ring_bearer';
    case Parent = 'parent';
    case Host = 'host';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MaidOfHonour => 'Maid of Honour',
            self::BestMan => 'Best Man',
            self::Bridesmaid => 'Bridesmaid',
            self::Groomsman => 'Groomsman',
            self::Officiant => 'Officiant',
            self::Usher => 'Usher',
            self::FlowerGirl => 'Flower Girl',
            self::RingBearer => 'Ring Bearer',
            self::Parent => 'Parent',
            self::Host => 'MC / Host',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}

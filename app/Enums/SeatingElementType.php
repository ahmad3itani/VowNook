<?php

namespace App\Enums;

/**
 * Non-table objects that can be placed on the reception floor plan.
 */
enum SeatingElementType: string
{
    case DanceFloor = 'dance_floor';
    case Bar = 'bar';
    case DjBooth = 'dj_booth';
    case Stage = 'stage';
    case GiftTable = 'gift_table';
    case CakeTable = 'cake_table';
    case PhotoBooth = 'photo_booth';
    case Buffet = 'buffet';
    case Entrance = 'entrance';
    case Restroom = 'restroom';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DanceFloor => 'Dance floor',
            self::Bar => 'Bar',
            self::DjBooth => 'DJ booth',
            self::Stage => 'Stage',
            self::GiftTable => 'Gift table',
            self::CakeTable => 'Cake table',
            self::PhotoBooth => 'Photo booth',
            self::Buffet => 'Buffet',
            self::Entrance => 'Entrance',
            self::Restroom => 'Restroom',
            self::Other => 'Other',
        };
    }

    /** Default size as a percentage of the room [width, height]. */
    public function defaultSize(): array
    {
        return match ($this) {
            self::DanceFloor => [28, 24],
            self::Stage => [24, 12],
            self::Bar => [22, 8],
            self::Buffet => [22, 8],
            self::DjBooth => [12, 10],
            self::PhotoBooth => [12, 12],
            self::GiftTable, self::CakeTable => [12, 7],
            self::Entrance, self::Restroom => [12, 8],
            self::Other => [14, 12],
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}

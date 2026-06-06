<?php

namespace App\Enums;

/**
 * The kind of moment a timeline entry represents on the wedding day(s).
 */
enum EventType: string
{
    case Preparation = 'preparation';
    case Ceremony = 'ceremony';
    case Photos = 'photos';
    case Cocktails = 'cocktails';
    case Reception = 'reception';
    case Party = 'party';
    case Travel = 'travel';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Preparation => 'Preparation',
            self::Ceremony => 'Ceremony',
            self::Photos => 'Photos',
            self::Cocktails => 'Cocktails',
            self::Reception => 'Reception',
            self::Party => 'Party',
            self::Travel => 'Travel',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}

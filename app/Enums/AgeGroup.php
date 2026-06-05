<?php

namespace App\Enums;

/**
 * Age bracket of a guest. Used for catering counts and seating rules
 * (e.g. children at a family table).
 */
enum AgeGroup: string
{
    case Adult = 'adult';
    case Child = 'child';
    case Infant = 'infant';

    public function label(): string
    {
        return match ($this) {
            self::Adult => 'Adult',
            self::Child => 'Child',
            self::Infant => 'Infant',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

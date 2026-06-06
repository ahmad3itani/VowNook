<?php

namespace App\Enums;

/**
 * The physical shape of a reception table, used to render it on the chart.
 */
enum TableShape: string
{
    case Round = 'round';
    case Rectangle = 'rectangle';
    case Square = 'square';

    public function label(): string
    {
        return match ($this) {
            self::Round => 'Round',
            self::Rectangle => 'Rectangle',
            self::Square => 'Square',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

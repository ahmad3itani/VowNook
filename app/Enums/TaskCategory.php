<?php

namespace App\Enums;

/**
 * Broad buckets a planning task can belong to.
 */
enum TaskCategory: string
{
    case Planning = 'planning';
    case Ceremony = 'ceremony';
    case Reception = 'reception';
    case Attire = 'attire';
    case Stationery = 'stationery';
    case Logistics = 'logistics';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Ceremony => 'Ceremony',
            self::Reception => 'Reception',
            self::Attire => 'Attire',
            self::Stationery => 'Stationery',
            self::Logistics => 'Logistics',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

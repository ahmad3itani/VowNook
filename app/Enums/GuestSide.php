<?php

namespace App\Enums;

/**
 * Which side of the couple a guest belongs to. Labels are intentionally
 * neutral; the UI can substitute the partners' names when available.
 */
enum GuestSide: string
{
    case PartnerOne = 'partner_one';
    case PartnerTwo = 'partner_two';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::PartnerOne => 'Partner 1',
            self::PartnerTwo => 'Partner 2',
            self::Both => 'Both',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

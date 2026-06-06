<?php

namespace App\Enums;

/**
 * Where a vendor sits in the booking pipeline.
 */
enum VendorStatus: string
{
    case Researching = 'researching';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Booked = 'booked';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Researching => 'Researching',
            self::Contacted => 'Contacted',
            self::Quoted => 'Quoted',
            self::Booked => 'Booked',
            self::Declined => 'Declined',
        };
    }

    public function isBooked(): bool
    {
        return $this === self::Booked;
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

<?php

namespace App\Enums;

/**
 * Top-level account kind, chosen at registration. Orthogonal to is_admin.
 *
 * - Couple: plans a wedding (owns a wedding workspace) — the default.
 * - Vendor: a marketplace business that lists services and receives bookings;
 *   has a VendorProfile instead of a wedding.
 * - Planner: a professional managing many client weddings — gets the planner
 *   HQ portfolio dashboard and unlimited weddings.
 */
enum AccountType: string
{
    case Couple = 'couple';
    case Vendor = 'vendor';
    case Planner = 'planner';

    public function label(): string
    {
        return match ($this) {
            self::Couple => 'Couple',
            self::Vendor => 'Vendor',
            self::Planner => 'Planner',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}

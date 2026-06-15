<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Planner = 'planner';
    case Partner = 'partner';
    case Collaborator = 'collaborator';
    case Viewer = 'viewer';
    case Vendor = 'vendor';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Planner => 'Planner',
            self::Partner => 'Partner',
            self::Collaborator => 'Collaborator',
            self::Viewer => 'Viewer',
            self::Vendor => 'Vendor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full control of the wedding, billing, and collaborators.',
            self::Planner => 'Manages every working section on the couple\'s behalf.',
            self::Partner => 'The other half of the couple — broad access, no billing.',
            self::Collaborator => 'A trusted helper with access to selected sections.',
            self::Viewer => 'Read-only access for family and friends.',
            self::Vendor => 'Service vendor — sees their own booking, timeline, and payment status.',
        };
    }

    /** Roles an owner/planner may assign to collaborators (excludes Owner). */
    public static function assignable(): array
    {
        return [self::Planner, self::Partner, self::Collaborator, self::Viewer, self::Vendor];
    }

    /** Roles that see the couple's full planning dashboard. */
    public static function coupleSide(): array
    {
        return [self::Owner, self::Partner, self::Planner, self::Collaborator, self::Viewer];
    }

    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}

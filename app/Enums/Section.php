<?php

namespace App\Enums;

/**
 * The 13 access-controlled sections of a wedding workspace.
 * The permission matrix in config/permissions.php is keyed by these values.
 */
enum Section: string
{
    case Overview = 'overview';
    case Budget = 'budget';
    case Guests = 'guests';
    case Seating = 'seating';
    case Vendors = 'vendors';
    case Timeline = 'timeline';
    case Checklist = 'checklist';
    case Inspiration = 'inspiration';
    case Gallery = 'gallery';
    case Website = 'website';
    case Crew = 'crew';
    case Collaborators = 'collaborators';
    case Settings = 'settings';

    public function label(): string
    {
        return match ($this) {
            self::Overview => 'Overview',
            self::Budget => 'Budget',
            self::Guests => 'Guests',
            self::Seating => 'Seating',
            self::Vendors => 'Vendors',
            self::Timeline => 'Timeline',
            self::Checklist => 'Checklist',
            self::Inspiration => 'Inspiration',
            self::Gallery => 'Gallery',
            self::Website => 'Wedding Site',
            self::Crew => 'Crew',
            self::Collaborators => 'Collaborators',
            self::Settings => 'Settings',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

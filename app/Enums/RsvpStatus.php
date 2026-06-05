<?php

namespace App\Enums;

/**
 * The reply state of a guest's invitation. "Maybe" covers tentative
 * replies that should not yet count toward the confirmed head count.
 */
enum RsvpStatus: string
{
    case Pending = 'pending';
    case Attending = 'attending';
    case Declined = 'declined';
    case Maybe = 'maybe';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Attending => 'Attending',
            self::Declined => 'Declined',
            self::Maybe => 'Maybe',
        };
    }

    /** Whether this reply counts toward the confirmed head count. */
    public function isConfirmed(): bool
    {
        return $this === self::Attending;
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

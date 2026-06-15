<?php

namespace App\Enums;

/**
 * Moderation lifecycle of a marketplace vendor profile. Only Published profiles
 * are exposed on public routes and discoverable in the marketplace.
 */
enum VendorProfileStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending review',
            self::Published => 'Published',
            self::Suspended => 'Suspended',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

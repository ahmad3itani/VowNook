<?php

namespace App\Enums;

/**
 * The type of service a vendor provides. "Other" is a catch-all so the list
 * never blocks adding an unusual vendor.
 */
enum VendorCategory: string
{
    case Venue = 'venue';
    case Catering = 'catering';
    case Photography = 'photography';
    case Videography = 'videography';
    case Florist = 'florist';
    case Music = 'music';
    case Bakery = 'bakery';
    case Officiant = 'officiant';
    case Transportation = 'transportation';
    case Attire = 'attire';
    case Beauty = 'beauty';
    case Planner = 'planner';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Venue => 'Venue',
            self::Catering => 'Catering',
            self::Photography => 'Photography',
            self::Videography => 'Videography',
            self::Florist => 'Florist',
            self::Music => 'Music & DJ',
            self::Bakery => 'Bakery',
            self::Officiant => 'Officiant',
            self::Transportation => 'Transportation',
            self::Attire => 'Attire',
            self::Beauty => 'Hair & Beauty',
            self::Planner => 'Planner',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /** Keyword URL slug for programmatic SEO pages, e.g. "wedding-photographers". */
    public function seoSlug(): string
    {
        return match ($this) {
            self::Venue => 'wedding-venues',
            self::Catering => 'wedding-caterers',
            self::Photography => 'wedding-photographers',
            self::Videography => 'wedding-videographers',
            self::Florist => 'wedding-florists',
            self::Music => 'wedding-djs-musicians',
            self::Bakery => 'wedding-cakes',
            self::Officiant => 'wedding-officiants',
            self::Transportation => 'wedding-transportation',
            self::Attire => 'wedding-attire',
            self::Beauty => 'wedding-hair-makeup',
            self::Planner => 'wedding-planners',
            self::Other => 'wedding-vendors',
        };
    }

    /** Plural human noun for SEO titles, e.g. "Wedding Photographers". */
    public function seoNoun(): string
    {
        return match ($this) {
            self::Venue => 'Wedding Venues',
            self::Catering => 'Wedding Caterers',
            self::Photography => 'Wedding Photographers',
            self::Videography => 'Wedding Videographers',
            self::Florist => 'Wedding Florists',
            self::Music => 'Wedding DJs & Musicians',
            self::Bakery => 'Wedding Cake Bakers',
            self::Officiant => 'Wedding Officiants',
            self::Transportation => 'Wedding Transportation',
            self::Attire => 'Wedding Attire Boutiques',
            self::Beauty => 'Wedding Hair & Makeup Artists',
            self::Planner => 'Wedding Planners',
            self::Other => 'Wedding Vendors',
        };
    }

    public static function fromSeoSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->seoSlug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    /** Categories that get programmatic SEO pages (everything except the catch-all). */
    public static function seoCases(): array
    {
        return array_filter(self::cases(), fn (self $c) => $c !== self::Other);
    }

    /** Pipe-joined slug pattern for route constraints. */
    public static function seoSlugPattern(): string
    {
        return implode('|', array_map(fn (self $c) => $c->seoSlug(), self::seoCases()));
    }
}

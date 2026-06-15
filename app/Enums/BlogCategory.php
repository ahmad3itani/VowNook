<?php

namespace App\Enums;

/**
 * Editorial sections of the blog. Each maps to a clean SEO slug used in
 * /blog/category/{slug} URLs.
 */
enum BlogCategory: string
{
    case PlanningTips = 'planning_tips';
    case Budgeting = 'budgeting';
    case Venues = 'venues';
    case VendorGuides = 'vendor_guides';
    case RealWeddings = 'real_weddings';

    public function label(): string
    {
        return match ($this) {
            self::PlanningTips => 'Planning Tips',
            self::Budgeting => 'Budgeting',
            self::Venues => 'Venues',
            self::VendorGuides => 'Vendor Guides',
            self::RealWeddings => 'Real Weddings',
        };
    }

    public function seoSlug(): string
    {
        return match ($this) {
            self::PlanningTips => 'planning-tips',
            self::Budgeting => 'budgeting',
            self::Venues => 'venues',
            self::VendorGuides => 'vendor-guides',
            self::RealWeddings => 'real-weddings',
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

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

<?php

namespace App\Enums;

/**
 * The couple's self-chosen aesthetic style for their wedding — pure visual/
 * design taste, captured on the onboarding-enrichment screen. Deliberately
 * limited to aesthetic categories only; never extend this with anything that
 * could read as cultural, religious, or ethnic (see CoupleSegments guardrail).
 */
enum WeddingVibe: string
{
    case ClassicElegant = 'classic-elegant';
    case ModernMinimal = 'modern-minimal';
    case RusticOutdoorsy = 'rustic-outdoorsy';
    case BohoRomantic = 'boho-romantic';
    case GlamLuxe = 'glam-luxe';
    case GardenBotanical = 'garden-botanical';

    public function label(): string
    {
        return match ($this) {
            self::ClassicElegant => 'Classic & elegant',
            self::ModernMinimal => 'Modern & minimal',
            self::RusticOutdoorsy => 'Rustic & outdoorsy',
            self::BohoRomantic => 'Boho & romantic',
            self::GlamLuxe => 'Glam & luxe',
            self::GardenBotanical => 'Garden & botanical',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

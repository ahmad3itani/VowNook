<?php

namespace App\Enums;

enum ReportReason: string
{
    case Inappropriate = 'inappropriate';
    case FakeOrScam = 'fake_or_scam';
    case NotAsDescribed = 'not_as_described';
    case Spam = 'spam';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Inappropriate => 'Inappropriate or offensive content',
            self::FakeOrScam => 'Fake listing or possible scam',
            self::NotAsDescribed => 'Misleading — not as described',
            self::Spam => 'Spam',
            self::Other => 'Something else',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

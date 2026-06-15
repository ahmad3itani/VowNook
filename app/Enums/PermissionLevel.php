<?php

namespace App\Enums;

enum PermissionLevel: string
{
    case None = 'none';
    case Read = 'read';
    case Write = 'write';

    /** Numeric rank for "at least" comparisons. */
    public function rank(): int
    {
        return match ($this) {
            self::None => 0,
            self::Read => 1,
            self::Write => 2,
        };
    }

    /** Does this level satisfy the required level? */
    public function allows(self $required): bool
    {
        return $this->rank() >= $required->rank();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $l) => $l->value, self::cases());
    }
}

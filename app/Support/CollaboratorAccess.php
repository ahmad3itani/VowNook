<?php

namespace App\Support;

use App\Enums\PermissionLevel;
use App\Enums\Role;
use App\Enums\Section;

/**
 * Translates between a collaborator's chosen role + per-section override and the
 * effective access map shown in the UI. The override stored on the membership
 * (or invitation) is kept *sparse* — only sections that differ from the role's
 * defaults — so changing the role later still moves the unset sections.
 */
class CollaboratorAccess
{
    /** The default level a role grants on a section (None when unlisted). */
    public static function roleDefault(Role $role, Section $section): PermissionLevel
    {
        $value = config("permissions.matrix.{$role->value}.{$section->value}", PermissionLevel::None->value);

        return PermissionLevel::from($value);
    }

    /**
     * Full section => level map for a role with its override applied.
     *
     * @param  array<string,string>|null  $override
     * @return array<string,string>
     */
    public static function effective(Role $role, ?array $override): array
    {
        $map = [];

        foreach (Section::cases() as $section) {
            $level = self::roleDefault($role, $section);

            if ($override !== null && isset($override[$section->value])) {
                $level = PermissionLevel::tryFrom($override[$section->value]) ?? $level;
            }

            $map[$section->value] = $level->value;
        }

        return $map;
    }

    /**
     * Reduce a desired full map to the sparse override (only the sections that
     * differ from the role's defaults). Returns null when nothing differs.
     *
     * @param  array<string,string>  $desired
     * @return array<string,string>|null
     */
    public static function diff(Role $role, array $desired): ?array
    {
        $override = [];

        foreach (Section::cases() as $section) {
            $level = PermissionLevel::tryFrom($desired[$section->value] ?? '');

            if ($level === null) {
                continue;
            }

            if ($level !== self::roleDefault($role, $section)) {
                $override[$section->value] = $level->value;
            }
        }

        return $override === [] ? null : $override;
    }
}

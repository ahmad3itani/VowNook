<?php

namespace App\Services;

use App\Enums\PermissionLevel;
use App\Enums\Role;
use App\Enums\Section;
use App\Models\User;
use App\Models\Wedding;

/**
 * Resolves what a user may do within a wedding. Server-side source of truth —
 * the client receives a resolved map purely for showing/hiding UI, never for
 * authorization.
 */
class PermissionService
{
    /**
     * The resolved permission level for a user on a section of a wedding.
     */
    public function levelFor(User $user, Wedding $wedding, Section $section): PermissionLevel
    {
        if ($user->is_admin) {
            return PermissionLevel::Write;
        }

        $role = $wedding->roleFor($user);

        if ($role === null) {
            return PermissionLevel::None;
        }

        // Per-user override stored on the membership pivot takes precedence.
        $override = $this->overrideFor($user, $wedding);

        if (isset($override[$section->value])) {
            return PermissionLevel::from($override[$section->value]);
        }

        return $this->defaultLevel($role, $section);
    }

    public function defaultLevel(Role $role, Section $section): PermissionLevel
    {
        $value = config("permissions.matrix.{$role->value}.{$section->value}", PermissionLevel::None->value);

        return PermissionLevel::from($value);
    }

    public function allows(User $user, Wedding $wedding, Section $section, PermissionLevel $required): bool
    {
        return $this->levelFor($user, $wedding, $section)->allows($required);
    }

    public function canRead(User $user, Wedding $wedding, Section $section): bool
    {
        return $this->allows($user, $wedding, $section, PermissionLevel::Read);
    }

    public function canWrite(User $user, Wedding $wedding, Section $section): bool
    {
        return $this->allows($user, $wedding, $section, PermissionLevel::Write);
    }

    /**
     * Full resolved map (section => level string) for sharing with the client.
     *
     * @return array<string, string>
     */
    public function mapFor(User $user, Wedding $wedding): array
    {
        $map = [];

        foreach (Section::cases() as $section) {
            $map[$section->value] = $this->levelFor($user, $wedding, $section)->value;
        }

        return $map;
    }

    /** @return array<string, string> */
    protected function overrideFor(User $user, Wedding $wedding): array
    {
        $member = $wedding->members->firstWhere('id', $user->id)
            ?? $wedding->members()->find($user->id);

        $permissions = $member?->pivot?->permissions;

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        return is_array($permissions) ? $permissions : [];
    }
}

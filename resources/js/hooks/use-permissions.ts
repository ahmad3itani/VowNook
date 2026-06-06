import { usePage } from '@inertiajs/react';
import type { PermissionLevel, Section } from '@/types/wedding';

const RANK: Record<PermissionLevel, number> = { none: 0, read: 1, write: 2 };

/**
 * Read-side permission helpers for the active wedding. The server is always the
 * source of truth; these are only for showing/hiding/disabling UI.
 */
export function usePermissions() {
    const { wedding, auth } = usePage().props;
    const map = wedding?.permissions ?? {};
    const isAdmin = Boolean(auth?.isAdmin);

    const levelOf = (section: Section): PermissionLevel =>
        isAdmin
            ? 'write'
            : ((map as Record<string, PermissionLevel>)[section] ?? 'none');

    const allows = (section: Section, required: PermissionLevel): boolean =>
        RANK[levelOf(section)] >= RANK[required];

    return {
        isAdmin,
        levelOf,
        canRead: (section: Section) => allows(section, 'read'),
        canWrite: (section: Section) => allows(section, 'write'),
    };
}

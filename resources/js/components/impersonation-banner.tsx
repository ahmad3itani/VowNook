import { router, usePage } from '@inertiajs/react';
import { Eye, X } from 'lucide-react';

type ImpersonationProp = {
    active: boolean;
    user: { name: string; account_type: string };
} | null;

const ROLE_LABEL: Record<string, string> = {
    couple: 'couple',
    vendor: 'vendor',
    planner: 'planner',
};

/**
 * Persistent bar shown when a platform admin is signed in as another user
 * ("view as"). Appears in every role's chrome so the admin always knows they're
 * impersonating and can switch back in one click.
 */
export function ImpersonationBanner() {
    const impersonation = (usePage().props as { impersonation?: ImpersonationProp })
        .impersonation;

    if (!impersonation?.active) return null;

    const role = ROLE_LABEL[impersonation.user.account_type] ?? 'user';

    return (
        <div className="flex items-center justify-between gap-3 border-b border-violet-300 bg-violet-100 px-4 py-2 text-sm text-violet-950 dark:border-violet-700/60 dark:bg-violet-950/50 dark:text-violet-100">
            <span className="flex items-center gap-2">
                <Eye className="size-4 shrink-0" />
                <span>
                    Viewing as{' '}
                    <strong>{impersonation.user.name}</strong>{' '}
                    <span className="opacity-70">({role})</span> — actions you take
                    affect this account.
                </span>
            </span>
            <button
                type="button"
                onClick={() => router.post('/impersonate/stop')}
                className="flex shrink-0 items-center gap-1 rounded px-2 py-1 font-medium hover:bg-violet-200 dark:hover:bg-violet-900/50"
            >
                <X className="size-3.5" />
                Back to admin
            </button>
        </div>
    );
}

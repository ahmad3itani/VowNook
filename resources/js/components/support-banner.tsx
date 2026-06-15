import { router, usePage } from '@inertiajs/react';
import { LifeBuoy, X } from 'lucide-react';

type SupportProp = { active: boolean; wedding: { name: string } } | null;

/**
 * Thin amber bar shown only to a platform admin who has entered a couple's
 * workspace in support mode. Couples never see it.
 */
export function SupportBanner() {
    const support = (usePage().props as { support?: SupportProp }).support;

    if (!support?.active) return null;

    return (
        <div className="flex items-center justify-between gap-3 border-b border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/40 dark:text-amber-200">
            <span className="flex items-center gap-2">
                <LifeBuoy className="size-4 shrink-0" />
                <span>
                    Support mode — you’re viewing <strong>{support.wedding.name}</strong> with full access.
                </span>
            </span>
            <button
                type="button"
                onClick={() => router.post('/admin/support/exit')}
                className="flex items-center gap-1 rounded px-2 py-1 font-medium hover:bg-amber-100 dark:hover:bg-amber-900/40"
            >
                <X className="size-3.5" />
                Exit
            </button>
        </div>
    );
}

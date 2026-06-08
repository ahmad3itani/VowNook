import { usePage } from '@inertiajs/react';

/**
 * Returns a `t(key, fallback)` helper backed by the admin-managed translation
 * strings for the active locale. Falls back to the provided default copy when a
 * key has no override.
 */
export function useTranslations() {
    const { translations, locale } = usePage().props;

    function t(key: string, fallback: string): string {
        return translations?.[key] ?? fallback;
    }

    return { t, locale };
}

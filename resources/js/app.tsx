import { createInertiaApp, router } from '@inertiajs/react';
import { ErrorBoundary, reloadForStaleAssets } from '@/components/error-boundary';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme, syncForcedLight } from '@/hooks/use-appearance';
import AdminLayout from '@/layouts/admin-layout';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import PlannerLayout from '@/layouts/planner-layout';
import VendorLayout from '@/layouts/vendor-layout';

declare global {
    interface Window {
        gtag?: (...args: unknown[]) => void;
        fbq?: (...args: unknown[]) => void;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'VowNook';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('public/'):
                return null;
            case name.startsWith('invitations/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('admin/'):
                return AdminLayout;
            case name.startsWith('vendor/'):
                return VendorLayout;
            case name.startsWith('planner/'):
                return PlannerLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <ErrorBoundary>
                <TooltipProvider delayDuration={0}>
                    {app}
                    <Toaster />
                </TooltipProvider>
            </ErrorBoundary>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

type Conversion = { ga: string; meta: string; params?: Record<string, unknown> };

// Everything below is client-only side-effect setup. app.tsx is ALSO the source
// of the SSR bundle (@inertiajs/vite swaps createInertiaApp for createServer),
// which runs in Node — so this whole block must be guarded, or `window` /
// `document` access here crashes the SSR server on boot.
if (typeof window !== 'undefined') {
    // A page chunk that 404s after a deploy (the browser has stale HTML/assets)
    // would otherwise leave a blank page mid-navigation — recover with one reload.
    window.addEventListener('vite:preloadError', (event) => {
        event.preventDefault();
        reloadForStaleAssets();
    });

    // Conversion tracking: the server flashes a one-shot `conversion` prop (see
    // App\Support\Conversions) at signup / inquiry / booking / purchase. Fire it
    // once into GA4 + the Meta Pixel. Both are consent-gated (undefined until the
    // visitor accepts cookies), so the optional-chaining calls simply no-op.
    const firedConversions = new Set<string>();

    const fireConversion = (conversion?: Conversion | null) => {
        if (!conversion) return;
        const signature = JSON.stringify(conversion);
        if (firedConversions.has(signature)) return;
        firedConversions.add(signature);

        const { ga, meta, params = {} } = conversion;
        try { window.gtag?.('event', ga, params); } catch { /* analytics not loaded */ }
        try { window.fbq?.('track', meta, params); } catch { /* pixel not loaded */ }
    };

    // Full-page loads (e.g. returning from Stripe Checkout) carry the conversion
    // in the initial Inertia payload embedded on the root element.
    try {
        const initial = document.getElementById('app')?.dataset.page;
        if (initial) {
            fireConversion((JSON.parse(initial).props as { conversion?: Conversion | null }).conversion);
        }
    } catch { /* no initial conversion */ }

    // Client-side visits (signup, inquiry, booking accept redirect here).
    router.on('navigate', (event) => {
        fireConversion((event.detail.page.props as { conversion?: Conversion | null }).conversion);
    });

    // This will set light / dark mode on load...
    initializeTheme();

    // Dark mode is a vendor/planner perk; couples, guests and the auth pages are
    // always light. The blade root sets the lock on full loads — this keeps it
    // correct across client-side navigations (e.g. the login → dashboard redirect).
    router.on('navigate', (event) => {
        const user = (event.detail.page.props as {
            auth?: { user?: { account_type?: string } | null };
        }).auth?.user;

        syncForcedLight(!user || !['vendor', 'planner'].includes(user.account_type ?? ''));
    });
}

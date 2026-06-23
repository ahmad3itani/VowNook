import { createInertiaApp } from '@inertiajs/react';
import { ErrorBoundary, reloadForStaleAssets } from '@/components/error-boundary';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AdminLayout from '@/layouts/admin-layout';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import PlannerLayout from '@/layouts/planner-layout';
import VendorLayout from '@/layouts/vendor-layout';

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

// A page chunk that 404s after a deploy (the browser has stale HTML/assets)
// would otherwise leave a blank page mid-navigation — recover with one reload.
window.addEventListener('vite:preloadError', (event) => {
    event.preventDefault();
    reloadForStaleAssets();
});

// This will set light / dark mode on load...
initializeTheme();

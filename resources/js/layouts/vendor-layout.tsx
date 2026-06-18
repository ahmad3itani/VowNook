import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { ImpersonationBanner } from '@/components/impersonation-banner';
import { VendorSidebar } from '@/components/vendor-sidebar';
import { VerifyEmailBanner } from '@/components/verify-email-banner';
import type { BreadcrumbItem } from '@/types';

export default function VendorLayout({
    children,
    breadcrumbs = [],
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    return (
        <AppShell variant="sidebar">
            <VendorSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <ImpersonationBanner />
                <VerifyEmailBanner />
                {children}
            </AppContent>
        </AppShell>
    );
}

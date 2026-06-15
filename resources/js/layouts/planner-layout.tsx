import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { PlannerSidebar } from '@/components/planner-sidebar';
import { VerifyEmailBanner } from '@/components/verify-email-banner';
import type { BreadcrumbItem } from '@/types';

export default function PlannerLayout({
    children,
    breadcrumbs = [],
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    return (
        <AppShell variant="sidebar">
            <PlannerSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <VerifyEmailBanner />
                {children}
            </AppContent>
        </AppShell>
    );
}

import { Link } from '@inertiajs/react';
import {
    CalendarRange,
    LayoutGrid,
    MessageSquare,
    PackageOpen,
    Store,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

// Vendor business navigation. Items beyond Dashboard/Profile are wired in
// later phases; they are shown so the vendor sees the roadmap of their portal.
const vendorNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/vendor', icon: LayoutGrid },
    { title: 'Business profile', href: '/vendor/profile', icon: Store },
    { title: 'Services', href: '/vendor/services', icon: PackageOpen },
    { title: 'Availability', href: '/vendor/availability', icon: CalendarRange },
    { title: 'Inquiries', href: '/vendor/inquiries', icon: MessageSquare },
    { title: 'Earnings', href: '/vendor/earnings', icon: Wallet },
];

export function VendorSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/vendor" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={vendorNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

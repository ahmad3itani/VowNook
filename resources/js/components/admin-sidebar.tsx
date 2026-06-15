import { Link } from '@inertiajs/react';
import {
    BadgeCheck,
    Flag,
    Heart,
    LayoutGrid,
    Languages,
    Newspaper,
    Settings2,
    Store,
    Users,
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

// Platform admin console — oversees everything. Opening a wedding's workspace
// for support drops into the regular couple sidebar (with a support banner).
const adminNavItems: NavItem[] = [
    { title: 'Console', href: '/admin/dashboard', icon: LayoutGrid },
    { title: 'Weddings', href: '/admin/weddings', icon: Heart, matchPrefix: true },
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Vendors', href: '/admin/vendors', icon: BadgeCheck },
    { title: 'Marketplace', href: '/admin/marketplace', icon: Store },
    { title: 'Reports', href: '/admin/reports', icon: Flag },
    { title: 'Blog', href: '/admin/blog', icon: Newspaper, matchPrefix: true },
    { title: 'Settings', href: '/admin/settings', icon: Settings2 },
    { title: 'Localisation', href: '/admin/localisation', icon: Languages },
];

export function AdminSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

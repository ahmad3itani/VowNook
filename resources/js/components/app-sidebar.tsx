import { Link, usePage } from '@inertiajs/react';
import {
    Armchair,
    ArrowLeft,
    Briefcase,
    CalendarClock,
    CalendarDays,
    Gift,
    Globe,
    HeartHandshake,
    Images,
    LayoutGrid,
    ListChecks,
    QrCode,
    Languages,
    Settings2,
    Sparkles,
    UserCog,
    Users,
    Wallet,
    WandSparkles,
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
import { WeddingSwitcher } from '@/components/wedding-switcher';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const { canRead, canWrite } = usePermissions();

    const isPlanner = auth?.user?.account_type === 'planner';
    const canUseAssistant = canWrite('checklist') || canWrite('budget') || canWrite('timeline');

    const navItems: NavItem[] = [
        // Planners came from the HQ — give them the way back, and a Dashboard
        // link that opens the per-wedding overview instead of bouncing to HQ.
        ...(isPlanner
            ? [
                  {
                      title: 'All weddings',
                      href: '/planner',
                      icon: ArrowLeft,
                  } satisfies NavItem,
              ]
            : []),
        {
            title: 'Dashboard',
            href: isPlanner ? '/dashboard?workspace=1' : dashboard(),
            icon: LayoutGrid,
        },
        ...(canUseAssistant
            ? [
                  {
                      title: 'AI assistant',
                      href: '/assistant',
                      icon: WandSparkles,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('guests')
            ? [
                  {
                      title: 'Guests',
                      href: '/guests',
                      icon: Users,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('guests')
            ? [
                  {
                      title: 'Schedule',
                      href: '/events',
                      icon: CalendarDays,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('seating')
            ? [
                  {
                      title: 'Floor plan',
                      href: '/seating',
                      icon: Armchair,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('budget')
            ? [
                  {
                      title: 'Budget',
                      href: '/budget',
                      icon: Wallet,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('vendors')
            ? [
                  {
                      title: 'Marketplace',
                      href: '/vendors/marketplace',
                      icon: Briefcase,
                      matchPrefix: true,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('checklist')
            ? [
                  {
                      title: 'Checklist',
                      href: '/checklist',
                      icon: ListChecks,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('timeline')
            ? [
                  {
                      title: 'Timeline',
                      href: '/timeline',
                      icon: CalendarClock,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('inspiration')
            ? [
                  {
                      title: 'Inspiration',
                      href: '/inspiration',
                      icon: Sparkles,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('gallery')
            ? [
                  {
                      title: 'Gallery',
                      href: '/gallery',
                      icon: Images,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('website')
            ? [
                  {
                      title: 'Website',
                      href: '/website',
                      icon: Globe,
                  } satisfies NavItem,
                  {
                      title: 'Registry',
                      href: '/registry',
                      icon: Gift,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('crew')
            ? [
                  {
                      title: 'Crew',
                      href: '/crew',
                      icon: HeartHandshake,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('collaborators')
            ? [
                  {
                      title: 'Collaborators',
                      href: '/collaborators',
                      icon: UserCog,
                  } satisfies NavItem,
              ]
            : []),
        {
            title: 'Share',
            href: '/share',
            icon: QrCode,
        },
        ...(auth?.isAdmin
            ? [
                  {
                      title: 'Admin settings',
                      href: '/admin/settings',
                      icon: Settings2,
                  } satisfies NavItem,
                  {
                      title: 'Localisation',
                      href: '/admin/localisation',
                      icon: Languages,
                  } satisfies NavItem,
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <WeddingSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

import { Link, usePage } from '@inertiajs/react';
import {
    Armchair,
    BookOpen,
    Briefcase,
    CalendarClock,
    FolderGit2,
    Globe,
    HeartHandshake,
    Images,
    LayoutGrid,
    ListChecks,
    QrCode,
    Settings2,
    Sparkles,
    UserCog,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const { canRead } = usePermissions();

    const navItems: NavItem[] = [
        ...mainNavItems,
        ...(canRead('guests')
            ? [
                  {
                      title: 'Guests',
                      href: '/guests',
                      icon: Users,
                  } satisfies NavItem,
              ]
            : []),
        ...(canRead('seating')
            ? [
                  {
                      title: 'Seating',
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
                      title: 'Vendors',
                      href: '/vendors',
                      icon: Briefcase,
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

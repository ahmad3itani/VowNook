import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Heart } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';

export function WeddingSwitcher() {
    const { wedding } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    const active = wedding?.active ?? null;
    const list = wedding?.list ?? [];

    if (!active) {
        return null;
    }

    const switchTo = (slug: string) => {
        if (slug === active.slug) {
            return;
        }

        router.post(`/weddings/${slug}/switch`, {}, { preserveScroll: true });
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent"
                            data-test="wedding-switcher"
                        >
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                <Heart className="size-4" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">
                                    {active.name}
                                </span>
                                <span className="truncate text-xs text-muted-foreground">
                                    {active.event_date ?? 'Date to be set'}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'right'
                                  : 'bottom'
                        }
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Weddings
                        </DropdownMenuLabel>
                        {list.map((w) => (
                            <DropdownMenuItem
                                key={w.id}
                                onClick={() => switchTo(w.slug)}
                                className="gap-2"
                            >
                                <span className="truncate">{w.name}</span>
                                {w.slug === active.slug && (
                                    <Check className="ml-auto size-4" />
                                )}
                            </DropdownMenuItem>
                        ))}
                        {list.length === 0 && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem disabled>
                                    No other weddings
                                </DropdownMenuItem>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

import { Link, router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Item = {
    id: string;
    read: boolean;
    title: string;
    body: string | null;
    url: string | null;
    created_at: string | null;
};

type NotificationsProp = { unread: number; items: Item[] } | null;

export function NotificationBell() {
    const { notifications } = usePage().props as unknown as {
        notifications: NotificationsProp;
    };

    if (!notifications) return null;
    const { unread, items } = notifications;

    function open(item: Item) {
        router.post(`/notifications/${item.id}/read`, {}, {
            preserveScroll: true,
            preserveState: false,
            onFinish: () => {
                if (item.url) router.visit(item.url);
            },
        });
    }

    function markAll() {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative h-9 w-9 cursor-pointer" aria-label="Notifications">
                    <Bell className="!size-5 opacity-80" />
                    {unread > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                            {unread > 9 ? '9+' : unread}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between border-b px-3 py-2">
                    <span className="text-sm font-medium">Notifications</span>
                    {unread > 0 && (
                        <button onClick={markAll} className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">
                            <CheckCheck className="size-3" /> Mark all read
                        </button>
                    )}
                </div>

                <div className="max-h-80 overflow-y-auto">
                    {items.length === 0 ? (
                        <p className="px-3 py-8 text-center text-sm text-muted-foreground">You’re all caught up.</p>
                    ) : (
                        items.map((item) => (
                            <button
                                key={item.id}
                                onClick={() => open(item)}
                                className={`flex w-full flex-col items-start gap-0.5 border-b px-3 py-2.5 text-left transition-colors hover:bg-muted/60 ${
                                    item.read ? 'opacity-60' : ''
                                }`}
                            >
                                <span className="flex items-center gap-2 text-sm font-medium">
                                    {!item.read && <span className="size-1.5 rounded-full bg-red-500" />}
                                    {item.title}
                                </span>
                                {item.body && <span className="text-xs text-muted-foreground">{item.body}</span>}
                            </button>
                        ))
                    )}
                </div>

                <Link href="/notifications" className="block border-t px-3 py-2 text-center text-xs font-medium text-muted-foreground hover:text-foreground">
                    See all notifications
                </Link>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

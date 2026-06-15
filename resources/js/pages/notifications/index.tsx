import { Head, router } from '@inertiajs/react';
import { Bell, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Item = {
    id: string;
    read: boolean;
    title: string;
    body: string | null;
    url: string | null;
    created_at: string | null;
};

type PageProps = {
    notifications: { data: Item[]; next_page_url: string | null };
};

const when = new Intl.DateTimeFormat('en-CA', { dateStyle: 'medium', timeStyle: 'short' });

export default function NotificationsIndex({ notifications }: PageProps) {
    function open(item: Item) {
        router.post(`/notifications/${item.id}/read`, {}, {
            preserveScroll: true,
            onFinish: () => {
                if (item.url) router.visit(item.url);
            },
        });
    }

    function remove(id: string) {
        router.delete(`/notifications/${id}`, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Notifications" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading title="Notifications" description="Everything that's happened across your wedding." />
                    {notifications.data.some((n) => !n.read) && (
                        <Button
                            variant="outline"
                            onClick={() => router.post('/notifications/read-all', {}, { preserveScroll: true })}
                        >
                            Mark all read
                        </Button>
                    )}
                </div>

                {notifications.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-16 text-center text-muted-foreground">
                            <Bell className="size-8 opacity-40" />
                            <p>You’re all caught up.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-2">
                        {notifications.data.map((item) => (
                            <Card key={item.id} className={item.read ? 'opacity-60' : ''}>
                                <CardContent className="flex items-center justify-between gap-4 py-3">
                                    <button onClick={() => open(item)} className="flex flex-1 flex-col items-start text-left">
                                        <span className="flex items-center gap-2 text-sm font-medium">
                                            {!item.read && <span className="size-1.5 rounded-full bg-red-500" />}
                                            {item.title}
                                        </span>
                                        {item.body && <span className="text-sm text-muted-foreground">{item.body}</span>}
                                        {item.created_at && (
                                            <span className="mt-0.5 text-xs text-muted-foreground/70">
                                                {when.format(new Date(item.created_at))}
                                            </span>
                                        )}
                                    </button>
                                    <Button variant="ghost" size="icon" onClick={() => remove(item.id)} aria-label="Delete">
                                        <Trash2 className="size-4 text-muted-foreground" />
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {notifications.next_page_url && (
                    <div className="flex justify-center">
                        <Button variant="outline" onClick={() => router.visit(notifications.next_page_url!)}>
                            Load more
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

NotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifications', href: '/notifications' }],
};

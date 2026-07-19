import { Head, Link, router } from '@inertiajs/react';
import { LifeBuoy } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type Ticket = {
    id: number;
    subject: string;
    name: string;
    email: string;
    category: string;
    status: string;
    status_label: string;
    source: string;
    assignee: string | null;
    last_reply_at: string | null;
    created_at: string | null;
};

type PageProps = {
    tickets: Ticket[];
    filter: { status: string };
    counts: { open: number; all: number };
};

const TABS = [
    { value: 'open', label: 'Needs attention' },
    { value: 'pending', label: 'Awaiting reply' },
    { value: 'closed', label: 'Closed' },
    { value: 'all', label: 'All' },
];

function statusVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'open') return 'default';
    if (status === 'pending') return 'secondary';
    return 'outline';
}

function fmt(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

export default function AdminSupportIndex({ tickets, filter, counts }: PageProps) {
    return (
        <>
            <Head title="Support inbox" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Support inbox"
                    description={`${counts.open} open of ${counts.all} total — replies email the requester.`}
                />

                <div className="flex flex-wrap items-center gap-1.5 rounded-lg border border-border bg-card p-1">
                    {TABS.map((t) => (
                        <button
                            key={t.value}
                            type="button"
                            onClick={() => router.get('/admin/support', { status: t.value }, { preserveState: true, preserveScroll: true })}
                            className={`rounded px-3 py-1 text-xs font-medium transition-colors ${
                                filter.status === t.value ? 'bg-[#1b4638] text-white' : 'text-muted-foreground hover:bg-muted'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                <Card>
                    <CardContent className="p-0">
                        {tickets.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-16 text-muted-foreground">
                                <LifeBuoy className="size-7" />
                                <p className="text-sm">No tickets here.</p>
                            </div>
                        ) : (
                            <ul className="divide-y">
                                {tickets.map((t) => (
                                    <li key={t.id}>
                                        <Link href={`/admin/support/${t.id}`} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 hover:bg-muted/40">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">{t.subject}</span>
                                                    <Badge variant={statusVariant(t.status)} className="text-[10px] capitalize">{t.status_label}</Badge>
                                                    <Badge variant="outline" className="text-[10px] capitalize">{t.category}</Badge>
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {t.name} &lt;{t.email}&gt; · {t.source === 'in_app' ? 'In-app' : 'Contact form'}
                                                </div>
                                            </div>
                                            <div className="text-right text-xs text-muted-foreground">
                                                {t.assignee && <div>{t.assignee}</div>}
                                                <div>{fmt(t.last_reply_at ?? t.created_at)}</div>
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminSupportIndex.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Support', href: '/admin/support' },
    ],
};

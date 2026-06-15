import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, LifeBuoy, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Wedding = {
    id: number;
    slug: string;
    name: string;
    event_date: string | null;
    days_until: number | null;
    owner_name: string | null;
    owner_email: string | null;
    owner_plan: string | null;
    guest_count: number;
    vendor_count: number;
    task_count: number;
    created_at: string | null;
};

type PageProps = { weddings: Wedding[] };

function daysLabel(days: number | null): { text: string; variant: 'default' | 'secondary' | 'outline' } {
    if (days === null) return { text: 'No date', variant: 'outline' };
    if (days < 0) return { text: `${Math.abs(days)}d ago`, variant: 'secondary' };
    if (days === 0) return { text: 'Today!', variant: 'default' };
    return { text: `${days}d away`, variant: days <= 30 ? 'default' : 'outline' };
}

export default function AdminWeddings({ weddings }: PageProps) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) return weddings;
        return weddings.filter((w) =>
            w.name.toLowerCase().includes(term) ||
            (w.owner_name ?? '').toLowerCase().includes(term) ||
            (w.owner_email ?? '').toLowerCase().includes(term));
    }, [weddings, search]);

    function openWorkspace(w: Wedding) {
        router.post(`/admin/weddings/${w.slug}/support`);
    }

    return (
        <>
            <Head title="Weddings" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title="Weddings" description="Every wedding workspace on the platform. Open one to provide support." />

                <div className="relative max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by couple or owner…" className="pl-9" />
                </div>

                <Card>
                    <CardContent className="p-0">
                        {filtered.length === 0 ? (
                            <p className="py-12 text-center text-sm text-muted-foreground">No weddings match.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Wedding</th>
                                            <th className="px-4 py-3 font-medium">Owner</th>
                                            <th className="px-4 py-3 font-medium">Plan</th>
                                            <th className="px-4 py-3 font-medium">Date</th>
                                            <th className="px-4 py-3 text-right font-medium">Guests</th>
                                            <th className="px-4 py-3 text-right font-medium">Vendors</th>
                                            <th className="px-4 py-3" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((w) => {
                                            const days = daysLabel(w.days_until);
                                            return (
                                                <tr key={w.id} className="border-b last:border-0">
                                                    <td className="px-4 py-3">
                                                        <Link href={`/admin/weddings/${w.slug}`} className="font-medium hover:underline">{w.name}</Link>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div>{w.owner_name ?? '—'}</div>
                                                        {w.owner_email && <div className="text-xs text-muted-foreground">{w.owner_email}</div>}
                                                    </td>
                                                    <td className="px-4 py-3"><Badge variant="outline" className="capitalize">{w.owner_plan ?? 'free'}</Badge></td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-muted-foreground">{w.event_date ?? '—'}</span>
                                                            <Badge variant={days.variant}>{days.text}</Badge>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right tabular-nums">{w.guest_count}</td>
                                                    <td className="px-4 py-3 text-right tabular-nums">{w.vendor_count}</td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <a href={`/w/${w.slug}`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">
                                                                Site <ExternalLink className="size-3" />
                                                            </a>
                                                            <Button size="sm" variant="outline" onClick={() => openWorkspace(w)}>
                                                                <LifeBuoy className="size-4" /> Open workspace
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminWeddings.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Weddings', href: '/admin/weddings' },
    ],
};

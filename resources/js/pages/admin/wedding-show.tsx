import { Head, router } from '@inertiajs/react';
import { ExternalLink, LifeBuoy } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Member = { id: number; name: string; email: string; role: string | null };
type Vendor = { id: number; name: string; category: string; status: string };
type Quote = { id: number; vendor_name: string | null; status: string; created_at: string | null };

type PageProps = {
    wedding: {
        id: number;
        slug: string;
        name: string;
        event_date: string | null;
        owner: { name: string | null; email: string | null; plan: string | null };
        members: Member[];
    };
    vendors: Vendor[];
    quotes: Quote[];
};

export default function AdminWeddingShow({ wedding, vendors, quotes }: PageProps) {
    return (
        <>
            <Head title={wedding.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title={wedding.name} description={wedding.event_date ? `Wedding date: ${wedding.event_date}` : 'No wedding date set.'} />
                    <div className="flex gap-2">
                        <a href={`/w/${wedding.slug}`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-muted">
                            Public site <ExternalLink className="size-4" />
                        </a>
                        <Button onClick={() => router.post(`/admin/weddings/${wedding.slug}/support`)}>
                            <LifeBuoy className="size-4" /> Open workspace
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader><CardTitle className="text-base">Owner</CardTitle></CardHeader>
                    <CardContent className="flex flex-wrap items-center gap-x-8 gap-y-2 text-sm">
                        <div><span className="text-muted-foreground">Name</span><div className="font-medium">{wedding.owner.name ?? '—'}</div></div>
                        <div><span className="text-muted-foreground">Email</span><div className="font-medium">{wedding.owner.email ?? '—'}</div></div>
                        <div><span className="text-muted-foreground">Plan</span><div><Badge variant="outline" className="capitalize">{wedding.owner.plan ?? 'free'}</Badge></div></div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-3">
                    <ListCard title={`Collaborators (${wedding.members.length})`}>
                        {wedding.members.length === 0 ? <Empty label="No collaborators." /> : wedding.members.map((m) => (
                            <div key={m.id} className="flex items-center justify-between gap-2 px-2 py-1.5">
                                <span className="min-w-0"><span className="block truncate font-medium">{m.name}</span><span className="block truncate text-xs text-muted-foreground">{m.email}</span></span>
                                {m.role && <Badge variant="outline" className="shrink-0 capitalize">{m.role}</Badge>}
                            </div>
                        ))}
                    </ListCard>

                    <ListCard title={`Vendors (${vendors.length})`}>
                        {vendors.length === 0 ? <Empty label="No vendors." /> : vendors.map((v) => (
                            <div key={v.id} className="flex items-center justify-between gap-2 px-2 py-1.5">
                                <span className="min-w-0"><span className="block truncate font-medium">{v.name}</span><span className="block truncate text-xs text-muted-foreground">{v.category}</span></span>
                                <Badge variant="secondary" className="shrink-0">{v.status}</Badge>
                            </div>
                        ))}
                    </ListCard>

                    <ListCard title={`Quotes (${quotes.length})`}>
                        {quotes.length === 0 ? <Empty label="No quotes." /> : quotes.map((q) => (
                            <div key={q.id} className="flex items-center justify-between gap-2 px-2 py-1.5">
                                <span className="min-w-0"><span className="block truncate font-medium">{q.vendor_name ?? '—'}</span><span className="block truncate text-xs text-muted-foreground">{q.created_at}</span></span>
                                <Badge variant="outline" className="shrink-0">{q.status}</Badge>
                            </div>
                        ))}
                    </ListCard>
                </div>
            </div>
        </>
    );
}

function ListCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <Card>
            <CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader>
            <CardContent className="flex flex-col gap-0.5 text-sm">{children}</CardContent>
        </Card>
    );
}

function Empty({ label }: { label: string }) {
    return <p className="px-2 py-6 text-center text-sm text-muted-foreground">{label}</p>;
}

AdminWeddingShow.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Weddings', href: '/admin/weddings' },
    ],
};

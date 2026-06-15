import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Recent = { id: number; wedding_name: string | null; vendor_name: string | null; status: string; created_at: string | null; total?: number };

type PageProps = {
    stats: {
        inquiries: number;
        offers: number;
        bookings: number;
        gmv: number;
        platform_fees: number;
        inquiries_by_status: Record<string, number>;
        bookings_by_status: Record<string, number>;
    };
    recent: { inquiries: Recent[]; bookings: Recent[] };
};

const num = new Intl.NumberFormat('en-CA', { maximumFractionDigits: 0 });
const money = (n: number) => `$${num.format(n)}`;

export default function AdminMarketplace({ stats, recent }: PageProps) {
    return (
        <>
            <Head title="Marketplace" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title="Marketplace activity" description="Quote requests, offers, and bookings across every couple and vendor." />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Stat label="Quote requests" value={num.format(stats.inquiries)} />
                    <Stat label="Offers" value={num.format(stats.offers)} />
                    <Stat label="Bookings" value={num.format(stats.bookings)} />
                    <Stat label="GMV" value={money(stats.gmv)} accent />
                    <Stat label="Platform fees" value={money(stats.platform_fees)} accent />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle className="text-base">Recent quote requests</CardTitle></CardHeader>
                        <CardContent className="flex flex-col gap-0.5 text-sm">
                            {recent.inquiries.length === 0 ? <Empty /> : recent.inquiries.map((i) => (
                                <Row key={i.id} title={i.vendor_name} sub={i.wedding_name} right={i.status} />
                            ))}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle className="text-base">Recent bookings</CardTitle></CardHeader>
                        <CardContent className="flex flex-col gap-0.5 text-sm">
                            {recent.bookings.length === 0 ? <Empty /> : recent.bookings.map((b) => (
                                <Row key={b.id} title={b.vendor_name} sub={b.wedding_name} right={b.total !== undefined ? money(b.total) : b.status} />
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function Stat({ label, value, accent }: { label: string; value: string; accent?: boolean }) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className={`mt-1 text-2xl font-semibold tabular-nums ${accent ? 'text-[#775a19]' : ''}`}>{value}</div>
            </CardContent>
        </Card>
    );
}

function Row({ title, sub, right }: { title: string | null; sub: string | null; right: string }) {
    return (
        <div className="flex items-center justify-between gap-2 px-2 py-1.5">
            <span className="min-w-0">
                <span className="block truncate font-medium">{title ?? '—'}</span>
                <span className="block truncate text-xs text-muted-foreground">{sub ?? '—'}</span>
            </span>
            <span className="shrink-0 text-xs text-muted-foreground">{right}</span>
        </div>
    );
}

function Empty() {
    return <p className="px-2 py-6 text-center text-sm text-muted-foreground">Nothing yet.</p>;
}

AdminMarketplace.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Marketplace', href: '/admin/marketplace' },
    ],
};

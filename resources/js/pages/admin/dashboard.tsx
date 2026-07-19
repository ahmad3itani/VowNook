import { Head, Link } from '@inertiajs/react';
import { CalendarHeart, ShoppingBag, Users2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Stats = {
    total_weddings: number;
    upcoming_weddings: number;
    total_users: number;
    new_users_30d: number;
    couples: number;
    planners: number;
    vendors: number;
    vendor_profiles: number;
    vendors_pending: number;
    vendors_published: number;
    open_inquiries: number;
    total_bookings: number;
    gmv: number;
};

type PageProps = {
    stats: Stats;
    recent: {
        weddings: { id: number; slug: string; name: string; owner_name: string | null; created_at: string | null }[];
        users: { id: number; name: string; email: string; account_type: string; created_at: string | null }[];
        bookings: { id: number; wedding_name: string | null; vendor_name: string | null; total: number; status: string; created_at: string | null }[];
    };
};

const num = new Intl.NumberFormat('en-CA', { maximumFractionDigits: 0 });
const money = (n: number) => `$${num.format(n)}`;

export default function AdminDashboard({ stats, recent }: PageProps) {
    return (
        <>
            <Head title="Admin Console" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Platform console"
                    description="Everything happening across weddings, accounts, vendors, and the marketplace."
                />

                {/* Headline stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Weddings" value={num.format(stats.total_weddings)} sub={`${stats.upcoming_weddings} upcoming`} icon={<CalendarHeart className="size-4" />} href="/admin/weddings" />
                    <StatCard label="Users" value={num.format(stats.total_users)} sub={`${stats.new_users_30d} new in 30d`} icon={<Users2 className="size-4" />} href="/admin/users" />
                    <StatCard label="Vendor profiles" value={num.format(stats.vendor_profiles)} sub={`${stats.vendors_pending} pending review`} icon={<ShoppingBag className="size-4" />} href="/admin/vendors" />
                    <StatCard label="Marketplace GMV" value={money(stats.gmv)} sub={`${stats.total_bookings} bookings`} accent="text-[#1b4638]" href="/admin/marketplace" />
                </div>

                {/* Account / vendor breakdown */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <MiniStat label="Couples" value={stats.couples} />
                    <MiniStat label="Planners" value={stats.planners} />
                    <MiniStat label="Vendors" value={stats.vendors} />
                    <MiniStat label="Open quotes" value={stats.open_inquiries} />
                </div>

                {/* Recent activity */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <RecentCard title="New weddings">
                        {recent.weddings.length === 0 ? <Empty /> : recent.weddings.map((w) => (
                            <Link key={w.id} href={`/admin/weddings/${w.slug}`} className="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-muted">
                                <span className="truncate font-medium">{w.name}</span>
                                <span className="shrink-0 text-xs text-muted-foreground">{w.created_at}</span>
                            </Link>
                        ))}
                    </RecentCard>

                    <RecentCard title="New users">
                        {recent.users.length === 0 ? <Empty /> : recent.users.map((u) => (
                            <div key={u.id} className="flex items-center justify-between gap-2 px-2 py-1.5">
                                <span className="min-w-0">
                                    <span className="block truncate font-medium">{u.name}</span>
                                    <span className="block truncate text-xs text-muted-foreground">{u.email}</span>
                                </span>
                                <Badge variant="outline" className="shrink-0 capitalize">{u.account_type}</Badge>
                            </div>
                        ))}
                    </RecentCard>

                    <RecentCard title="Recent bookings">
                        {recent.bookings.length === 0 ? <Empty /> : recent.bookings.map((b) => (
                            <div key={b.id} className="flex items-center justify-between gap-2 px-2 py-1.5">
                                <span className="min-w-0">
                                    <span className="block truncate font-medium">{b.vendor_name ?? '—'}</span>
                                    <span className="block truncate text-xs text-muted-foreground">{b.wedding_name ?? '—'}</span>
                                </span>
                                <span className="shrink-0 tabular-nums">{money(b.total)}</span>
                            </div>
                        ))}
                    </RecentCard>
                </div>
            </div>
        </>
    );
}

function StatCard({ label, value, sub, accent, icon, href }: { label: string; value: string; sub?: string; accent?: string; icon?: React.ReactNode; href?: string }) {
    const body = (
        <CardContent className="px-5">
            <div className="flex items-center justify-between">
                <div className="text-sm text-muted-foreground">{label}</div>
                {icon && <div className="text-muted-foreground">{icon}</div>}
            </div>
            <div className={`mt-1 text-2xl font-semibold tabular-nums ${accent ?? ''}`}>{value}</div>
            {sub && <div className="mt-0.5 text-xs text-muted-foreground">{sub}</div>}
        </CardContent>
    );
    return href ? (
        <Link href={href} className="block">
            <Card className="lift transition-shadow hover:shadow-atelier-lg">{body}</Card>
        </Link>
    ) : (
        <Card>{body}</Card>
    );
}

function MiniStat({ label, value }: { label: string; value: number }) {
    return (
        <Card>
            <CardContent className="flex items-center justify-between px-5 py-4">
                <span className="text-sm text-muted-foreground">{label}</span>
                <span className="text-lg font-semibold tabular-nums">{num.format(value)}</span>
            </CardContent>
        </Card>
    );
}

function RecentCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <Card>
            <CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader>
            <CardContent className="flex flex-col gap-0.5 text-sm">{children}</CardContent>
        </Card>
    );
}

function Empty() {
    return <p className="px-2 py-6 text-center text-sm text-muted-foreground">Nothing yet.</p>;
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Console', href: '/admin/dashboard' }],
};

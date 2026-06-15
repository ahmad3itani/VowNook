import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, Clock, PauseCircle, Store } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Profile = {
    id: number;
    business_name: string;
    slug: string;
    category: string | null;
    category_label: string | null;
    status: string;
    status_label: string;
    owner_name: string | null;
    owner_email: string | null;
    services_count: number;
    media_count: number;
    created_at: string | null;
};

type Stats = {
    draft: number;
    pending_review: number;
    published: number;
    suspended: number;
};

type PageProps = {
    profiles: Profile[];
    stats: Stats;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    published: 'default',
    pending_review: 'secondary',
    draft: 'outline',
    suspended: 'destructive',
};

export default function AdminVendors({ profiles, stats }: PageProps) {
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [pendingId, setPendingId] = useState<number | null>(null);

    const filtered = statusFilter === 'all'
        ? profiles
        : profiles.filter((p) => p.status === statusFilter);

    function approve(id: number) {
        if (pendingId !== null) return;
        setPendingId(id);
        router.patch(`/admin/vendors/${id}/approve`, {}, {
            preserveScroll: true,
            onError: () => toast.error('Something went wrong. Please try again.'),
            onFinish: () => setPendingId(null),
        });
    }

    function suspend(id: number) {
        if (pendingId !== null) return;
        if (!confirm('Suspend this vendor? They will no longer appear in the marketplace.')) return;
        setPendingId(id);
        router.patch(`/admin/vendors/${id}/suspend`, {}, {
            preserveScroll: true,
            onError: () => toast.error('Something went wrong. Please try again.'),
            onFinish: () => setPendingId(null),
        });
    }

    return (
        <>
            <Head title="Vendor moderation" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Vendor listings"
                        description="Review and moderate vendor profiles submitted for the marketplace."
                    />
                    <Link
                        href="/admin/dashboard"
                        className="inline-flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-muted"
                    >
                        ← Dashboard
                    </Link>
                </div>

                {/* Status summary */}
                <div className="grid gap-4 sm:grid-cols-4">
                    <StatChip icon={<Store className="size-4" />} label="Draft" value={stats.draft} />
                    <StatChip icon={<Clock className="size-4 text-amber-500" />} label="Pending review" value={stats.pending_review} accent="text-amber-600" />
                    <StatChip icon={<CheckCircle className="size-4 text-green-600" />} label="Published" value={stats.published} accent="text-green-700" />
                    <StatChip icon={<PauseCircle className="size-4 text-destructive" />} label="Suspended" value={stats.suspended} accent="text-destructive" />
                </div>

                {/* Filter */}
                <div className="flex items-center gap-3">
                    <span className="text-sm text-muted-foreground">Filter by status:</span>
                    <Select value={statusFilter} onValueChange={(v: string) => setStatusFilter(v)}>
                        <SelectTrigger className="w-44">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="pending_review">Pending review</SelectItem>
                            <SelectItem value="published">Published</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
                        </SelectContent>
                    </Select>
                    <span className="text-sm text-muted-foreground">{filtered.length} result{filtered.length !== 1 ? 's' : ''}</span>
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        {filtered.length === 0 ? (
                            <p className="py-12 text-center text-sm text-muted-foreground">No vendor profiles matching this filter.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Business</th>
                                            <th className="px-4 py-3 font-medium">Owner</th>
                                            <th className="px-4 py-3 font-medium">Category</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 text-right font-medium">Services</th>
                                            <th className="px-4 py-3 text-right font-medium">Photos</th>
                                            <th className="px-4 py-3 font-medium">Joined</th>
                                            <th className="px-4 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((p) => (
                                            <tr key={p.id} className="border-b last:border-0 hover:bg-muted/30">
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">{p.business_name}</div>
                                                    <div className="text-xs text-muted-foreground">/{p.slug}</div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div>{p.owner_name ?? '—'}</div>
                                                    {p.owner_email && (
                                                        <div className="text-xs text-muted-foreground">{p.owner_email}</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">{p.category_label ?? '—'}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={STATUS_VARIANT[p.status] ?? 'outline'}>
                                                        {p.status_label}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">{p.services_count}</td>
                                                <td className="px-4 py-3 text-right tabular-nums">{p.media_count}</td>
                                                <td className="px-4 py-3 text-muted-foreground">{p.created_at ?? '—'}</td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2">
                                                        {(p.status === 'pending_review' || p.status === 'suspended') && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                className="h-7 border-green-600 text-green-700 hover:bg-green-50 hover:text-green-800"
                                                                disabled={pendingId !== null}
                                                                onClick={() => approve(p.id)}
                                                            >
                                                                Approve
                                                            </Button>
                                                        )}
                                                        {p.status === 'published' && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                className="h-7 border-destructive text-destructive hover:bg-destructive/10"
                                                                disabled={pendingId !== null}
                                                                onClick={() => suspend(p.id)}
                                                            >
                                                                Suspend
                                                            </Button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
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

function StatChip({
    icon,
    label,
    value,
    accent,
}: {
    icon: React.ReactNode;
    label: string;
    value: number;
    accent?: string;
}) {
    return (
        <Card>
            <CardContent className="px-5 py-4">
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">{label}</span>
                    {icon}
                </div>
                <div className={`mt-1 text-2xl font-semibold tabular-nums ${accent ?? ''}`}>{value}</div>
            </CardContent>
        </Card>
    );
}

AdminVendors.layout = {
    breadcrumbs: [
        { title: 'Admin', href: '/admin/dashboard' },
        { title: 'Vendor listings', href: '/admin/vendors' },
    ],
};

import { Head, router } from '@inertiajs/react';
import { ExternalLink, Flag } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Report = {
    id: number;
    type: string;
    reason: string;
    details: string | null;
    status: string;
    reporter: string | null;
    created_at: string | null;
    target: string;
    target_url: string | null;
};

type PageProps = { reports: Report[]; open_count: number };

const STATUS: Record<string, string> = {
    open: 'bg-red-100 text-red-700',
    reviewed: 'bg-amber-100 text-amber-700',
    actioned: 'bg-emerald-100 text-emerald-700',
    dismissed: 'bg-neutral-100 text-neutral-600',
};

export default function AdminReports({ reports, open_count }: PageProps) {
    function setStatus(id: number, status: string) {
        router.put(`/admin/reports/${id}`, { status }, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Reports" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Content reports"
                    description={`${open_count} open report${open_count === 1 ? '' : 's'} to review.`}
                />

                {reports.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-muted-foreground">
                            <Flag className="size-8 opacity-40" />
                            <p>No reports. The marketplace is clean.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-2">
                        {reports.map((r) => (
                            <Card key={r.id}>
                                <CardContent className="flex flex-wrap items-start justify-between gap-3 py-3">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline" className="text-xs">{r.type}</Badge>
                                            <span className="font-medium">{r.target}</span>
                                            {r.target_url && (
                                                <a href={r.target_url} target="_blank" rel="noreferrer" className="text-muted-foreground hover:text-foreground">
                                                    <ExternalLink className="size-3.5" />
                                                </a>
                                            )}
                                            <Badge className={`text-xs ${STATUS[r.status] ?? ''}`}>{r.status}</Badge>
                                        </div>
                                        <p className="mt-1 text-sm font-medium">{r.reason}</p>
                                        {r.details && <p className="text-sm text-muted-foreground">{r.details}</p>}
                                        <p className="mt-0.5 text-xs text-muted-foreground/70">
                                            {r.reporter ?? 'A user'} · {r.created_at}
                                        </p>
                                    </div>
                                    <div className="flex gap-1.5">
                                        <Button size="sm" variant="outline" onClick={() => setStatus(r.id, 'actioned')}>Action</Button>
                                        <Button size="sm" variant="ghost" onClick={() => setStatus(r.id, 'dismissed')}>Dismiss</Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AdminReports.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Reports', href: '/admin/reports' },
    ],
};

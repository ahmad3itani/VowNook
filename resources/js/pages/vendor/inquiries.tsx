import { Head, Link } from '@inertiajs/react';
import { CalendarDays, MessageSquare, Users } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatMoney } from '@/lib/format';

type InquiryRow = {
    id: number;
    status: string;
    status_label: string;
    couple_wedding: string | null;
    event_date: string | null;
    guest_count: number | null;
    budget_cents: number | null;
    has_offer: boolean;
    offer_status: string | null;
    created_at: string | null;
};

type Stats = {
    new: number;
    offered: number;
    accepted: number;
};

type PageProps = {
    inquiries: InquiryRow[];
    stats: Stats;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    requested: 'secondary',
    offered: 'default',
    accepted: 'default',
    declined: 'outline',
    closed: 'outline',
};

export default function VendorInquiries({ inquiries, stats }: PageProps) {
    return (
        <>
            <Head title="Inquiries" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Inquiries"
                    description="Couples requesting quotes for your services."
                />

                {/* Stats row */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <StatChip label="New inquiries" value={stats.new} accent={stats.new > 0 ? 'text-amber-600' : ''} />
                    <StatChip label="Offers sent" value={stats.offered} />
                    <StatChip label="Accepted" value={stats.accepted} accent="text-[#1b4638]" />
                </div>

                {inquiries.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-14">
                            <MessageSquare className="size-10 text-muted-foreground/30" />
                            <p className="text-sm text-muted-foreground">
                                No inquiries yet. Make sure your listing is published in the marketplace.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-2">
                        {inquiries.map((inq) => (
                            <Link key={inq.id} href={`/vendor/inquiries/${inq.id}`} className="block">
                                <Card className="transition-shadow hover:shadow-sm">
                                    <CardContent className="flex flex-wrap items-center gap-4 py-4">
                                        <div className="flex-1 space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium">
                                                    {inq.couple_wedding ?? 'Wedding'}
                                                </span>
                                                <Badge variant={STATUS_VARIANT[inq.status] ?? 'outline'}>
                                                    {inq.status_label}
                                                </Badge>
                                            </div>
                                            <div className="flex flex-wrap gap-3 text-xs text-muted-foreground">
                                                {inq.event_date && (
                                                    <span className="flex items-center gap-1">
                                                        <CalendarDays className="size-3" />
                                                        {inq.event_date}
                                                    </span>
                                                )}
                                                {inq.guest_count && (
                                                    <span className="flex items-center gap-1">
                                                        <Users className="size-3" />
                                                        {inq.guest_count} guests
                                                    </span>
                                                )}
                                                {inq.budget_cents && (
                                                    <span>Budget: {formatMoney(inq.budget_cents)}</span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                            <span>{inq.created_at}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

function StatChip({ label, value, accent }: { label: string; value: number; accent?: string }) {
    return (
        <Card>
            <CardContent className="px-5 py-4">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className={`mt-1 text-2xl font-semibold tabular-nums ${accent ?? ''}`}>{value}</div>
            </CardContent>
        </Card>
    );
}

VendorInquiries.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/vendor' },
        { title: 'Inquiries', href: '/vendor/inquiries' },
    ],
};

import { Head } from '@inertiajs/react';
import { Banknote, Clock, Percent, ReceiptText } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatMoney } from '@/lib/format';

type BookingRow = {
    id: number;
    wedding_name: string | null;
    event_date: string | null;
    total_cents: number;
    deposit_cents: number;
    platform_fee_cents: number;
    net_cents: number;
    status: string | null;
    status_label: string | null;
    created_at: string | null;
};

type PageProps = {
    totals: {
        earned_cents: number;
        pending_cents: number;
        fees_cents: number;
        bookings_count: number;
    };
    bookings: BookingRow[];
};

const STATUS_VARIANT: Record<string, string> = {
    pending_payment: 'bg-amber-100 text-amber-900',
    deposit_paid: 'bg-blue-100 text-blue-900',
    paid_in_full: 'bg-emerald-100 text-emerald-900',
    completed: 'bg-emerald-100 text-emerald-900',
    cancelled: 'bg-red-100 text-red-900',
};

export default function VendorEarnings({ totals, bookings }: PageProps) {
    const summary = [
        { label: 'Net earned', value: formatMoney(totals.earned_cents), icon: Banknote },
        { label: 'Pending payment', value: formatMoney(totals.pending_cents), icon: Clock },
        { label: 'Platform fees paid', value: formatMoney(totals.fees_cents), icon: Percent },
        { label: 'Total bookings', value: String(totals.bookings_count), icon: ReceiptText },
    ];

    return (
        <>
            <Head title="Earnings" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Earnings"
                    description="Your bookings, payouts after platform fees, and what's still pending."
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {summary.map((s) => (
                        <Card key={s.label}>
                            <CardContent className="flex items-center gap-3 py-4">
                                <div className="rounded-lg bg-[#775a19]/10 p-2.5">
                                    <s.icon className="size-5 text-[#775a19]" />
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">{s.label}</p>
                                    <p className="text-xl font-bold">{s.value}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {bookings.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-16">
                            <ReceiptText className="size-10 text-muted-foreground/30" />
                            <p className="text-sm text-muted-foreground">
                                No bookings yet. When a couple accepts one of your offers, it will show up here.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="overflow-x-auto p-0">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-3 font-medium">Wedding</th>
                                        <th className="px-4 py-3 font-medium">Event date</th>
                                        <th className="px-4 py-3 text-right font-medium">Total</th>
                                        <th className="px-4 py-3 text-right font-medium">Platform fee</th>
                                        <th className="px-4 py-3 text-right font-medium">Your payout</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {bookings.map((b) => (
                                        <tr key={b.id} className="border-b last:border-0 hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{b.wedding_name ?? '—'}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{b.event_date ?? '—'}</td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {formatMoney(b.total_cents)}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                                −{formatMoney(b.platform_fee_cents)}
                                            </td>
                                            <td className="px-4 py-3 text-right font-semibold tabular-nums text-[#775a19]">
                                                {formatMoney(b.net_cents)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className={`border-0 text-xs ${STATUS_VARIANT[b.status ?? ''] ?? ''}`}
                                                >
                                                    {b.status_label ?? b.status}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

VendorEarnings.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/vendor' },
        { title: 'Earnings', href: '/vendor/earnings' },
    ],
};

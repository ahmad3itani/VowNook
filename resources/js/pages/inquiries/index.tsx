import { Head, Link } from '@inertiajs/react';
import { GitCompareArrows, MessageSquare, Store } from 'lucide-react';
import Heading from '@/components/heading';
import { VendorsHubTabs } from '@/components/vendors-hub-tabs';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type InquiryCard = {
    id: number;
    vendor_name: string | null;
    vendor_slug: string | null;
    status: string;
    status_label: string;
    has_offer: boolean;
    created_at: string | null;
};

type PageProps = {
    inquiries: InquiryCard[];
    quote_badge: number;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    requested: 'secondary',
    offered: 'default',
    accepted: 'default',
    declined: 'outline',
    closed: 'outline',
};

export default function InquiriesIndex({ inquiries, quote_badge }: PageProps) {
    const hasOffers = inquiries.some((i) => i.has_offer);

    return (
        <>
            <Head title="My quotes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Vendors"
                        description="Track your quote requests and offers from marketplace vendors."
                    />
                    <div className="flex items-center gap-2">
                        {hasOffers && (
                            <Link
                                href="/vendors/quotes/compare"
                                className="inline-flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-muted"
                            >
                                <GitCompareArrows className="size-4" />
                                Compare offers
                            </Link>
                        )}
                        <Link
                            href="/vendors/marketplace"
                            className="inline-flex items-center gap-2 rounded-md bg-[#1b4638] px-3 py-2 text-sm font-medium text-white hover:opacity-90"
                        >
                            <Store className="size-4" />
                            Browse marketplace
                        </Link>
                    </div>
                </div>

                <VendorsHubTabs active="quotes" quoteBadge={quote_badge} />

                {inquiries.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-14">
                            <MessageSquare className="size-10 text-muted-foreground/30" />
                            <p className="text-sm text-muted-foreground">No inquiries yet.</p>
                            <Link
                                href="/vendors/marketplace"
                                className="inline-flex items-center gap-2 rounded-md bg-[#1b4638] px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                            >
                                Find vendors
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-3">
                        {inquiries.map((inq) => (
                            <Link key={inq.id} href={`/vendors/quotes/${inq.id}`} className="block">
                                <Card className="transition-shadow hover:shadow-sm">
                                    <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-10 items-center justify-center rounded-lg bg-muted">
                                                <Store className="size-5 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <p className="font-medium">{inq.vendor_name ?? 'Unknown vendor'}</p>
                                                <p className="text-xs text-muted-foreground">Sent {inq.created_at}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            {inq.has_offer && inq.status === 'offered' && (
                                                <Badge variant="default" className="bg-[#1b4638] text-xs">
                                                    Offer received
                                                </Badge>
                                            )}
                                            <Badge variant={STATUS_VARIANT[inq.status] ?? 'outline'}>
                                                {inq.status_label}
                                            </Badge>
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

InquiriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Vendors', href: '/vendors' },
        { title: 'My quotes', href: '/vendors/quotes' },
    ],
};

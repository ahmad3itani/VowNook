import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ExternalLink, GitCompareArrows } from 'lucide-react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { VendorsHubTabs } from '@/components/vendors-hub-tabs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatMoney } from '@/lib/format';

type Offer = {
    inquiry_id: number;
    vendor_name: string | null;
    vendor_slug: string | null;
    status: string;
    is_accepted: boolean;
    total_cents: number;
    deposit_cents: number;
    line_items: Array<{ name: string; amount_cents: number; qty?: number }>;
    valid_until: string | null;
    terms: string | null;
    can_accept: boolean;
};

type Group = {
    category: string;
    offers: Offer[];
};

type PageProps = {
    groups: Group[];
    quote_badge: number;
};

export default function QuoteCompare({ groups, quote_badge }: PageProps) {
    function accept(inquiryId: number) {
        if (!confirm('Accept this offer? A booking will be created in your planning workspace.')) return;
        router.post(`/vendors/quotes/${inquiryId}/accept`, {}, {
            onError: () => toast.error('Something went wrong. Please try again.'),
        });
    }

    return (
        <>
            <Head title="Compare offers" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Compare offers"
                    description="Weigh the quotes you've received side by side and pick the best fit."
                />

                <VendorsHubTabs active="quotes" quoteBadge={quote_badge} />

                <Link
                    href="/vendors/quotes"
                    className="flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> Back to quotes
                </Link>

                {groups.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-16">
                            <GitCompareArrows className="size-10 text-muted-foreground/30" />
                            <p className="text-sm text-muted-foreground">
                                No offers to compare yet. Once vendors send you quotes, they'll appear here grouped by category.
                            </p>
                            <Link
                                href="/vendors/marketplace"
                                className="rounded-md bg-[#775a19] px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                            >
                                Browse marketplace
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    groups.map((group) => (
                        <section key={group.category} className="space-y-3">
                            <h2 className="text-sm font-semibold text-muted-foreground">{group.category}</h2>
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {group.offers.map((offer) => (
                                    <Card
                                        key={offer.inquiry_id}
                                        className={offer.is_accepted ? 'border-[#775a19]/40 bg-[#775a19]/5' : ''}
                                    >
                                        <CardContent className="space-y-3 py-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <p className="font-semibold">{offer.vendor_name}</p>
                                                    {offer.vendor_slug && (
                                                        <Link
                                                            href={`/vendors/marketplace/${offer.vendor_slug}`}
                                                            className="flex items-center gap-1 text-xs text-[#775a19] hover:underline"
                                                        >
                                                            View profile <ExternalLink className="size-3" />
                                                        </Link>
                                                    )}
                                                </div>
                                                {offer.is_accepted && (
                                                    <Badge className="bg-[#775a19] text-xs">Accepted</Badge>
                                                )}
                                            </div>

                                            <div>
                                                <p className="text-2xl font-bold text-[#775a19]">
                                                    {formatMoney(offer.total_cents)}
                                                </p>
                                                {offer.deposit_cents > 0 && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatMoney(offer.deposit_cents)} deposit
                                                    </p>
                                                )}
                                            </div>

                                            {offer.line_items.length > 0 && (
                                                <div className="divide-y rounded-lg border text-xs">
                                                    {offer.line_items.map((li, i) => (
                                                        <div key={i} className="flex items-center justify-between px-3 py-1.5">
                                                            <span>{li.name}{li.qty && li.qty > 1 ? ` ×${li.qty}` : ''}</span>
                                                            <span>{formatMoney(li.amount_cents)}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}

                                            {offer.valid_until && (
                                                <p className="text-xs text-muted-foreground">Valid until {offer.valid_until}</p>
                                            )}

                                            <div className="flex gap-2 pt-1">
                                                {offer.can_accept && (
                                                    <Button
                                                        size="sm"
                                                        className="flex-1 bg-[#775a19] hover:bg-[#5c4414]"
                                                        onClick={() => accept(offer.inquiry_id)}
                                                    >
                                                        <CheckCircle2 className="mr-1.5 size-4" />
                                                        Accept
                                                    </Button>
                                                )}
                                                <Button size="sm" variant="outline" className="flex-1" asChild>
                                                    <Link href={`/vendors/quotes/${offer.inquiry_id}`}>Details</Link>
                                                </Button>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </section>
                    ))
                )}
            </div>
        </>
    );
}

QuoteCompare.layout = {
    breadcrumbs: [
        { title: 'Vendors', href: '/vendors' },
        { title: 'My quotes', href: '/vendors/quotes' },
        { title: 'Compare offers', href: '/vendors/quotes/compare' },
    ],
};

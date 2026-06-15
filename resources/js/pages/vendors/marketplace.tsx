import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    MarketplaceBrowse,
    MarketplaceCategory,
    MarketplaceFilters,
} from '@/components/marketplace/marketplace-browse';
import { VendorCardData } from '@/components/marketplace/vendor-card';
import { VendorsHubTabs } from '@/components/vendors-hub-tabs';

type PageProps = {
    profiles: VendorCardData[];
    categories: MarketplaceCategory[];
    filters: MarketplaceFilters;
    total: number;
    quote_badge: number;
};

export default function VendorsMarketplace({ profiles, categories, filters, total, quote_badge }: PageProps) {
    return (
        <>
            <Head title="Browse marketplace" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Vendors"
                    description="Discover marketplace vendors, request quotes, and manage your shortlist."
                />

                <VendorsHubTabs active="marketplace" quoteBadge={quote_badge} />

                <p className="text-sm text-muted-foreground">
                    Browsing {total} published vendor{total !== 1 ? 's' : ''}.
                </p>

                <MarketplaceBrowse
                    profiles={profiles}
                    categories={categories}
                    filters={filters}
                    endpoint="/vendors/marketplace"
                    hrefBase="/vendors/marketplace"
                />
            </div>
        </>
    );
}

VendorsMarketplace.layout = {
    breadcrumbs: [
        { title: 'Vendors', href: '/vendors' },
        { title: 'Browse marketplace', href: '/vendors/marketplace' },
    ],
};

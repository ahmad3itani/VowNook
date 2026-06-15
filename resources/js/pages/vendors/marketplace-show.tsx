import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import {
    MarketplaceAuthContext,
    MarketplaceProfile,
    ServiceOption,
    VendorProfileView,
} from '@/components/marketplace/vendor-profile-view';
import { VendorsHubTabs } from '@/components/vendors-hub-tabs';

type PageProps = {
    profile: MarketplaceProfile;
    auth_context: MarketplaceAuthContext;
    services_for_select: ServiceOption[];
    quote_badge: number;
};

export default function VendorsMarketplaceShow({ profile, auth_context, services_for_select, quote_badge }: PageProps) {
    return (
        <>
            <Head title={profile.business_name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <VendorsHubTabs active="marketplace" quoteBadge={quote_badge} />

                <Link
                    href="/vendors/marketplace"
                    className="flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> Back to browse
                </Link>

                {/* Shared profile display (cover, about, gallery, services, quote CTA, contact) */}
                <div className="-mx-4 overflow-hidden rounded-none">
                    <VendorProfileView
                        profile={profile}
                        authContext={auth_context}
                        services={services_for_select}
                    />
                </div>
            </div>
        </>
    );
}

VendorsMarketplaceShow.layout = {
    breadcrumbs: [
        { title: 'Vendors', href: '/vendors' },
        { title: 'Browse marketplace', href: '/vendors/marketplace' },
        { title: 'Vendor', href: '#' },
    ],
};

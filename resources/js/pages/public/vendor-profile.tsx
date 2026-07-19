import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import {
    MarketplaceAuthContext,
    MarketplaceProfile,
    ServiceOption,
    VendorProfileView,
} from '@/components/marketplace/vendor-profile-view';
import { SiteHeader } from '@/components/public/site-header';

type PageProps = {
    profile: MarketplaceProfile;
    auth_context: MarketplaceAuthContext;
    services_for_select: ServiceOption[];
};

export default function PublicVendorProfile({ profile, auth_context, services_for_select }: PageProps) {
    return (
        <>
            {/* Title/description/OG/JSON-LD are server-rendered in the blade head. */}
            <Head title={profile.business_name} />

            <div className="min-h-screen bg-background">
                <SiteHeader />

                <div className="mx-auto max-w-7xl px-4 pt-5">
                    <Link href="/marketplace" className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-[#1f5142]" prefetch>
                        <ArrowLeft className="size-4" />
                        Back to marketplace
                    </Link>
                </div>

                <VendorProfileView
                    profile={profile}
                    authContext={auth_context}
                    services={services_for_select}
                />
            </div>
        </>
    );
}

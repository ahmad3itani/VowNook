import { Head, Link } from '@inertiajs/react';
import {
    MarketplaceBrowse,
    MarketplaceCategory,
    MarketplaceFilters,
} from '@/components/marketplace/marketplace-browse';
import { VendorCardData } from '@/components/marketplace/vendor-card';
import { SiteHeader } from '@/components/public/site-header';

const fraunces = "font-['Fraunces']";

type PageProps = {
    profiles: VendorCardData[];
    categories: MarketplaceCategory[];
    filters: MarketplaceFilters;
    total: number;
};

export default function Marketplace({ profiles, categories, filters, total }: PageProps) {
    return (
        <>
            {/* SEO meta + structured data are server-rendered in the blade head. */}
            <Head title="Wedding Vendors in Ontario" />

            <div className="min-h-screen bg-background">
                <SiteHeader />

                {/* Hero */}
                <section className="border-b border-border bg-gradient-to-b from-secondary/40 to-background px-4 py-14 md:py-20">
                    <div className="mx-auto grid max-w-7xl items-center gap-10 md:grid-cols-12">
                        <div className="md:col-span-7">
                            <p className="mb-3 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">Ontario wedding vendors</p>
                            <h1 className={`${fraunces} max-w-3xl text-4xl leading-[1.05] font-light tracking-tight sm:text-5xl`}>
                                Find your <em className="text-[#8a651c]">wedding people.</em>
                            </h1>
                            <p className="mt-5 max-w-xl text-[15px] leading-relaxed text-muted-foreground">
                                Browse {total} reviewed Ontario vendor{total !== 1 ? 's' : ''} — photographers, venues, florists,
                                caterers and more. Compare real quotes and book, free.
                            </p>
                        </div>
                        <div className="hidden md:col-span-5 md:block">
                            <div className="grid grid-cols-2 gap-3">
                                <img src="/images/landing/florist.webp" alt="A florist arranging a cream bridal bouquet" className="aspect-[3/4] w-full rounded-xl border border-border object-cover shadow-lg" loading="lazy" />
                                <img src="/images/landing/photographer.webp" alt="A wedding photographer at work at golden hour" className="mt-8 aspect-[3/4] w-full rounded-xl border border-border object-cover shadow-lg" loading="lazy" />
                            </div>
                        </div>
                    </div>
                </section>

                <div className="mx-auto max-w-7xl px-4 py-10">
                    <MarketplaceBrowse
                        profiles={profiles}
                        categories={categories}
                        filters={filters}
                        endpoint="/marketplace"
                        hrefBase="/marketplace"
                    />
                </div>
            </div>
        </>
    );
}

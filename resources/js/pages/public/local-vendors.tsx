import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ChevronRight, MapPin } from 'lucide-react';
import { VendorCard, type VendorCardData } from '@/components/marketplace/vendor-card';

const fraunces = "font-['Fraunces']";

type CategoryItem = { slug: string; noun: string; label: string; url: string; cost: string | null; count: number };
type CityItem = { slug: string; name: string; url: string };
type Faq = { question: string; answer: string };

type Props = {
    place: { name: string; city: string | null; slug: string | null; blurb: string | null };
    categories: CategoryItem[];
    cities: CityItem[];
    vendors: VendorCardData[];
    faqs: Faq[];
    total_vendors: number;
};

function PublicNav() {
    return (
        <header className="border-b border-[#191613]/8 bg-[#faf6ef]/85 backdrop-blur-md">
            <nav className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-4 md:px-12">
                <Link href="/" className={`${fraunces} text-[20px] font-medium tracking-tight`}>
                    VowNook <span className="italic font-light text-[#8a651c]">Atelier</span>
                </Link>
                <div className="flex items-center gap-4 text-[13px]">
                    <Link href="/marketplace" className="text-[#52493d] hover:text-[#8a651c]">Marketplace</Link>
                    <Link href="/register" className="bg-[#191613] px-5 py-2 text-[11px] font-medium tracking-[0.18em] text-[#faf6ef] uppercase hover:bg-[#8a651c]">
                        Get started
                    </Link>
                </div>
            </nav>
        </header>
    );
}

export default function LocalVendors({ place, categories, cities, vendors, faqs, total_vendors }: Props) {
    const where = place.city ?? 'Ontario';

    return (
        <div className="min-h-screen bg-[#faf6ef] font-['DM_Sans'] text-[#191613]">
            <Head title={`Wedding Vendors in ${place.name}`} />
            <PublicNav />

            {/* Hero */}
            <section className="px-5 pt-14 pb-10 md:px-12 md:pt-20">
                <div className="mx-auto max-w-[1480px]">
                    <nav className="mb-5 flex flex-wrap items-center gap-1.5 text-[12px] text-[#52493d]">
                        <Link href="/marketplace" className="hover:text-[#8a651c]">Marketplace</Link>
                        <ChevronRight className="size-3.5" />
                        {place.city ? (
                            <>
                                <Link href="/wedding-vendors" className="hover:text-[#8a651c]">Wedding Vendors</Link>
                                <ChevronRight className="size-3.5" />
                                <span className="text-[#191613]">{place.city}</span>
                            </>
                        ) : (
                            <span className="text-[#191613]">Wedding Vendors</span>
                        )}
                    </nav>

                    <p className="mb-3 flex items-center gap-1.5 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">
                        {place.city && <MapPin className="size-3.5" />} {place.city ? `${place.city}, Ontario` : 'Ontario'}
                    </p>
                    <h1 className={`${fraunces} max-w-4xl text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                        Wedding vendors in <em className="text-[#8a651c]">{where}</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-[15px] leading-relaxed text-[#52493d]">
                        Find and compare {place.city ? `${place.city}` : 'Ontario'} wedding vendors — venues,
                        photographers, caterers, florists, planners and more — all in one place. Every listing is
                        reviewed before it goes live and every review is tied to a real booking, so there's no
                        pay-to-play. Browse by type below, see typical local costs, and request quotes for free.
                    </p>
                    {place.blurb && (
                        <p className="mt-4 max-w-2xl text-[14px] leading-relaxed text-[#52493d]/80">{place.blurb}</p>
                    )}
                </div>
            </section>

            {/* Category directory — the core of the head-term hub */}
            <section className="px-5 pb-14 md:px-12">
                <div className="mx-auto max-w-[1480px]">
                    <h2 className={`${fraunces} mb-6 text-2xl font-light`}>Browse {where} wedding vendors by type</h2>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {categories.map((c) => (
                            <Link
                                key={c.slug}
                                href={c.url}
                                className="group rounded-2xl border border-[#191613]/12 bg-white p-5 transition-colors hover:border-[#8a651c]"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <h3 className={`${fraunces} text-lg font-normal`}>{c.noun}</h3>
                                    {c.count > 0 && (
                                        <span className="shrink-0 rounded-full bg-[#f3ecdf] px-2.5 py-0.5 text-[11px] text-[#52493d]">
                                            {c.count}
                                        </span>
                                    )}
                                </div>
                                {c.cost && (
                                    <p className="mt-2 text-[13px] text-[#52493d]">
                                        Typically <span className="text-[#8a651c]">{c.cost}</span>
                                    </p>
                                )}
                                <span className="mt-3 inline-flex items-center gap-1 text-[12px] font-medium text-[#8a651c]">
                                    Browse {c.noun.toLowerCase()} <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
                                </span>
                            </Link>
                        ))}
                    </div>
                    <p className="mt-4 text-[12px] text-[#52493d]/70">
                        Costs shown are typical estimates for {where}; your quote depends on date, guest count and style.
                    </p>
                </div>
            </section>

            {/* Sample vendors */}
            {vendors.length > 0 && (
                <section className="px-5 pb-16 md:px-12">
                    <div className="mx-auto max-w-[1480px]">
                        <h2 className={`${fraunces} mb-5 text-2xl font-light`}>
                            Featured {place.city ? `${place.city} ` : ''}vendors
                        </h2>
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {vendors.map((v) => (
                                <VendorCard key={v.id} vendor={v} hrefBase="/marketplace" />
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Browse by city */}
            {cities.length > 0 && (
                <section className="border-t border-[#191613]/10 bg-[#f3ecdf] px-5 py-14 md:px-12">
                    <div className="mx-auto max-w-[1480px]">
                        <h2 className={`${fraunces} mb-5 text-2xl font-light`}>
                            {place.city ? 'Wedding vendors in other Ontario cities' : 'Browse wedding vendors by city'}
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {cities.map((c) => (
                                <Link
                                    key={c.slug}
                                    href={c.url}
                                    className="rounded-full border border-[#191613]/15 bg-[#faf6ef] px-4 py-2 text-sm text-[#52493d] hover:border-[#8a651c] hover:text-[#8a651c]"
                                >
                                    {c.name}
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* FAQ (brand answers — strong for AI/GEO citation) */}
            {faqs.length > 0 && (
                <section className="px-5 py-14 md:px-12">
                    <div className="mx-auto max-w-[760px]">
                        <h2 className={`${fraunces} mb-6 text-2xl font-light`}>Frequently asked questions</h2>
                        <div className="divide-y divide-[#191613]/10">
                            {faqs.map((f, i) => (
                                <div key={i} className="py-4">
                                    <h3 className="text-[15px] font-medium text-[#191613]">{f.question}</h3>
                                    <p className="mt-2 text-[15px] leading-relaxed text-[#52493d]">{f.answer}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* CTA */}
            <section className="bg-[#191613] px-5 py-16 text-center text-[#faf6ef] md:py-20">
                <h2 className={`${fraunces} text-3xl font-light sm:text-4xl`}>
                    Planning a{place.city ? ` ${place.city}` : 'n Ontario'} wedding?
                </h2>
                <p className="mx-auto mt-3 max-w-md text-sm text-white/70">
                    Create your free planning studio — guest list, budget, checklist and vendor quotes, all in one place.
                </p>
                <Link
                    href="/register"
                    className="mt-7 inline-flex items-center gap-2 bg-[#faf6ef] px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#191613] uppercase hover:bg-[#e9c176]"
                >
                    Start planning — free <ArrowRight className="size-4" />
                </Link>
            </section>
        </div>
    );
}

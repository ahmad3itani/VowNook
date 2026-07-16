import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ChevronRight, MapPin } from 'lucide-react';
import { VendorCard, type VendorCardData } from '@/components/marketplace/vendor-card';

const fraunces = "font-['Fraunces']";

type CityLink = { slug: string; name: string; count: number; url: string };
type CatLink = { noun: string; url: string };

type Faq = { question: string; answer: string };
type Cost = { low_cents: number; high_cents: number; unit: string; note: string; display: string };

type Props = {
    category: { slug: string; noun: string; label: string };
    vendors: VendorCardData[];
    cities: CityLink[];
    total: number;
    cost: Cost | null;
    other_categories: CatLink[];
    intro_html: string | null;
    faqs: Faq[];
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

export default function LocalCategory({ category, vendors, cities, total, cost, other_categories, intro_html, faqs }: Props) {
    return (
        <div className="min-h-screen bg-[#faf6ef] font-['DM_Sans'] text-[#191613]">
            <Head title={`${category.noun} in Ontario`} />
            <PublicNav />

            <section className="px-5 pt-14 pb-10 md:px-12 md:pt-20">
                <div className="mx-auto max-w-[1480px]">
                    <nav className="mb-5 flex flex-wrap items-center gap-1.5 text-[12px] text-[#52493d]">
                        <Link href="/marketplace" className="hover:text-[#8a651c]">Marketplace</Link>
                        <ChevronRight className="size-3.5" />
                        <span className="text-[#191613]">{category.noun}</span>
                    </nav>
                    <p className="mb-3 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">Ontario</p>
                    <h1 className={`${fraunces} max-w-4xl text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                        {category.noun} in <em className="text-[#8a651c]">Ontario</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-[15px] leading-relaxed text-[#52493d]">
                        Browse {total > 0 ? total : 'trusted'} {category.noun.toLowerCase()} across Ontario. Every listing is
                        reviewed before it goes live — compare portfolios, packages and verified reviews, and request
                        quotes for free.
                    </p>
                </div>
            </section>

            {/* Typical cost — province-wide range, a high-intent SEO hook. */}
            {cost && (
                <section className="px-5 pb-10 md:px-12">
                    <div className="mx-auto max-w-[760px] rounded-2xl border border-[#191613]/12 bg-white p-6 md:p-7">
                        <p className="text-[11px] tracking-[0.28em] text-[#8a651c] uppercase">Typical cost in Ontario</p>
                        <p className={`${fraunces} mt-2 text-2xl leading-tight font-light md:text-[28px]`}>
                            {category.noun} typically cost{' '}
                            <span className="text-[#8a651c]">{cost.display}</span>
                        </p>
                        <p className="mt-2.5 text-[14px] leading-relaxed text-[#52493d]">
                            Estimated range — {cost.note}. Costs run higher in Toronto and the GTA, and lower in
                            southwestern and northern Ontario.
                        </p>
                    </div>
                </section>
            )}

            {/* Local guide (stored, data-backed) */}
            {intro_html && (
                <section className="px-5 pb-12 md:px-12">
                    <div
                        className="mx-auto max-w-[760px] space-y-4 text-[15px] leading-relaxed text-[#52493d] [&_a]:text-[#8a651c] [&_a]:underline"
                        dangerouslySetInnerHTML={{ __html: intro_html }}
                    />
                </section>
            )}

            {/* Cities */}
            <section className="px-5 pb-12 md:px-12">
                <div className="mx-auto max-w-[1480px]">
                    <h2 className={`${fraunces} mb-5 text-2xl font-light`}>Browse by city</h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        {cities.map((c) => (
                            <Link
                                key={c.slug}
                                href={c.url}
                                className="group flex items-center justify-between rounded-xl border border-[#191613]/12 bg-white px-4 py-3.5 transition-colors hover:border-[#8a651c]"
                            >
                                <span className="flex items-center gap-2 text-sm font-medium">
                                    <MapPin className="size-3.5 text-[#8a651c]" />
                                    {c.name}
                                </span>
                                {c.count > 0 && <span className="text-xs text-[#52493d]">{c.count}</span>}
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* Vendors */}
            {vendors.length > 0 && (
                <section className="px-5 pb-16 md:px-12">
                    <div className="mx-auto max-w-[1480px]">
                        <h2 className={`${fraunces} mb-5 text-2xl font-light`}>Featured {category.noun.toLowerCase()}</h2>
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {vendors.map((v) => (
                                <VendorCard key={v.id} vendor={v} hrefBase="/marketplace" />
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Other categories */}
            <section className="border-t border-[#191613]/10 bg-[#f3ecdf] px-5 py-14 md:px-12">
                <div className="mx-auto max-w-[1480px]">
                    <h2 className={`${fraunces} mb-5 text-2xl font-light`}>Other wedding vendors in Ontario</h2>
                    <div className="flex flex-wrap gap-2">
                        {other_categories.map((c) => (
                            <Link
                                key={c.url}
                                href={c.url}
                                className="rounded-full border border-[#191613]/15 bg-[#faf6ef] px-4 py-2 text-sm text-[#52493d] hover:border-[#8a651c] hover:text-[#8a651c]"
                            >
                                {c.noun}
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* FAQ */}
            {faqs.length > 0 && (
                <section className="px-5 pb-16 md:px-12">
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

            <section className="bg-[#191613] px-5 py-16 text-center text-[#faf6ef] md:py-20">
                <h2 className={`${fraunces} text-3xl font-light sm:text-4xl`}>Plan it all in one place</h2>
                <p className="mx-auto mt-3 max-w-md text-sm text-white/70">
                    Free guest list, budget, checklist and vendor quotes — your whole wedding, organised.
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

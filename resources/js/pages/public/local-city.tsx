import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ChevronRight, MapPin } from 'lucide-react';
import { RelatedGuides, type Guide } from '@/components/marketing/related-guides';
import { VendorCard, type VendorCardData } from '@/components/marketplace/vendor-card';

const fraunces = "font-['Newsreader']";

type LinkItem = { name?: string; noun?: string; url: string };
type Faq = { question: string; answer: string };
type Cost = { low_cents: number; high_cents: number; unit: string; note: string; display: string };

type Props = {
    category: { slug: string; noun: string; label: string };
    city: { slug: string; name: string; blurb: string };
    vendors: VendorCardData[];
    total: number;
    price_range: string | null;
    cost: Cost | null;
    other_cities: LinkItem[];
    other_categories: LinkItem[];
    hub_url: string;
    intro_html: string | null;
    guides: Guide[];
    faqs: Faq[];
};

function PublicNav() {
    return (
        <header className="border-b border-[#0f1c17]/8 bg-[#f1f0ea]/85 backdrop-blur-md">
            <nav className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-4 md:px-12">
                <Link href="/" className={`${fraunces} text-[20px] font-medium tracking-tight`}>
                    VowNook <span className="italic font-light text-[#1f5142]">Atelier</span>
                </Link>
                <div className="flex items-center gap-4 text-[13px]">
                    <Link href="/marketplace" className="text-[#4b5850] hover:text-[#1f5142]">Marketplace</Link>
                    <Link href="/register" className="cta-press px-5 py-2 text-[11px] font-medium tracking-[0.18em] uppercase">
                        Get started
                    </Link>
                </div>
            </nav>
        </header>
    );
}

export default function LocalCity({
    category,
    city,
    vendors,
    total,
    price_range,
    cost,
    other_cities,
    other_categories,
    guides,
    hub_url,
    intro_html,
    faqs,
}: Props) {
    return (
        <div className="min-h-screen bg-[#f1f0ea] font-['Instrument_Sans'] text-[#0f1c17]">
            <Head title={`${category.noun} in ${city.name}, Ontario`} />
            <PublicNav />

            {/* Hero */}
            <section className="px-5 pt-14 pb-10 md:px-12 md:pt-20">
                <div className="mx-auto max-w-[1480px]">
                    <nav className="mb-5 flex flex-wrap items-center gap-1.5 text-[12px] text-[#4b5850]">
                        <Link href="/marketplace" className="hover:text-[#1f5142]">Marketplace</Link>
                        <ChevronRight className="size-3.5" />
                        <Link href={hub_url} className="hover:text-[#1f5142]">{category.noun}</Link>
                        <ChevronRight className="size-3.5" />
                        <span className="text-[#0f1c17]">{city.name}</span>
                    </nav>

                    <p className="mb-3 flex items-center gap-1.5 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">
                        <MapPin className="size-3.5" /> {city.name}, Ontario
                    </p>
                    <h1 className={`${fraunces} max-w-4xl text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                        {category.noun} in <em className="text-[#1f5142]">{city.name}</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-[15px] leading-relaxed text-[#4b5850]">
                        {total > 0
                            ? `Compare ${total} ${category.noun.toLowerCase()} serving ${city.name} and the surrounding area${price_range ? `, with packages from ${price_range}` : ''}. Every vendor is reviewed before listing — view portfolios, read verified reviews, and request quotes for free.`
                            : `We're adding ${category.noun.toLowerCase()} in ${city.name} right now. Browse the full marketplace, or create your free planning studio and we'll help you find the right match.`}
                    </p>
                    {/* The local guide opens with this same city context, so only
                        show it here when there's no guide — never both. */}
                    {!intro_html && (
                        <p className="mt-4 max-w-2xl text-[14px] leading-relaxed text-[#4b5850]/80">{city.blurb}</p>
                    )}
                </div>
            </section>

            {/* Typical cost — unique per-city data, and a high-intent SEO hook. */}
            {cost && (
                <section className="px-5 pb-10 md:px-12">
                    <div className="mx-auto max-w-[760px] rounded-2xl border border-[#0f1c17]/12 bg-white p-6 md:p-7">
                        <p className="text-[11px] tracking-[0.28em] text-[#1f5142] uppercase">Typical cost</p>
                        <p className={`${fraunces} mt-2 text-2xl leading-tight font-light md:text-[28px]`}>
                            {category.noun} in {city.name} cost{' '}
                            <span className="text-[#1f5142]">{cost.display}</span>
                        </p>
                        <p className="mt-2.5 text-[14px] leading-relaxed text-[#4b5850]">
                            Estimated range — {cost.note}. Your final quote depends on your date, guest count and style.
                        </p>
                    </div>
                </section>
            )}

            {/* Local guide (stored, data-backed) */}
            {intro_html && (
                <section className="px-5 pb-10 md:px-12">
                    <div
                        className="mx-auto max-w-[760px] space-y-4 text-[15px] leading-relaxed text-[#4b5850] [&_a]:text-[#1f5142] [&_a]:underline"
                        dangerouslySetInnerHTML={{ __html: intro_html }}
                    />
                </section>
            )}

            {/* Vendor grid */}
            <section className="px-5 pb-16 md:px-12">
                <div className="mx-auto max-w-[1480px]">
                    {vendors.length > 0 ? (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {vendors.map((v) => (
                                <VendorCard key={v.id} vendor={v} hrefBase="/marketplace" />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-dashed border-[#0f1c17]/15 p-12 text-center">
                            <h2 className={`${fraunces} text-2xl font-light`}>No listings here yet</h2>
                            <p className="mx-auto mt-2 max-w-md text-sm text-[#4b5850]">
                                Be the first to find {category.noun.toLowerCase()} for your {city.name} wedding.
                            </p>
                            <Link
                                href="/marketplace"
                                className="mt-6 inline-flex items-center gap-2 cta-press px-7 py-3 text-[11px] font-semibold tracking-[0.2em] uppercase"
                            >
                                Browse all vendors <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    )}
                </div>
            </section>

            {/* Internal linking */}
            <section className="border-t border-[#0f1c17]/10 bg-[#e7e9e2] px-5 py-14 md:px-12">
                <div className="mx-auto grid max-w-[1480px] gap-12 md:grid-cols-2">
                    <div>
                        <h2 className={`${fraunces} mb-5 text-2xl font-light`}>
                            {category.noun} in other Ontario cities
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {other_cities.map((c) => (
                                <Link
                                    key={c.url}
                                    href={c.url}
                                    className="rounded-full border border-[#0f1c17]/15 bg-[#f1f0ea] px-4 py-2 text-sm text-[#4b5850] hover:border-[#1f5142] hover:text-[#1f5142]"
                                >
                                    {category.noun} in {c.name}
                                </Link>
                            ))}
                        </div>
                    </div>
                    <div>
                        <h2 className={`${fraunces} mb-5 text-2xl font-light`}>
                            Other wedding vendors in {city.name}
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {other_categories.map((c) => (
                                <Link
                                    key={c.url}
                                    href={c.url}
                                    className="rounded-full border border-[#0f1c17]/15 bg-[#f1f0ea] px-4 py-2 text-sm text-[#4b5850] hover:border-[#1f5142] hover:text-[#1f5142]"
                                >
                                    {c.noun} in {city.name}
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <RelatedGuides guides={guides} />

            {/* FAQ */}
            {faqs.length > 0 && (
                <section className="px-5 py-14 md:px-12">
                    <div className="mx-auto max-w-[760px]">
                        <h2 className={`${fraunces} mb-6 text-2xl font-light`}>
                            {category.noun} in {city.name} — FAQs
                        </h2>
                        <div className="divide-y divide-[#0f1c17]/10">
                            {faqs.map((f, i) => (
                                <div key={i} className="py-4">
                                    <h3 className="text-[15px] font-medium text-[#0f1c17]">{f.question}</h3>
                                    <p className="mt-2 text-[15px] leading-relaxed text-[#4b5850]">{f.answer}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* CTA */}
            <section className="bg-[#0f1c17] px-5 py-16 text-center text-[#f1f0ea] md:py-20">
                <h2 className={`${fraunces} text-3xl font-light sm:text-4xl`}>
                    Planning a {city.name} wedding?
                </h2>
                <p className="mx-auto mt-3 max-w-md text-sm text-white/70">
                    Create your free planning studio — guest list, budget, checklist and vendor quotes, all in one place.
                </p>
                <Link
                    href="/register"
                    className="mt-7 inline-flex items-center gap-2 bg-[#f1f0ea] px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#0f1c17] uppercase hover:bg-[#7fb79e]"
                >
                    Start planning — free <ArrowRight className="size-4" />
                </Link>
            </section>
        </div>
    );
}

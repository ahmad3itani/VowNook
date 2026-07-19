import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';

const fraunces = "font-['Newsreader']";

type Faq = { q: string; a: string };

const freeFeatures = [
    'Guest list, RSVP tracking & meal choices',
    'Budget tracker, checklist & timeline',
    'Wedding website builder (publish with Atelier)',
    'Browse the marketplace, request quotes & book — free',
    'Up to 25 guests · 15 gallery photos',
];

const atelierFeatures = [
    'Everything in Free, with the limits lifted — 500 guests, 10 collaborators, 1,000 photos',
    'Publish your wedding website with its own web address',
    'The seating studio — floor plan, tap-to-assign, printables',
    'Registry with cash funds, multi-event RSVP & travel pages',
    'Save-the-dates & guest broadcasts with open tracking',
    'The AI assistant — checklist, budget & timeline drafted for you',
];

const plannerFeatures = [
    'Unlimited client weddings, each with every Atelier feature',
    'Planner HQ dashboard across all of your clients',
    'Checklist & budget templates you reuse per client',
    'A public marketplace listing for your planning business',
];

function Tick() {
    return <Check className="mt-0.5 size-4 flex-none text-[#1f5142]" aria-hidden />;
}

export default function Pricing({ faqs }: { faqs: Faq[] }) {
    return (
        <div className="min-h-screen bg-[#f1f0ea] font-['Instrument_Sans'] text-[#0f1c17] antialiased selection:bg-[#7fb79e]/40">
            {/* Description/canonical/OG are server-rendered in the blade head. */}
            <Head title="Pricing" />

            {/* Header */}
            <header className="fixed inset-x-0 top-0 z-50 border-b border-[#0f1c17]/8 bg-[#f1f0ea]/85 backdrop-blur-md">
                <nav className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-4 md:px-12">
                    <Link href="/" className="flex items-center gap-2.5" aria-label="VowNook home">
                        <img src="/images/brand/logo-mark.svg" alt="" className="size-9 rounded-md border border-[#0f1c17]/10" />
                        <span className={`${fraunces} text-[22px] font-medium tracking-tight`}>VowNook</span>
                    </Link>
                    <div className="hidden items-center gap-9 md:flex">
                        <a href="#couples" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">For couples</a>
                        <a href="#planners" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">For planners</a>
                        <a href="#vendors" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">For vendors</a>
                        <Link href="/how-it-works" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">How it works</Link>
                    </div>
                    <Link
                        href="/register"
                        className="cta-press px-6 py-2.5 text-[11px] font-medium tracking-[0.18em] uppercase"
                    >
                        Get started
                    </Link>
                </nav>
            </header>

            {/* Hero */}
            <section className="px-5 pt-32 pb-12 md:px-12 md:pt-40 md:pb-16">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal>
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">Pricing</p>
                        <h1 className={`${fraunces} max-w-3xl text-5xl leading-[1.02] font-light sm:text-6xl md:text-7xl`}>
                            Honest pricing, <em className="text-[#1f5142]">no subscription traps.</em>
                        </h1>
                        <p className="mt-6 max-w-xl text-[15px] leading-relaxed text-[#4b5850]">
                            Couples plan free and pay once — not monthly — if they want the extras.
                            Vendors list free and pay only when a couple actually books. That's the
                            whole model.
                        </p>
                    </Reveal>
                </div>
            </section>

            {/* Couple + planner tiers */}
            <section id="couples" className="px-5 pb-20 md:px-12 md:pb-28">
                <Stagger className="mx-auto grid max-w-[1480px] gap-6 md:grid-cols-3">
                    {/* Free */}
                    <StaggerItem className="flex flex-col border border-[#0f1c17]/10 bg-white p-8">
                        <p className="text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">For couples</p>
                        <h2 className={`${fraunces} mt-3 text-3xl font-light`}>Free</h2>
                        <p className={`${fraunces} mt-4 text-5xl font-light`}>
                            $0<span className="ml-2 align-middle text-sm text-[#4b5850]">forever</span>
                        </p>
                        <p className="mt-4 text-sm leading-relaxed text-[#4b5850]">
                            The full planning studio and the marketplace. No credit card, no trial clock.
                        </p>
                        <ul className="mt-6 mb-8 space-y-3 text-sm text-[#39433d]">
                            {freeFeatures.map((f) => (
                                <li key={f} className="flex gap-2.5"><Tick />{f}</li>
                            ))}
                        </ul>
                        <Link
                            href="/register"
                            className="mt-auto inline-flex items-center justify-center gap-2 border border-[#0f1c17] px-6 py-3.5 text-[11px] font-semibold tracking-[0.2em] uppercase transition-colors hover:bg-[#0f1c17] hover:text-[#f1f0ea]"
                        >
                            Start free
                        </Link>
                    </StaggerItem>

                    {/* Atelier */}
                    <StaggerItem className="relative flex flex-col border-2 border-[#1f5142] bg-white p-8 shadow-[0_30px_60px_-30px_rgba(138,101,28,0.45)]">
                        <span className="absolute -top-3 left-8 bg-[#1f5142] px-3 py-1 text-[10px] font-semibold tracking-[0.18em] text-white uppercase">
                            Most loved
                        </span>
                        <p className="text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">For couples</p>
                        <h2 className={`${fraunces} mt-3 text-3xl font-light`}>Atelier</h2>
                        <p className={`${fraunces} mt-4 text-5xl font-light`}>
                            $99<span className="ml-2 align-middle text-sm text-[#4b5850]">once · per wedding</span>
                        </p>
                        <p className="mt-4 text-sm leading-relaxed text-[#4b5850]">
                            One payment covers your whole engagement — it's a wedding, not a subscription.
                        </p>
                        <ul className="mt-6 mb-8 space-y-3 text-sm text-[#39433d]">
                            {atelierFeatures.map((f) => (
                                <li key={f} className="flex gap-2.5"><Tick />{f}</li>
                            ))}
                        </ul>
                        <Link
                            href="/register"
                            className="mt-auto inline-flex items-center justify-center gap-2 cta-press px-6 py-3.5 text-[11px] font-semibold tracking-[0.2em] uppercase"
                        >
                            Start free, upgrade in-app <ArrowRight className="size-3.5" />
                        </Link>
                    </StaggerItem>

                    {/* Planner HQ */}
                    <StaggerItem className="flex flex-col border border-[#0f1c17]/10 bg-white p-8">
                        <p id="planners" className="scroll-mt-24 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">For planners</p>
                        <h2 className={`${fraunces} mt-3 text-3xl font-light`}>Planner HQ</h2>
                        <p className={`${fraunces} mt-4 text-5xl font-light`}>
                            $499<span className="ml-2 align-middle text-sm text-[#4b5850]">per year</span>
                        </p>
                        <p className="mt-4 text-sm leading-relaxed text-[#4b5850]">
                            Run every client wedding from one professional workspace.
                        </p>
                        <ul className="mt-6 mb-8 space-y-3 text-sm text-[#39433d]">
                            {plannerFeatures.map((f) => (
                                <li key={f} className="flex gap-2.5"><Tick />{f}</li>
                            ))}
                        </ul>
                        <Link
                            href="/register"
                            className="mt-auto inline-flex items-center justify-center gap-2 border border-[#0f1c17] px-6 py-3.5 text-[11px] font-semibold tracking-[0.2em] uppercase transition-colors hover:bg-[#0f1c17] hover:text-[#f1f0ea]"
                        >
                            Create a planner account
                        </Link>
                    </StaggerItem>
                </Stagger>
            </section>

            {/* Vendors */}
            <section id="vendors" className="border-t border-[#0f1c17]/10 bg-[#0f1c17] px-5 py-20 text-[#f1f0ea] md:px-12 md:py-28">
                <div className="mx-auto grid max-w-[1480px] items-center gap-12 md:grid-cols-2">
                    <Reveal>
                        <p className="mb-3 text-[11px] tracking-[0.3em] text-[#bfd8cb] uppercase">For vendors</p>
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            List free. Pay only <em className="text-[#bfd8cb]">when you're booked.</em>
                        </h2>
                        <p className="mt-6 max-w-lg text-[15px] leading-relaxed text-[#cfc6b6]">
                            No subscription, no pay-to-rank, no contract. Your portfolio, packages and
                            inquiries cost nothing. When a couple books you through VowNook, a small
                            success fee comes off that booking — and it's capped, so big weddings don't
                            mean big fees.
                        </p>
                        <Link
                            href="/register"
                            className="mt-8 inline-flex items-center gap-3 bg-[#bfd8cb] px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#0f1c17] uppercase transition-colors hover:bg-white"
                        >
                            List your business <ArrowRight className="size-3.5" />
                        </Link>
                    </Reveal>
                    <Reveal delay={0.15}>
                        <div className="border border-white/15 p-8">
                            <p className={`${fraunces} text-2xl font-light`}>The success fee, in full</p>
                            <ul className="mt-6 space-y-4 text-[15px] text-[#e2dccf]">
                                <li className="flex justify-between gap-6 border-b border-white/10 pb-4"><span>Listing, portfolio &amp; inquiries</span><span className={fraunces}>$0</span></li>
                                <li className="flex justify-between gap-6 border-b border-white/10 pb-4"><span>On the first $5,000 of a booking</span><span className={fraunces}>8%</span></li>
                                <li className="flex justify-between gap-6 border-b border-white/10 pb-4"><span>On everything above $5,000</span><span className={fraunces}>5%</span></li>
                                <li className="flex justify-between gap-6"><span>Maximum fee, per booking</span><span className={fraunces}>$1,000</span></li>
                            </ul>
                            <p className="mt-6 text-[13px] text-[#8c8478]">
                                Example: a $8,000 booking = 8% of $5,000 + 5% of $3,000 = $550. Payouts go
                                straight to your bank through Stripe.
                            </p>
                        </div>
                    </Reveal>
                </div>
            </section>

            {/* FAQ */}
            <section id="faq" className="px-5 py-20 md:px-12 md:py-28">
                <div className="mx-auto max-w-[880px]">
                    <Reveal className="mb-12">
                        <p className="mb-3 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">Questions</p>
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            Asked <em className="text-[#1f5142]">honestly answered.</em>
                        </h2>
                    </Reveal>
                    <Stagger className="divide-y divide-[#0f1c17]/10">
                        {faqs.map((f) => (
                            <StaggerItem key={f.q} className="py-7">
                                <h3 className={`${fraunces} text-xl font-normal`}>{f.q}</h3>
                                <p className="mt-3 max-w-2xl text-[15px] leading-relaxed text-[#4b5850]">{f.a}</p>
                            </StaggerItem>
                        ))}
                    </Stagger>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-[#0f1c17]/10 py-10">
                <div className="mx-auto flex max-w-[1480px] flex-wrap items-center justify-between gap-4 px-5 text-[13px] text-[#4b5850] md:px-12">
                    <span>© {new Date().getFullYear()} VowNook — made in Ontario.</span>
                    <div className="flex flex-wrap gap-6">
                        <Link href="/how-it-works" className="hover:text-[#1f5142]">How it works</Link>
                        <Link href="/marketplace" className="hover:text-[#1f5142]">Marketplace</Link>
                        <a href="/shop" className="hover:text-[#1f5142]">Shop</a>
                        <Link href="/terms" className="hover:text-[#1f5142]">Terms</Link>
                        <Link href="/privacy" className="hover:text-[#1f5142]">Privacy</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}

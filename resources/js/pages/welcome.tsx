import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    ArrowRight,
    ArrowUpRight,
    CalendarDays,
    Check,
    Gift,
    GitCompareArrows,
    Globe,
    HeartHandshake,
    Inbox,
    LayoutGrid,
    MailCheck,
    MailOpen,
    Megaphone,
    Menu,
    PenLine,
    Plane,
    Printer,
    Users,
    Wallet,
    X,
} from 'lucide-react';
import { type ReactNode, useState } from 'react';
import { BudgetInstrument, type BudgetModel } from '@/components/marketing/budget-instrument';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { dashboard, login, register } from '@/routes';

const fraunces = "font-['Newsreader']";
const DEMO = '/w/amelia-and-julian';

const IMG = {
    hero: '/images/landing/hero.webp',
    florist: '/images/landing/florist.webp',
    tablescape: '/images/landing/tablescape.webp',
    photographer: '/images/landing/photographer.webp',
    reception: '/images/landing/reception.webp',
    registry: '/images/landing/registry.webp',
    travel: '/images/landing/travel.webp',
    stationery: '/images/landing/stationery.webp',
    thankyou: '/images/landing/thankyou.webp',
};

const CATEGORIES = [
    'Venues', 'Catering', 'Photography', 'Florals', 'Music', 'Bakery',
    'Planning', 'Beauty', 'Videography', 'Officiants', 'Attire', 'Transport',
];

const coupleFeatures = [
    { icon: Users, title: 'Guests & RSVP', body: 'One list, every reply — with a public RSVP page your guests will love.' },
    { icon: LayoutGrid, title: 'Seating studio', body: 'Drag-and-drop floor plans, to scale, down to the last chair.' },
    { icon: Wallet, title: 'Budget, balanced', body: 'Estimates, actuals and payments — always in plain sight.' },
    { icon: PenLine, title: 'A website that wows', body: 'Templates, galleries, video and a countdown — published in minutes.' },
];

// The complete celebration suite — the eight things that make VowNook end-to-end.
const suite = [
    { icon: Gift, title: 'Gift registry', body: 'Cash, honeymoon and item registries — guests give in a tap, no fees held.' },
    { icon: CalendarDays, title: 'Multi-day schedule', body: 'Rehearsal, welcome party, ceremony, brunch — each with its own RSVP.' },
    { icon: Plane, title: 'Travel & stays', body: 'Hotel room blocks, directions and shuttle notes for out-of-town guests.' },
    { icon: MailCheck, title: 'Save-the-dates', body: 'Send beautiful cards and see exactly who opened them.' },
    { icon: Megaphone, title: 'Message your guests', body: 'A venue change or a thank-you — broadcast to the right people instantly.' },
    { icon: Printer, title: 'Printables', body: 'Escort cards, place cards, menus and table numbers — print-ready in a click.' },
    { icon: HeartHandshake, title: 'Thank-you tracking', body: 'Every gift in one list, ticked off as the notes go out.' },
    { icon: Globe, title: 'name.vownook.com', body: 'A free, personal web address for your wedding site — shareable in seconds.' },
];

const mosaic = [
    { src: IMG.registry, alt: 'Wedding gifts wrapped in cream paper and gold ribbon', label: 'Registry' },
    { src: IMG.stationery, alt: 'Luxury wedding save-the-date stationery flatlay', label: 'Save-the-dates' },
    { src: IMG.travel, alt: 'A vintage suitcase and map for the honeymoon', label: 'Travel & stays' },
    { src: IMG.thankyou, alt: 'Handwritten thank-you cards with a gold pen', label: 'Thank-yous' },
];

const vendorStats = [
    { value: '$0', label: 'to list your business — no contract, no monthly fee' },
    { value: '100%', label: 'of your leads, in one inbox' },
    { value: '8%', label: 'only when a booking is won — capped, lower on big tickets' },
];

const coupleSteps = [
    { n: '01', title: 'Browse & shortlist', body: 'Explore published vendors by category, region and budget — no account needed to look.' },
    { n: '02', title: 'Request quotes', body: 'Send one inquiry with your date and guest count. Compare offers side by side when they arrive.' },
    { n: '03', title: 'Book & plan together', body: 'Accept an offer and the vendor lands in your planning workspace — timeline, budget and all.' },
];

const vendorSteps = [
    { n: '01', title: 'Build your profile', body: 'Portfolio, packages, pricing and availability — a public page that sells while you sleep.' },
    { n: '02', title: 'Answer real inquiries', body: 'Couples come to you with a date, headcount and budget. Reply with a structured offer.' },
    { n: '03', title: 'Win the booking', body: 'When a couple accepts, the booking is confirmed and tracked — earnings dashboard included.' },
];

const tiers = [
    {
        name: 'Couples — Essential',
        price: '$0',
        cadence: 'forever',
        features: ['Guest list & RSVP', 'Budget tracker', 'Vendor marketplace & quotes', 'Checklist & timeline'],
        cta: 'Begin free',
        href: '/register',
        featured: false,
    },
    {
        name: 'Couples — The Atelier',
        price: '$99',
        cadence: 'per wedding',
        features: ['Everything in Essential', 'Wedding website + name.vownook.com', 'Registry, travel & save-the-dates', 'Floor plan, printables & more'],
        cta: 'Choose Atelier',
        href: '/register',
        featured: true,
    },
    {
        name: 'Vendors',
        price: '8%',
        cadence: 'only when booked',
        features: ['Free listing & portfolio page', 'Unlimited quotes & messaging', 'No monthly fee, no contract', 'Lower rate & cap on large bookings'],
        cta: 'List your business',
        href: '/register?type=vendor',
        featured: false,
    },
    {
        name: 'Planners — HQ',
        price: '$499',
        cadence: 'per year',
        features: ['Unlimited client weddings', 'Portfolio dashboard & week view', 'Reusable checklist & budget templates', 'Marketplace sourcing for every client'],
        cta: 'Run your studio',
        href: '/register?type=planner',
        featured: false,
    },
];

const faqs = [
    {
        q: 'Is VowNook free to use?',
        a: 'Yes. Couples plan for free — guest list and RSVP, budget tracker, checklist, timeline and the full vendor marketplace cost nothing. The Atelier upgrade adds the wedding website, your free name.vownook.com address, registry, travel, save-the-dates, printables and the seating studio.',
    },
    {
        q: 'Can I build a wedding website for free?',
        a: 'You can design and preview a beautiful wedding website on any plan, with templates, galleries, video, a countdown and a per-event RSVP. Publishing it live — and claiming a free personal address like yourname.vownook.com — is part of the Atelier plan.',
    },
    {
        q: 'How does the vendor marketplace work?',
        a: 'Browse trusted wedding vendors by category, region and budget, send one inquiry with your date and guest count, then compare real quotes side by side. Accept an offer and the vendor appears right inside your planning workspace.',
    },
    {
        q: 'Which areas do you cover?',
        a: 'VowNook is built for couples and wedding vendors across Ontario — Toronto, Ottawa, Hamilton, Kitchener-Waterloo, London, Niagara and beyond — with planning tools that work anywhere.',
    },
    {
        q: 'What does it cost vendors to join?',
        a: 'Listing your business is free — no monthly fee and no contract. You only pay a small commission when a booking is actually won, capped and reduced on larger bookings.',
    },
    {
        q: 'Does it include a gift registry and thank-you tracking?',
        a: 'Yes. Set up cash, honeymoon and item registries with no fees held, and every contribution flows into a thank-you list you can tick off as the notes go out.',
    },
];

// Real screenshots of the product (captured from a seeded demo wedding) for
// the homepage showcase — the full set lives on /features.
const SHOWCASE = [
    {
        label: 'Dashboard',
        img: '/images/tour/dashboard.webp',
        alt: 'The VowNook dashboard with countdown, stats and a needs-attention list',
        caption: 'Mission control: countdown, RSVP rate, budget health and everything needing attention — on one calm screen.',
    },
    {
        label: 'Seating studio',
        img: '/images/tour/seating.webp',
        alt: 'The VowNook seating studio with a floor plan and live stats',
        caption: 'Size your real room, place tables and the dance floor, and seat guests chair by chair with live capacity stats.',
    },
    {
        label: 'Wedding website',
        img: '/images/tour/wedding-site.webp',
        alt: 'A published VowNook wedding website with a live countdown',
        caption: 'The page your guests actually visit — tap-to-open invitation, your music, your story, and RSVP built in.',
    },
    {
        label: 'Budget',
        img: '/images/tour/budget.webp',
        alt: 'The VowNook budget tracker with estimated versus actual spending',
        caption: 'Estimated vs. actual per vendor, deposits and what is still owed — the drift never surprises you.',
    },
    {
        label: 'Stationery',
        img: '/images/tour/shop-personalizer.webp',
        alt: 'The VowNook stationery personaliser with a live invitation preview',
        caption: 'Type your names and watch your invitation suite draw itself — ten designs, print-ready, from $14.',
    },
];

function Wordmark({ className = '' }: { className?: string }) {
    return (
        <Link href="/" className={`flex items-center gap-2.5 ${className}`} aria-label="VowNook home">
            <img src="/images/brand/logo-mark.svg" alt="" className="size-9 rounded-md border border-[#0f1c17]/10" />
            <span className={`${fraunces} text-[22px] font-medium tracking-tight`}>VowNook</span>
        </Link>
    );
}

/**
 * The page's structural signature.
 *
 * A left rail carries the chapter number and a short italic label while the
 * content sits offset in the right column. Every section uses it, so the page
 * reads as one composed system instead of a stack of centred blocks — which was
 * the tell that made the old layout feel machine-made. Nothing here is centred
 * on purpose: the consistent left edge is what makes it feel typeset.
 */
function Rail({
    n,
    label,
    children,
    className = '',
    tone = 'light',
}: {
    n: string;
    label: ReactNode;
    children: ReactNode;
    className?: string;
    tone?: 'light' | 'dark';
}) {
    const muted = tone === 'dark' ? 'text-white/55' : 'text-[#4b5850]';

    return (
        <div
            className={`mx-auto grid max-w-[1480px] gap-8 lg:grid-cols-[7rem_minmax(0,1fr)] lg:gap-12 ${className}`}
        >
            <div className="hidden lg:block">
                <div className="sticky top-32">
                    <span
                        aria-hidden
                        className={`block h-px w-12 ${tone === 'dark' ? 'bg-[#7fb79e]' : 'bg-[#c4502e]'}`}
                    />
                    <p className={`eyebrow mt-4 ${muted}`}>{n}</p>
                    <p className={`${fraunces} mt-2 text-[15px] leading-snug italic ${muted}`}>
                        {label}
                    </p>
                </div>
            </div>
            <div className="min-w-0">{children}</div>
        </div>
    );
}

export default function Welcome({ budgetModel }: { budgetModel: BudgetModel }) {
    const { auth } = usePage().props;
    const authed = !!auth?.user;
    const [menuOpen, setMenuOpen] = useState(false);
    const [showcaseIdx, setShowcaseIdx] = useState(0);

    return (
        <div className="min-h-screen bg-[#f1f0ea] font-['Instrument_Sans'] text-[#0f1c17] antialiased selection:bg-[#7fb79e]/40">
            {/* Title/description/canonical/OG are server-rendered in the blade head. */}
            <Head />

            {/* Marquee keyframes */}
            <style>{`
                @keyframes wfa-marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
                .wfa-marquee { animation: wfa-marquee 36s linear infinite; }
                .wfa-marquee:hover { animation-play-state: paused; }
            `}</style>

            {/* ── Header ─────────────────────────────────────────────────── */}
            <header className="fixed inset-x-0 top-0 z-50 border-b border-[#0f1c17]/8 bg-[#f1f0ea]/85 backdrop-blur-md">
                <nav className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-3.5 md:px-12">
                    <Wordmark />
                    <div className="hidden items-center gap-9 md:flex">
                        <a href="#couples" className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1f5142]">
                            For couples
                        </a>
                        <a href="#suite" className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1f5142]">
                            The suite
                        </a>
                        <Link href="/marketplace" className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1f5142]">
                            Marketplace
                        </Link>
                        <a href="/shop" className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1f5142]">
                            Shop
                        </a>
                        <Link href="/features" className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1f5142]">
                            Features
                        </Link>
                        <a href="#vendors" className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1f5142]">
                            For vendors
                        </a>
                        <a href="#pricing" className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1f5142]">
                            Pricing
                        </a>
                    </div>
                    <div className="flex items-center gap-3">
                        {authed ? (
                            <Link
                                href={dashboard()}
                                className="cta-press px-5 py-2.5 text-[11px] font-medium tracking-[0.18em] uppercase md:px-6"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link href={login()} className="hidden text-[13px] text-[#4b5850] hover:text-[#1f5142] sm:block">
                                    Sign in
                                </Link>
                                <Link
                                    href={register()}
                                    className="cta-press px-5 py-2.5 text-[11px] font-medium tracking-[0.18em] uppercase md:px-6"
                                >
                                    Get started
                                </Link>
                            </>
                        )}
                        <button
                            type="button"
                            onClick={() => setMenuOpen((o) => !o)}
                            aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                            aria-expanded={menuOpen}
                            className="flex size-10 items-center justify-center text-[#0f1c17] md:hidden"
                        >
                            {menuOpen ? <X className="size-6" /> : <Menu className="size-6" />}
                        </button>
                    </div>
                </nav>

                {/* Mobile menu */}
                {menuOpen && (
                    <div className="flex flex-col border-t border-[#0f1c17]/8 bg-[#f1f0ea] px-5 py-2 md:hidden">
                        <a href="#couples" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">For couples</a>
                        <a href="#suite" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">The suite</a>
                        <Link href="/marketplace" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">Marketplace</Link>
                        <a href="/shop" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">Shop</a>
                        <Link href="/features" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">Features</Link>
                        <a href="#vendors" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">For vendors</a>
                        <a href="#pricing" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">Pricing</a>
                        {!authed && (
                            <Link href={login()} onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#4b5850]">Sign in</Link>
                        )}
                    </div>
                )}
            </header>

            {/*
              ── Hero: an instrument, not a photograph ──────────────────────
              Every competitor here opens on a stock image and a soft line of
              copy — Zola's homepage and this one used to be structurally the
              same page. The visitor now does something in the first viewport
              instead of scrolling past a poem to find out what we are, and the
              thing they do is the one asset no competitor has: real Ontario
              cost data. Composition is deliberately offset rather than centred.
            */}
            <section className="relative overflow-hidden pt-28 pb-14 md:pt-36 md:pb-20">
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-x-0 top-0 h-[460px] bg-gradient-to-b from-[#e7e9e2] via-[#e7e9e2]/60 to-transparent"
                />
                <div className="relative mx-auto grid max-w-[1480px] gap-8 px-5 md:px-12 lg:grid-cols-[7rem_minmax(0,1fr)] lg:gap-12">
                    {/* Left rail — the editorial marker that breaks the centred grid. */}
                    <div className="hidden lg:block">
                        <div className="sticky top-32">
                            <span className="rule-draw block h-px w-12 bg-[#c4502e]" />
                            <p className="eyebrow mt-4 text-[#4b5850]">N°01</p>
                            <p className={`${fraunces} mt-2 text-[15px] leading-snug text-[#4b5850] italic`}>
                                The budget
                                <br />
                                instrument
                            </p>
                        </div>
                    </div>

                    <div className="rise">
                        <BudgetInstrument
                            model={budgetModel}
                            ctaHref={authed ? dashboard() : register()}
                        />

                        <div className="mt-6 flex flex-wrap items-center gap-x-8 gap-y-3 text-[12px] text-[#4b5850]">
                            <span className="tabular">
                                <span className="text-[#0f1c17]">42</span> Ontario cities priced
                            </span>
                            <span className="tabular">
                                <span className="text-[#0f1c17]">12</span> vendor categories
                            </span>
                            <span>
                                Reviews tied to real bookings — <span className="text-[#0f1c17]">no pay-to-play</span>
                            </span>
                            <Link href="/marketplace" className="link-draw ml-auto text-[#1b4638]">
                                Or browse the marketplace →
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* ── Category marquee ───────────────────────────────────────── */}
            <div className="overflow-hidden border-y border-[#0f1c17]/10 bg-[#e7e9e2] py-4">
                <div className="wfa-marquee flex w-max items-center">
                    {[0, 1].map((dup) => (
                        <div key={dup} className="flex items-center" aria-hidden={dup === 1}>
                            {CATEGORIES.map((c) => (
                                <span key={`${dup}-${c}`} className="flex items-center">
                                    <span className={`${fraunces} px-6 text-sm tracking-[0.25em] text-[#4b5850] uppercase`}>{c}</span>
                                    <span className="text-[#1f5142]">✦</span>
                                </span>
                            ))}
                        </div>
                    ))}
                </div>
            </div>

            {/* ── 01 · For couples ───────────────────────────────────────── */}
            <section id="couples" className="overflow-hidden px-5 py-24 md:px-12 md:py-32">
                <Rail
                    n="N°02"
                    label={
                        <>
                            For
                            <br />
                            couples
                        </>
                    }
                >
                    <Reveal>
                        <h2 className={`${fraunces} max-w-2xl text-4xl leading-[1.05] font-light sm:text-5xl md:text-[3.4rem]`}>
                            Every detail, <em className="text-[#1f5142]">composed.</em>
                        </h2>
                        <p className="mt-5 max-w-xl text-[15px] leading-relaxed text-[#4b5850]">
                            Guest lists, seating, budget, timeline, a wedding website your guests will
                            actually visit — one calm studio instead of eleven spreadsheets.
                        </p>
                    </Reveal>

                    {/* Text column leads; the photograph sits offset to the right
                        as supporting evidence rather than as the headline act. */}
                    <div className="mt-12 grid gap-12 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                        <div>
                            <Stagger className="grid gap-x-10 gap-y-8 border-t border-[#0f1c17]/10 pt-8 sm:grid-cols-2">
                                {coupleFeatures.map((f) => (
                                    <StaggerItem key={f.title} className="group">
                                        <f.icon className="mb-3 size-5 text-[#1f5142]" />
                                        <h3 className="text-sm font-bold tracking-wide">{f.title}</h3>
                                        <p className="mt-1.5 text-sm leading-relaxed text-[#4b5850]">{f.body}</p>
                                    </StaggerItem>
                                ))}
                            </Stagger>

                            <Reveal delay={0.2} className="mt-10 flex flex-wrap items-center gap-6">
                                <Link
                                    href={authed ? dashboard() : register()}
                                    className="cta-press px-8 py-3.5 text-[11px] font-semibold tracking-[0.2em] uppercase"
                                >
                                    Start planning
                                </Link>
                                <a
                                    href={DEMO}
                                    className="group flex items-center gap-1.5 text-sm text-[#4b5850] underline-offset-4 hover:text-[#1f5142] hover:underline"
                                >
                                    See a live wedding site
                                    <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                </a>
                            </Reveal>
                        </div>

                        <Reveal className="relative hidden lg:block">
                            <img
                                src={IMG.tablescape}
                                alt="A candlelit wedding reception tablescape an hour before guests arrive"
                                className="aspect-[3/4] w-full object-cover"
                                loading="lazy"
                            />
                            <p className={`${fraunces} mt-3 border-t border-[#0f1c17]/12 pt-2 text-xs text-[#4b5850] italic`}>
                                The long table, an hour before everyone arrived.
                            </p>
                        </Reveal>
                    </div>
                </Rail>
            </section>

            {/* ── The complete suite (NEW) ───────────────────────────────── */}
            <section id="suite" className="overflow-hidden border-t border-[#0f1c17]/10 bg-[#eaede5] px-5 py-24 md:px-12 md:py-32">
                <Rail
                    n="N°03"
                    label={
                        <>
                            The whole
                            <br />
                            celebration
                        </>
                    }
                >
                    <Reveal className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-end">
                        <h2 className={`${fraunces} max-w-2xl text-4xl leading-[1.05] font-light sm:text-5xl md:text-[3.4rem]`}>
                            From the first “yes” to the <em className="text-[#1f5142]">last thank-you note.</em>
                        </h2>
                        <p className="text-[15px] leading-relaxed text-[#4b5850]">
                            The whole celebration, not just the ceremony — registry, multi-day schedule with
                            per-event RSVPs, room blocks, save-the-dates with open-tracking, guest messaging,
                            print-ready stationery, thank-you tracking and a free{' '}
                            <strong className="font-semibold text-[#0f1c17]">name.vownook.com</strong> website.
                        </p>
                    </Reveal>

                    {/* Editorial band */}
                    <Reveal className="mt-12">
                        <div className="relative overflow-hidden">
                            <img
                                src={IMG.reception}
                                alt="Guests toasting at a golden-hour outdoor wedding reception"
                                className="h-[280px] w-full object-cover md:h-[420px]"
                                loading="lazy"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-[#0f1c17]/70 via-transparent to-transparent" />
                            <p className={`${fraunces} absolute bottom-6 left-6 max-w-md text-2xl leading-tight text-white md:bottom-10 md:left-10 md:text-4xl`}>
                                One studio for the whole weekend, not just the big day.
                            </p>
                        </div>
                    </Reveal>

                    {/* Eight feature cards */}
                    <Stagger className="mt-14 grid gap-px overflow-hidden rounded-sm border border-[#0f1c17]/10 bg-[#0f1c17]/10 sm:grid-cols-2 lg:grid-cols-4">
                        {suite.map((f) => (
                            <StaggerItem
                                key={f.title}
                                className="group bg-[#f1f0ea] p-7 transition-colors duration-300 hover:bg-white"
                            >
                                <div className="flex size-11 items-center justify-center rounded-full bg-[#e2e8e2] text-[#1f5142] transition-transform duration-300 group-hover:-translate-y-0.5">
                                    <f.icon className="size-5" />
                                </div>
                                <h3 className={`${fraunces} mt-4 text-xl font-medium`}>{f.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-[#4b5850]">{f.body}</p>
                            </StaggerItem>
                        ))}
                    </Stagger>

                    {/* Editorial mosaic */}
                    <Stagger className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        {mosaic.map((m) => (
                            <StaggerItem key={m.label} className="group relative overflow-hidden rounded-sm">
                                <img
                                    src={m.src}
                                    alt={m.alt}
                                    className="aspect-square w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    loading="lazy"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-[#0f1c17]/60 to-transparent" />
                                <span className="absolute bottom-3 left-3 text-[10px] font-semibold tracking-[0.2em] text-white uppercase">{m.label}</span>
                            </StaggerItem>
                        ))}
                    </Stagger>

                    <Reveal delay={0.15} className="mt-12">
                        <Link
                            href={authed ? dashboard() : register()}
                            className="group inline-flex items-center gap-3 border-b border-[#1f5142] pb-1 text-[11px] font-semibold tracking-[0.2em] text-[#1f5142] uppercase"
                        >
                            Build your wedding suite
                            <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </Reveal>
                </Rail>
            </section>

            {/* ── Product showcase: real screenshots ─────────────────────── */}
            <section className="border-t border-[#0f1c17]/10 px-5 py-24 md:px-12 md:py-36">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mx-auto max-w-3xl text-center">
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">Inside the studio</p>
                        <h2 className={`${fraunces} text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                            Don't imagine it — <em className="text-[#1f5142]">look at it.</em>
                        </h2>
                        <p className="mt-6 text-[15px] leading-relaxed text-[#4b5850]">
                            Every screenshot below is the real product with a real wedding in it. Click
                            through the tools your planning will actually live in.
                        </p>
                    </Reveal>

                    <Reveal className="mt-12">
                        <div className="flex flex-wrap justify-center gap-2.5">
                            {SHOWCASE.map((s, i) => (
                                <button
                                    key={s.label}
                                    type="button"
                                    onClick={() => setShowcaseIdx(i)}
                                    className={`rounded-full border px-4 py-2 text-[12px] tracking-wide transition-colors ${
                                        i === showcaseIdx
                                            ? 'border-[#0f1c17] bg-[#0f1c17] text-[#f1f0ea]'
                                            : 'border-[#0f1c17]/15 bg-white/60 text-[#4b5850] hover:border-[#0f1c17]/40'
                                    }`}
                                >
                                    {s.label}
                                </button>
                            ))}
                        </div>

                        <div className="mx-auto mt-8 max-w-5xl">
                            <div className="overflow-hidden rounded-xl border border-[#0f1c17]/10 bg-white shadow-[0_50px_100px_-45px_rgba(25,22,19,0.5)]">
                                <div className="flex items-center gap-2 border-b border-[#0f1c17]/8 bg-[#eef1eb] px-4 py-2.5">
                                    <span className="size-2.5 rounded-full bg-[#cfd8d0]" />
                                    <span className="size-2.5 rounded-full bg-[#cfd8d0]" />
                                    <span className="size-2.5 rounded-full bg-[#cfd8d0]" />
                                    <span className="mx-auto flex h-5 w-1/2 items-center justify-center rounded bg-white/70 text-[10px] tracking-wide text-[#8c8478]">
                                        vownook.com
                                    </span>
                                </div>
                                <img
                                    key={SHOWCASE[showcaseIdx].img}
                                    src={SHOWCASE[showcaseIdx].img}
                                    alt={SHOWCASE[showcaseIdx].alt}
                                    className="block w-full"
                                    width={1440}
                                    height={900}
                                    loading="lazy"
                                />
                            </div>
                            <p className="mt-5 text-center text-[14px] text-[#4b5850]">
                                {SHOWCASE[showcaseIdx].caption}
                            </p>
                            <p className="mt-6 text-center">
                                <Link
                                    href="/features"
                                    className="inline-flex items-center gap-2 text-[13px] font-semibold tracking-[0.14em] text-[#1f5142] uppercase hover:text-[#0f1c17]"
                                >
                                    Take the full tour — every tool, explained <ArrowRight className="size-3.5" />
                                </Link>
                            </p>
                        </div>
                    </Reveal>
                </div>
            </section>

            {/* ── 02 · The marketplace (dark chapter) ────────────────────── */}
            <section className="relative overflow-hidden bg-[#0f1c17] px-5 py-24 text-[#f1f0ea] md:px-12 md:py-32">
                <Rail
                    n="N°04"
                    tone="dark"
                    label={
                        <>
                            The
                            <br />
                            marketplace
                        </>
                    }
                    className="relative"
                >
                <div className="grid gap-16 md:grid-cols-12">
                    <div className="md:col-span-5">
                        <Reveal>
                            <h2 className={`${fraunces} text-4xl leading-[1.05] font-light sm:text-5xl md:text-[3.4rem]`}>
                                Real vendors. Real quotes.
                                <br />
                                <em className="text-[#7fb79e]">No guesswork.</em>
                            </h2>
                            <p className="mt-5 max-w-md text-[15px] leading-relaxed text-white/70">
                                Every listing is reviewed before it goes live. Send one inquiry with your date
                                and guest count, then weigh the offers side by side — terms, deposits and all.
                            </p>
                        </Reveal>

                        <Stagger className="mt-10 space-y-6">
                            {[
                                { icon: Inbox, text: 'One inquiry — your date, headcount and budget, sent in a minute.' },
                                { icon: GitCompareArrows, text: 'Offers compared side by side, grouped by category.' },
                                { icon: MailOpen, text: 'Accept, and the vendor appears in your planning workspace automatically.' },
                            ].map((item) => (
                                <StaggerItem key={item.text} className="flex items-start gap-4">
                                    <item.icon className="mt-0.5 size-5 shrink-0 text-[#7fb79e]" />
                                    <p className="text-sm leading-relaxed text-white/80">{item.text}</p>
                                </StaggerItem>
                            ))}
                        </Stagger>

                        <Reveal delay={0.2} className="mt-10">
                            <Link
                                href="/marketplace"
                                className="inline-flex items-center gap-3 bg-[#7fb79e] px-8 py-3.5 text-[11px] font-semibold tracking-[0.2em] text-[#0f1c17] uppercase transition-colors hover:bg-[#f1f0ea]"
                            >
                                Browse the marketplace
                                <ArrowRight className="size-4" />
                            </Link>
                        </Reveal>
                    </div>

                    {/* Staggered editorial image pair */}
                    <div className="relative min-h-[420px] md:col-span-6 md:col-start-7">
                        <Reveal className="absolute top-0 left-0 w-[62%]">
                            <div className="bg-[#f1f0ea] p-2.5">
                                <img src={IMG.florist} alt="A florist arranging a cream bridal bouquet" className="aspect-[3/4] w-full object-cover" loading="lazy" />
                            </div>
                            <p className="mt-2 text-[10px] tracking-[0.25em] text-white/50 uppercase">Florals · The bouquet bench</p>
                        </Reveal>
                        <Reveal delay={0.18} className="absolute right-0 bottom-0 w-[52%]">
                            <div className="bg-[#f1f0ea] p-2.5">
                                <img src={IMG.photographer} alt="A wedding photographer at work in a courtyard at golden hour" className="aspect-[3/4] w-full object-cover" loading="lazy" />
                            </div>
                            <p className="mt-2 text-right text-[10px] tracking-[0.25em] text-white/50 uppercase">Photography · Golden hour</p>
                        </Reveal>
                    </div>
                </div>
                </Rail>
            </section>

            {/* ── 03 · For vendors ───────────────────────────────────────── */}
            <section id="vendors" className="relative overflow-hidden px-5 py-24 md:px-12 md:py-32">
                <Rail
                    n="N°05"
                    label={
                        <>
                            For
                            <br />
                            vendors
                        </>
                    }
                >
                    <Reveal className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-end">
                        <h2 className={`${fraunces} max-w-2xl text-4xl leading-[1.05] font-light sm:text-5xl md:text-[3.4rem]`}>
                            Your craft, in front of couples <em className="text-[#1f5142]">ready to book.</em>
                        </h2>
                        <p className="text-[15px] leading-relaxed text-[#4b5850]">
                            A portfolio page with packages and availability, structured inquiries instead of
                            cold DMs, and offers that win — from one business dashboard.
                        </p>
                    </Reveal>

                    {/* Figures set left and large, like a printed rate card. */}
                    <Stagger className="mt-12 grid gap-px overflow-hidden border border-[#0f1c17]/10 bg-[#0f1c17]/10 sm:grid-cols-3">
                        {vendorStats.map((s) => (
                            <StaggerItem key={s.label} className="bg-[#f1f0ea] p-8 sm:p-10">
                                <p className={`${fraunces} tabular text-5xl font-light text-[#1f5142] sm:text-6xl`}>
                                    {s.value}
                                </p>
                                <p className="mt-3 text-sm leading-relaxed text-[#4b5850]">{s.label}</p>
                            </StaggerItem>
                        ))}
                    </Stagger>

                    <Reveal delay={0.15} className="mt-10 flex flex-wrap items-center gap-6">
                        <Link
                            href="/register?type=vendor"
                            className="cta-press px-8 py-3.5 text-[11px] font-semibold tracking-[0.2em] uppercase"
                        >
                            List your business — free
                        </Link>
                        <p className="text-sm text-[#4b5850]">Reviewed and published, usually within a day.</p>
                    </Reveal>
                </Rail>
            </section>

            {/* ── How it works ───────────────────────────────────────────── */}
            <section className="border-y border-[#0f1c17]/10 bg-[#e7e9e2] px-5 py-24 md:px-12 md:py-32">
                <Rail
                    n="N°06"
                    label={
                        <>
                            How it
                            <br />
                            works
                        </>
                    }
                >
                    <Reveal className="mb-14">
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            How the room <em className="text-[#1f5142]">comes together</em>
                        </h2>
                    </Reveal>

                    <div className="grid gap-16 md:grid-cols-2 md:gap-24">
                        <div>
                            <p className="mb-8 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">If you're planning</p>
                            <Stagger className="space-y-10">
                                {coupleSteps.map((s) => (
                                    <StaggerItem key={s.n} className="flex gap-6">
                                        <span className={`${fraunces} text-3xl font-light text-[#1f5142]/50`}>{s.n}</span>
                                        <div>
                                            <h3 className="text-sm font-bold tracking-wide">{s.title}</h3>
                                            <p className="mt-1.5 text-sm leading-relaxed text-[#4b5850]">{s.body}</p>
                                        </div>
                                    </StaggerItem>
                                ))}
                            </Stagger>
                        </div>
                        <div>
                            <p className="mb-8 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">If you're a vendor</p>
                            <Stagger className="space-y-10">
                                {vendorSteps.map((s) => (
                                    <StaggerItem key={s.n} className="flex gap-6">
                                        <span className={`${fraunces} text-3xl font-light text-[#1f5142]/50`}>{s.n}</span>
                                        <div>
                                            <h3 className="text-sm font-bold tracking-wide">{s.title}</h3>
                                            <p className="mt-1.5 text-sm leading-relaxed text-[#4b5850]">{s.body}</p>
                                        </div>
                                    </StaggerItem>
                                ))}
                            </Stagger>
                        </div>
                    </div>
                </Rail>
            </section>

            {/* ── Pricing ────────────────────────────────────────────────── */}
            <section id="pricing" className="px-5 py-24 md:px-12 md:py-32">
                <Rail
                    n="N°07"
                    label={
                        <>
                            What it
                            <br />
                            costs
                        </>
                    }
                >
                    <Reveal className="mb-14">
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            Choose your <em className="text-[#1f5142]">level of ceremony.</em>
                        </h2>
                    </Reveal>

                    <div className="grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-4">
                        {tiers.map((tier) => (
                            <div
                                key={tier.name}
                                className={`flex flex-col justify-between p-10 md:p-12 ${
                                    tier.featured
                                        ? 'relative bg-[#0f1c17] text-[#f1f0ea] shadow-[0_30px_60px_-25px_rgba(25,22,19,0.6)] md:-translate-y-5'
                                        : 'border border-[#0f1c17]/12 bg-[#f1f0ea]'
                                }`}
                            >
                                {tier.featured && (
                                    <div className="absolute top-0 right-0 bg-[#1f5142] px-5 py-2 text-[10px] tracking-[0.25em] text-white uppercase">
                                        Recommended
                                    </div>
                                )}
                                <div>
                                    <h3 className={`mb-2 text-xs font-bold tracking-[0.25em] uppercase ${tier.featured ? 'text-[#7fb79e]' : 'text-[#0f1c17]'}`}>
                                        {tier.name}
                                    </h3>
                                    <div className="mb-8 flex items-baseline gap-2">
                                        <span className={`${fraunces} text-5xl font-light`}>{tier.price}</span>
                                        <span className={`text-sm ${tier.featured ? 'text-white/60' : 'text-[#4b5850]'}`}>{tier.cadence}</span>
                                    </div>
                                    <ul className="mb-12 space-y-4">
                                        {tier.features.map((f) => (
                                            <li key={f} className="flex items-center gap-3 text-sm">
                                                <Check className={`size-4 shrink-0 ${tier.featured ? 'text-[#7fb79e]' : 'text-[#1f5142]'}`} />
                                                <span className={tier.featured ? 'text-white/85' : 'text-[#4b5850]'}>{f}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <Link
                                    href={tier.href}
                                    className={`py-4 text-center text-[11px] font-semibold tracking-[0.2em] uppercase transition-colors ${
                                        tier.featured
                                            ? 'bg-[#1f5142] text-white hover:bg-[#7fb79e] hover:text-[#0f1c17]'
                                            : 'border border-[#0f1c17] text-[#0f1c17] hover:bg-[#0f1c17] hover:text-[#f1f0ea]'
                                    }`}
                                >
                                    {tier.cta}
                                </Link>
                            </div>
                        ))}
                    </div>
                </Rail>
            </section>

            {/* ── FAQ (organic + AI search) ──────────────────────────────── */}
            <section className="border-t border-[#0f1c17]/10 bg-[#eaede5] px-5 py-24 md:px-12 md:py-32">
                <Rail
                    n="N°08"
                    label={
                        <>
                            Good to
                            <br />
                            know
                        </>
                    }
                >
                <div className="grid gap-14 md:grid-cols-12">
                    <Reveal className="md:col-span-4">
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            Questions, <em className="text-[#1f5142]">answered.</em>
                        </h2>
                        <p className="mt-5 text-sm leading-relaxed text-[#4b5850]">
                            Everything couples and vendors ask before they begin. Still curious?{' '}
                            <Link href="/contact" className="link-draw text-[#1f5142]">Talk to us.</Link>
                        </p>
                    </Reveal>

                    <div className="md:col-span-7 md:col-start-6">
                        <Stagger className="divide-y divide-[#0f1c17]/10 border-y border-[#0f1c17]/10">
                            {faqs.map((item) => (
                                <StaggerItem key={item.q}>
                                    <details className="group">
                                        <summary className="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-left">
                                            <h3 className={`${fraunces} text-lg font-medium`}>{item.q}</h3>
                                            <span className="grid size-7 shrink-0 place-items-center rounded-full border border-[#1f5142]/40 text-[#1f5142] transition-transform duration-300 group-open:rotate-45">+</span>
                                        </summary>
                                        <p className="pb-5 text-sm leading-relaxed text-[#4b5850]">{item.a}</p>
                                    </details>
                                </StaggerItem>
                            ))}
                        </Stagger>
                    </div>
                </div>
                </Rail>
            </section>

            {/* ── Final CTA ──────────────────────────────────────────────── */}
            <section className="bg-[#0f1c17] px-5 py-28 text-center text-[#f1f0ea] md:py-40">
                <Reveal className="mx-auto max-w-3xl">
                    <p className="mb-6 text-[11px] tracking-[0.35em] text-[#7fb79e] uppercase">The invitation stands</p>
                    <h2 className={`${fraunces} text-4xl leading-[1.1] font-light sm:text-6xl`}>
                        The best days are <em className="text-[#7fb79e]">planned together.</em>
                    </h2>
                    <div className="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Link
                            href={authed ? dashboard() : register()}
                            className="cta-press group inline-flex items-center gap-3 px-12 py-5 text-[11px] font-semibold tracking-[0.25em] uppercase"
                        >
                            <span className="relative z-10 flex items-center gap-3">
                                {authed ? 'Open your studio' : 'Create your account'}
                                <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                            </span>
                        </Link>
                        <Link
                            href="/register?type=vendor"
                            className="inline-flex items-center gap-3 border border-white/30 px-12 py-5 text-[11px] font-semibold tracking-[0.25em] text-white uppercase transition-colors hover:bg-white hover:text-[#0f1c17]"
                        >
                            I'm a vendor
                        </Link>
                    </div>
                </Reveal>
            </section>

            {/* ── Footer ─────────────────────────────────────────────────── */}
            <footer className="border-t border-[#0f1c17]/10 bg-[#f1f0ea] py-12">
                <div className="mx-auto flex max-w-[1480px] flex-col items-center justify-between gap-6 px-5 md:flex-row md:px-12">
                    <Wordmark />
                    <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[13px] text-[#4b5850]">
                        <Link href="/marketplace" className="hover:text-[#1f5142]">Marketplace</Link>
                        <Link href="/features" className="hover:text-[#1f5142]">Features</Link>
                        <Link href="/how-it-works" className="hover:text-[#1f5142]">How it works</Link>
                        <Link href="/pricing" className="hover:text-[#1f5142]">Pricing</Link>
                        <Link href="/blog" className="hover:text-[#1f5142]">Journal</Link>
                        <a href={DEMO} className="hover:text-[#1f5142]">Live demo</a>
                        <Link href="/terms" className="hover:text-[#1f5142]">Terms</Link>
                        <Link href="/privacy" className="hover:text-[#1f5142]">Privacy</Link>
                        <Link href="/contact" className="hover:text-[#1f5142]">Contact</Link>
                        <Link href={login()} className="hover:text-[#1f5142]">Sign in</Link>
                    </div>
                    <p className="text-[11px] tracking-[0.15em] text-[#4b5850]/70 uppercase">© {new Date().getFullYear()} VowNook</p>
                </div>
            </footer>
        </div>
    );
}

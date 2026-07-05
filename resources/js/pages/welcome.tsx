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
import { useState } from 'react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { dashboard, login, register } from '@/routes';

const fraunces = "font-['Fraunces']";
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
            <img src="/images/brand/logo-mark.svg" alt="" className="size-9 rounded-md border border-[#191613]/10" />
            <span className={`${fraunces} text-[22px] font-medium tracking-tight`}>VowNook</span>
        </Link>
    );
}

export default function Welcome() {
    const { auth } = usePage().props;
    const authed = !!auth?.user;
    const [menuOpen, setMenuOpen] = useState(false);
    const [showcaseIdx, setShowcaseIdx] = useState(0);

    return (
        <div className="min-h-screen bg-[#faf6ef] font-['DM_Sans'] text-[#191613] antialiased selection:bg-[#e9c176]/40">
            {/* Title/description/canonical/OG are server-rendered in the blade head. */}
            <Head />

            {/* Marquee keyframes */}
            <style>{`
                @keyframes wfa-marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
                .wfa-marquee { animation: wfa-marquee 36s linear infinite; }
                .wfa-marquee:hover { animation-play-state: paused; }
            `}</style>

            {/* ── Header ─────────────────────────────────────────────────── */}
            <header className="fixed inset-x-0 top-0 z-50 border-b border-[#191613]/8 bg-[#faf6ef]/85 backdrop-blur-md">
                <nav className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-3.5 md:px-12">
                    <Wordmark />
                    <div className="hidden items-center gap-9 md:flex">
                        <a href="#couples" className="text-[13px] tracking-wide text-[#52493d] transition-colors hover:text-[#8a651c]">
                            For couples
                        </a>
                        <a href="#suite" className="text-[13px] tracking-wide text-[#52493d] transition-colors hover:text-[#8a651c]">
                            The suite
                        </a>
                        <Link href="/marketplace" className="text-[13px] tracking-wide text-[#52493d] transition-colors hover:text-[#8a651c]">
                            Marketplace
                        </Link>
                        <a href="/shop" className="text-[13px] tracking-wide text-[#52493d] transition-colors hover:text-[#8a651c]">
                            Shop
                        </a>
                        <Link href="/features" className="text-[13px] tracking-wide text-[#52493d] transition-colors hover:text-[#8a651c]">
                            Features
                        </Link>
                        <a href="#vendors" className="text-[13px] tracking-wide text-[#52493d] transition-colors hover:text-[#8a651c]">
                            For vendors
                        </a>
                        <a href="#pricing" className="text-[13px] tracking-wide text-[#52493d] transition-colors hover:text-[#8a651c]">
                            Pricing
                        </a>
                    </div>
                    <div className="flex items-center gap-3">
                        {authed ? (
                            <Link
                                href={dashboard()}
                                className="bg-[#191613] px-5 py-2.5 text-[11px] font-medium tracking-[0.18em] text-[#faf6ef] uppercase transition-colors hover:bg-[#8a651c] md:px-6"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link href={login()} className="hidden text-[13px] text-[#52493d] hover:text-[#8a651c] sm:block">
                                    Sign in
                                </Link>
                                <Link
                                    href={register()}
                                    className="bg-[#191613] px-5 py-2.5 text-[11px] font-medium tracking-[0.18em] text-[#faf6ef] uppercase transition-colors hover:bg-[#8a651c] md:px-6"
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
                            className="flex size-10 items-center justify-center text-[#191613] md:hidden"
                        >
                            {menuOpen ? <X className="size-6" /> : <Menu className="size-6" />}
                        </button>
                    </div>
                </nav>

                {/* Mobile menu */}
                {menuOpen && (
                    <div className="flex flex-col border-t border-[#191613]/8 bg-[#faf6ef] px-5 py-2 md:hidden">
                        <a href="#couples" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">For couples</a>
                        <a href="#suite" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">The suite</a>
                        <Link href="/marketplace" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">Marketplace</Link>
                        <a href="/shop" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">Shop</a>
                        <Link href="/features" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">Features</Link>
                        <a href="#vendors" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">For vendors</a>
                        <a href="#pricing" onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">Pricing</a>
                        {!authed && (
                            <Link href={login()} onClick={() => setMenuOpen(false)} className="py-2.5 text-sm tracking-wide text-[#52493d]">Sign in</Link>
                        )}
                    </div>
                )}
            </header>

            {/* ── Hero ───────────────────────────────────────────────────── */}
            <section className="relative flex min-h-[100svh] flex-col justify-end overflow-hidden">
                <motion.img
                    src={IMG.hero}
                    alt="A couple laughing mid-dance at a golden-hour wedding reception"
                    className="absolute inset-0 size-full object-cover"
                    initial={{ scale: 1.12 }}
                    animate={{ scale: 1 }}
                    transition={{ duration: 16, ease: 'easeOut' }}
                    fetchPriority="high"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#191613]/85 via-[#191613]/25 to-[#191613]/30" />

                {/* Editorial issue stamp */}
                <motion.div
                    className="absolute top-28 right-6 hidden rotate-90 items-center gap-3 text-[10px] tracking-[0.4em] text-white/60 uppercase md:flex"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: 1.2, duration: 1 }}
                >
                    <span className="h-px w-10 bg-white/40" />
                    The Wedding Suite — N°01
                </motion.div>

                <div className="relative z-10 mx-auto w-full max-w-[1480px] px-5 pb-16 md:px-12 md:pb-20">
                    <motion.p
                        className="mb-5 text-[11px] tracking-[0.35em] text-[#e9c176] uppercase"
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.9, delay: 0.2 }}
                    >
                        Planning studio · Vendor marketplace · Wedding websites
                    </motion.p>
                    <motion.h1
                        className={`${fraunces} max-w-5xl text-[clamp(2.4rem,11vw,6.5rem)] leading-[0.95] font-light text-white`}
                        initial={{ opacity: 0, y: 28 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 1, delay: 0.35, ease: [0.22, 1, 0.36, 1] }}
                    >
                        Where weddings
                        <br />
                        <em className="font-normal text-[#e9c176]">find their people.</em>
                    </motion.h1>

                    <motion.div
                        className="mt-10 flex flex-col gap-8 md:flex-row md:items-end md:justify-between"
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.9, delay: 0.6 }}
                    >
                        <p className="max-w-md text-[15px] leading-relaxed text-white/85">
                            Plan every detail in one calm workspace — guests, seating, budget, a wedding website,
                            registry and more — then discover trusted vendors and compare real quotes, all in one place.
                        </p>
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Link
                                href={authed ? dashboard() : register()}
                                className="group flex items-center justify-center gap-3 bg-[#faf6ef] px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#191613] uppercase transition-colors hover:bg-[#e9c176]"
                            >
                                {authed ? 'Open your studio' : 'Start planning — free'}
                                <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                            </Link>
                            <Link
                                href="/marketplace"
                                className="flex items-center justify-center gap-3 border border-white/40 px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-white uppercase transition-colors hover:bg-white hover:text-[#191613]"
                            >
                                Explore vendors
                            </Link>
                        </div>
                    </motion.div>
                </div>
            </section>

            {/* ── Category marquee ───────────────────────────────────────── */}
            <div className="overflow-hidden border-y border-[#191613]/10 bg-[#f3ecdf] py-4">
                <div className="wfa-marquee flex w-max items-center">
                    {[0, 1].map((dup) => (
                        <div key={dup} className="flex items-center" aria-hidden={dup === 1}>
                            {CATEGORIES.map((c) => (
                                <span key={`${dup}-${c}`} className="flex items-center">
                                    <span className={`${fraunces} px-6 text-sm tracking-[0.25em] text-[#52493d] uppercase`}>{c}</span>
                                    <span className="text-[#8a651c]">✦</span>
                                </span>
                            ))}
                        </div>
                    ))}
                </div>
            </div>

            {/* ── 01 · For couples ───────────────────────────────────────── */}
            <section id="couples" className="overflow-hidden px-5 py-24 md:px-12 md:py-36">
                <div className="mx-auto grid max-w-[1480px] items-center gap-14 md:grid-cols-12">
                    <Reveal className="relative md:col-span-5">
                        <div className="relative z-10 -rotate-1 bg-white p-3 shadow-[0_30px_60px_-20px_rgba(25,22,19,0.35)]">
                            <img
                                src={IMG.tablescape}
                                alt="A candlelit wedding reception tablescape an hour before guests arrive"
                                className="aspect-[3/4] w-full object-cover"
                                loading="lazy"
                            />
                            <p className={`${fraunces} px-2 pt-3 pb-1 text-xs italic text-[#52493d]`}>
                                The long table, an hour before everyone arrived.
                            </p>
                        </div>
                        <div className="absolute -top-10 -left-6 z-0 hidden h-full w-full border border-[#8a651c]/25 md:block" />
                        <span className={`${fraunces} pointer-events-none absolute -top-16 -right-2 z-20 text-[120px] leading-none font-light text-[#8a651c]/15 select-none md:text-[170px]`}>
                            01
                        </span>
                    </Reveal>

                    <div className="md:col-span-6 md:col-start-7">
                        <Reveal>
                            <p className="mb-4 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">For couples</p>
                            <h2 className={`${fraunces} text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                                Every detail, <em className="text-[#8a651c]">composed.</em>
                            </h2>
                            <p className="mt-6 max-w-lg text-[15px] leading-relaxed text-[#52493d]">
                                Guest lists, seating, budget, timeline, a wedding website your guests will
                                actually visit — one calm studio instead of eleven spreadsheets.
                            </p>
                        </Reveal>

                        <Stagger className="mt-10 grid gap-x-10 gap-y-8 sm:grid-cols-2">
                            {coupleFeatures.map((f) => (
                                <StaggerItem key={f.title} className="group">
                                    <f.icon className="mb-3 size-5 text-[#8a651c]" />
                                    <h3 className="text-sm font-bold tracking-wide">{f.title}</h3>
                                    <p className="mt-1.5 text-sm leading-relaxed text-[#52493d]">{f.body}</p>
                                </StaggerItem>
                            ))}
                        </Stagger>

                        <Reveal delay={0.2} className="mt-10 flex flex-wrap items-center gap-6">
                            <Link
                                href={authed ? dashboard() : register()}
                                className="bg-[#191613] px-8 py-3.5 text-[11px] font-semibold tracking-[0.2em] text-[#faf6ef] uppercase transition-colors hover:bg-[#8a651c]"
                            >
                                Start planning
                            </Link>
                            <a href={DEMO} className="group flex items-center gap-1.5 text-sm text-[#52493d] underline-offset-4 hover:text-[#8a651c] hover:underline">
                                See a live wedding site
                                <ArrowUpRight className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                            </a>
                        </Reveal>
                    </div>
                </div>
            </section>

            {/* ── The complete suite (NEW) ───────────────────────────────── */}
            <section id="suite" className="overflow-hidden border-t border-[#191613]/10 bg-[#f6efe1] px-5 py-24 md:px-12 md:py-36">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mx-auto max-w-3xl text-center">
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">New · The complete suite</p>
                        <h2 className={`${fraunces} text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                            From the first “yes” to the <em className="text-[#8a651c]">last thank-you note.</em>
                        </h2>
                        <p className="mt-6 text-[15px] leading-relaxed text-[#52493d]">
                            VowNook now carries the whole celebration — a gift registry, a multi-day schedule with
                            per-event RSVPs, hotel room blocks, branded save-the-dates with open-tracking, guest
                            messaging, print-ready stationery, thank-you tracking, and a free <strong className="font-semibold text-[#191613]">name.vownook.com</strong> wedding website.
                        </p>
                    </Reveal>

                    {/* Full-bleed editorial band */}
                    <Reveal className="mt-14">
                        <div className="relative overflow-hidden rounded-sm">
                            <img
                                src={IMG.reception}
                                alt="Guests toasting at a golden-hour outdoor wedding reception"
                                className="h-[300px] w-full object-cover md:h-[460px]"
                                loading="lazy"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-[#191613]/70 via-transparent to-transparent" />
                            <p className={`${fraunces} absolute bottom-6 left-6 max-w-md text-2xl leading-tight text-white md:bottom-10 md:left-10 md:text-4xl`}>
                                One studio for the whole weekend, not just the big day.
                            </p>
                        </div>
                    </Reveal>

                    {/* Eight feature cards */}
                    <Stagger className="mt-14 grid gap-px overflow-hidden rounded-sm border border-[#191613]/10 bg-[#191613]/10 sm:grid-cols-2 lg:grid-cols-4">
                        {suite.map((f) => (
                            <StaggerItem
                                key={f.title}
                                className="group bg-[#faf6ef] p-7 transition-colors duration-300 hover:bg-white"
                            >
                                <div className="flex size-11 items-center justify-center rounded-full bg-[#f0e6d2] text-[#8a651c] transition-transform duration-300 group-hover:-translate-y-0.5">
                                    <f.icon className="size-5" />
                                </div>
                                <h3 className={`${fraunces} mt-4 text-xl font-medium`}>{f.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-[#52493d]">{f.body}</p>
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
                                <div className="absolute inset-0 bg-gradient-to-t from-[#191613]/60 to-transparent" />
                                <span className="absolute bottom-3 left-3 text-[10px] font-semibold tracking-[0.2em] text-white uppercase">{m.label}</span>
                            </StaggerItem>
                        ))}
                    </Stagger>

                    <Reveal delay={0.15} className="mt-12 text-center">
                        <Link
                            href={authed ? dashboard() : register()}
                            className="inline-flex items-center gap-3 bg-[#8a651c] px-9 py-4 text-[11px] font-semibold tracking-[0.2em] text-white uppercase transition-colors hover:bg-[#191613]"
                        >
                            Build your wedding suite
                            <ArrowRight className="size-4" />
                        </Link>
                    </Reveal>
                </div>
            </section>

            {/* ── Product showcase: real screenshots ─────────────────────── */}
            <section className="border-t border-[#191613]/10 px-5 py-24 md:px-12 md:py-36">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mx-auto max-w-3xl text-center">
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">Inside the studio</p>
                        <h2 className={`${fraunces} text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                            Don't imagine it — <em className="text-[#8a651c]">look at it.</em>
                        </h2>
                        <p className="mt-6 text-[15px] leading-relaxed text-[#52493d]">
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
                                            ? 'border-[#191613] bg-[#191613] text-[#faf6ef]'
                                            : 'border-[#191613]/15 bg-white/60 text-[#52493d] hover:border-[#191613]/40'
                                    }`}
                                >
                                    {s.label}
                                </button>
                            ))}
                        </div>

                        <div className="mx-auto mt-8 max-w-5xl">
                            <div className="overflow-hidden rounded-xl border border-[#191613]/10 bg-white shadow-[0_50px_100px_-45px_rgba(25,22,19,0.5)]">
                                <div className="flex items-center gap-2 border-b border-[#191613]/8 bg-[#f4efe6] px-4 py-2.5">
                                    <span className="size-2.5 rounded-full bg-[#e0d6c4]" />
                                    <span className="size-2.5 rounded-full bg-[#e0d6c4]" />
                                    <span className="size-2.5 rounded-full bg-[#e0d6c4]" />
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
                            <p className="mt-5 text-center text-[14px] text-[#52493d]">
                                {SHOWCASE[showcaseIdx].caption}
                            </p>
                            <p className="mt-6 text-center">
                                <Link
                                    href="/features"
                                    className="inline-flex items-center gap-2 text-[13px] font-semibold tracking-[0.14em] text-[#8a651c] uppercase hover:text-[#191613]"
                                >
                                    Take the full tour — every tool, explained <ArrowRight className="size-3.5" />
                                </Link>
                            </p>
                        </div>
                    </Reveal>
                </div>
            </section>

            {/* ── 02 · The marketplace (dark chapter) ────────────────────── */}
            <section className="relative overflow-hidden bg-[#191613] px-5 py-24 text-[#faf6ef] md:px-12 md:py-36">
                <span className={`${fraunces} pointer-events-none absolute top-10 left-6 text-[140px] leading-none font-light text-white/5 select-none md:text-[220px]`}>
                    02
                </span>

                <div className="relative mx-auto grid max-w-[1480px] gap-16 md:grid-cols-12">
                    <div className="md:col-span-5">
                        <Reveal>
                            <p className="mb-4 text-[11px] tracking-[0.3em] text-[#e9c176] uppercase">The marketplace</p>
                            <h2 className={`${fraunces} text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                                Real vendors. Real quotes.
                                <br />
                                <em className="text-[#e9c176]">No guesswork.</em>
                            </h2>
                            <p className="mt-6 max-w-md text-[15px] leading-relaxed text-white/70">
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
                                    <item.icon className="mt-0.5 size-5 shrink-0 text-[#e9c176]" />
                                    <p className="text-sm leading-relaxed text-white/80">{item.text}</p>
                                </StaggerItem>
                            ))}
                        </Stagger>

                        <Reveal delay={0.2} className="mt-10">
                            <Link
                                href="/marketplace"
                                className="inline-flex items-center gap-3 bg-[#e9c176] px-8 py-3.5 text-[11px] font-semibold tracking-[0.2em] text-[#191613] uppercase transition-colors hover:bg-[#faf6ef]"
                            >
                                Browse the marketplace
                                <ArrowRight className="size-4" />
                            </Link>
                        </Reveal>
                    </div>

                    {/* Staggered editorial image pair */}
                    <div className="relative min-h-[420px] md:col-span-6 md:col-start-7">
                        <Reveal className="absolute top-0 left-0 w-[62%]">
                            <div className="bg-[#faf6ef] p-2.5">
                                <img src={IMG.florist} alt="A florist arranging a cream bridal bouquet" className="aspect-[3/4] w-full object-cover" loading="lazy" />
                            </div>
                            <p className="mt-2 text-[10px] tracking-[0.25em] text-white/50 uppercase">Florals · The bouquet bench</p>
                        </Reveal>
                        <Reveal delay={0.18} className="absolute right-0 bottom-0 w-[52%]">
                            <div className="bg-[#faf6ef] p-2.5">
                                <img src={IMG.photographer} alt="A wedding photographer at work in a courtyard at golden hour" className="aspect-[3/4] w-full object-cover" loading="lazy" />
                            </div>
                            <p className="mt-2 text-right text-[10px] tracking-[0.25em] text-white/50 uppercase">Photography · Golden hour</p>
                        </Reveal>
                    </div>
                </div>
            </section>

            {/* ── 03 · For vendors ───────────────────────────────────────── */}
            <section id="vendors" className="relative overflow-hidden px-5 py-24 md:px-12 md:py-36">
                <span className={`${fraunces} pointer-events-none absolute -top-6 right-6 text-[140px] leading-none font-light text-[#8a651c]/10 select-none md:text-[220px]`}>
                    03
                </span>

                <div className="relative mx-auto max-w-[1480px]">
                    <Reveal className="max-w-2xl">
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">For vendors</p>
                        <h2 className={`${fraunces} text-4xl leading-[1.05] font-light sm:text-5xl md:text-6xl`}>
                            Your craft, in front of couples <em className="text-[#8a651c]">ready to book.</em>
                        </h2>
                        <p className="mt-6 text-[15px] leading-relaxed text-[#52493d]">
                            Build a portfolio page with packages and availability, receive structured inquiries
                            instead of cold DMs, and reply with offers that win — all from one business dashboard.
                        </p>
                    </Reveal>

                    <Stagger className="mt-14 grid gap-px overflow-hidden border border-[#191613]/10 bg-[#191613]/10 sm:grid-cols-3">
                        {vendorStats.map((s) => (
                            <StaggerItem key={s.label} className="bg-[#faf6ef] p-10 text-center sm:p-12">
                                <p className={`${fraunces} text-5xl font-light text-[#8a651c] sm:text-6xl`}>{s.value}</p>
                                <p className="mt-3 text-sm text-[#52493d]">{s.label}</p>
                            </StaggerItem>
                        ))}
                    </Stagger>

                    <Reveal delay={0.15} className="mt-10 flex flex-wrap items-center gap-6">
                        <Link
                            href="/register?type=vendor"
                            className="bg-[#8a651c] px-8 py-3.5 text-[11px] font-semibold tracking-[0.2em] text-white uppercase transition-colors hover:bg-[#191613]"
                        >
                            List your business — free
                        </Link>
                        <p className="text-sm text-[#52493d]">Reviewed and published, usually within a day.</p>
                    </Reveal>
                </div>
            </section>

            {/* ── How it works ───────────────────────────────────────────── */}
            <section className="border-y border-[#191613]/10 bg-[#f3ecdf] px-5 py-24 md:px-12 md:py-32">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mb-16 text-center">
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            How the room <em className="text-[#8a651c]">comes together</em>
                        </h2>
                    </Reveal>

                    <div className="grid gap-16 md:grid-cols-2 md:gap-24">
                        <div>
                            <p className="mb-8 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">If you're planning</p>
                            <Stagger className="space-y-10">
                                {coupleSteps.map((s) => (
                                    <StaggerItem key={s.n} className="flex gap-6">
                                        <span className={`${fraunces} text-3xl font-light text-[#8a651c]/50`}>{s.n}</span>
                                        <div>
                                            <h3 className="text-sm font-bold tracking-wide">{s.title}</h3>
                                            <p className="mt-1.5 text-sm leading-relaxed text-[#52493d]">{s.body}</p>
                                        </div>
                                    </StaggerItem>
                                ))}
                            </Stagger>
                        </div>
                        <div>
                            <p className="mb-8 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">If you're a vendor</p>
                            <Stagger className="space-y-10">
                                {vendorSteps.map((s) => (
                                    <StaggerItem key={s.n} className="flex gap-6">
                                        <span className={`${fraunces} text-3xl font-light text-[#8a651c]/50`}>{s.n}</span>
                                        <div>
                                            <h3 className="text-sm font-bold tracking-wide">{s.title}</h3>
                                            <p className="mt-1.5 text-sm leading-relaxed text-[#52493d]">{s.body}</p>
                                        </div>
                                    </StaggerItem>
                                ))}
                            </Stagger>
                        </div>
                    </div>
                </div>
            </section>

            {/* ── Pricing ────────────────────────────────────────────────── */}
            <section id="pricing" className="px-5 py-24 md:px-12 md:py-36">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mb-16">
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">Memberships</p>
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            Choose your <em className="text-[#8a651c]">level of ceremony.</em>
                        </h2>
                    </Reveal>

                    <div className="grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-4">
                        {tiers.map((tier) => (
                            <div
                                key={tier.name}
                                className={`flex flex-col justify-between p-10 md:p-12 ${
                                    tier.featured
                                        ? 'relative bg-[#191613] text-[#faf6ef] shadow-[0_30px_60px_-25px_rgba(25,22,19,0.6)] md:-translate-y-5'
                                        : 'border border-[#191613]/12 bg-[#faf6ef]'
                                }`}
                            >
                                {tier.featured && (
                                    <div className="absolute top-0 right-0 bg-[#8a651c] px-5 py-2 text-[10px] tracking-[0.25em] text-white uppercase">
                                        Recommended
                                    </div>
                                )}
                                <div>
                                    <h3 className={`mb-2 text-xs font-bold tracking-[0.25em] uppercase ${tier.featured ? 'text-[#e9c176]' : 'text-[#191613]'}`}>
                                        {tier.name}
                                    </h3>
                                    <div className="mb-8 flex items-baseline gap-2">
                                        <span className={`${fraunces} text-5xl font-light`}>{tier.price}</span>
                                        <span className={`text-sm ${tier.featured ? 'text-white/60' : 'text-[#52493d]'}`}>{tier.cadence}</span>
                                    </div>
                                    <ul className="mb-12 space-y-4">
                                        {tier.features.map((f) => (
                                            <li key={f} className="flex items-center gap-3 text-sm">
                                                <Check className={`size-4 shrink-0 ${tier.featured ? 'text-[#e9c176]' : 'text-[#8a651c]'}`} />
                                                <span className={tier.featured ? 'text-white/85' : 'text-[#52493d]'}>{f}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <Link
                                    href={tier.href}
                                    className={`py-4 text-center text-[11px] font-semibold tracking-[0.2em] uppercase transition-colors ${
                                        tier.featured
                                            ? 'bg-[#8a651c] text-white hover:bg-[#e9c176] hover:text-[#191613]'
                                            : 'border border-[#191613] text-[#191613] hover:bg-[#191613] hover:text-[#faf6ef]'
                                    }`}
                                >
                                    {tier.cta}
                                </Link>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── FAQ (organic + AI search) ──────────────────────────────── */}
            <section className="border-t border-[#191613]/10 bg-[#f6efe1] px-5 py-24 md:px-12 md:py-32">
                <div className="mx-auto grid max-w-[1480px] gap-14 md:grid-cols-12">
                    <Reveal className="md:col-span-4">
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#8a651c] uppercase">Good to know</p>
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            Questions, <em className="text-[#8a651c]">answered.</em>
                        </h2>
                        <p className="mt-6 text-sm leading-relaxed text-[#52493d]">
                            Everything couples and vendors ask before they begin. Still curious?{' '}
                            <Link href="/contact" className="text-[#8a651c] underline-offset-4 hover:underline">Talk to us.</Link>
                        </p>
                    </Reveal>

                    <div className="md:col-span-7 md:col-start-6">
                        <Stagger className="divide-y divide-[#191613]/10 border-y border-[#191613]/10">
                            {faqs.map((item) => (
                                <StaggerItem key={item.q}>
                                    <details className="group">
                                        <summary className="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-left">
                                            <h3 className={`${fraunces} text-lg font-medium`}>{item.q}</h3>
                                            <span className="grid size-7 shrink-0 place-items-center rounded-full border border-[#8a651c]/40 text-[#8a651c] transition-transform duration-300 group-open:rotate-45">+</span>
                                        </summary>
                                        <p className="pb-5 text-sm leading-relaxed text-[#52493d]">{item.a}</p>
                                    </details>
                                </StaggerItem>
                            ))}
                        </Stagger>
                    </div>
                </div>
            </section>

            {/* ── Final CTA ──────────────────────────────────────────────── */}
            <section className="bg-[#191613] px-5 py-28 text-center text-[#faf6ef] md:py-40">
                <Reveal className="mx-auto max-w-3xl">
                    <p className="mb-6 text-[11px] tracking-[0.35em] text-[#e9c176] uppercase">The invitation stands</p>
                    <h2 className={`${fraunces} text-4xl leading-[1.1] font-light sm:text-6xl`}>
                        The best days are <em className="text-[#e9c176]">planned together.</em>
                    </h2>
                    <div className="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Link
                            href={authed ? dashboard() : register()}
                            className="group inline-flex items-center gap-3 bg-[#faf6ef] px-12 py-5 text-[11px] font-semibold tracking-[0.25em] text-[#191613] uppercase transition-colors hover:bg-[#e9c176]"
                        >
                            {authed ? 'Open your studio' : 'Create your account'}
                            <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                        <Link
                            href="/register?type=vendor"
                            className="inline-flex items-center gap-3 border border-white/30 px-12 py-5 text-[11px] font-semibold tracking-[0.25em] text-white uppercase transition-colors hover:bg-white hover:text-[#191613]"
                        >
                            I'm a vendor
                        </Link>
                    </div>
                </Reveal>
            </section>

            {/* ── Footer ─────────────────────────────────────────────────── */}
            <footer className="border-t border-[#191613]/10 bg-[#faf6ef] py-12">
                <div className="mx-auto flex max-w-[1480px] flex-col items-center justify-between gap-6 px-5 md:flex-row md:px-12">
                    <Wordmark />
                    <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-[13px] text-[#52493d]">
                        <Link href="/marketplace" className="hover:text-[#8a651c]">Marketplace</Link>
                        <Link href="/features" className="hover:text-[#8a651c]">Features</Link>
                        <Link href="/how-it-works" className="hover:text-[#8a651c]">How it works</Link>
                        <Link href="/pricing" className="hover:text-[#8a651c]">Pricing</Link>
                        <Link href="/blog" className="hover:text-[#8a651c]">Journal</Link>
                        <a href={DEMO} className="hover:text-[#8a651c]">Live demo</a>
                        <Link href="/terms" className="hover:text-[#8a651c]">Terms</Link>
                        <Link href="/privacy" className="hover:text-[#8a651c]">Privacy</Link>
                        <Link href="/contact" className="hover:text-[#8a651c]">Contact</Link>
                        <Link href={login()} className="hover:text-[#8a651c]">Sign in</Link>
                    </div>
                    <p className="text-[11px] tracking-[0.15em] text-[#52493d]/70 uppercase">© {new Date().getFullYear()} VowNook</p>
                </div>
            </footer>
        </div>
    );
}

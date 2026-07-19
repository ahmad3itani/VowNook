import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';

const fraunces = "font-['Newsreader']";

type Step = { n: string; title: string; body: string; detail: string[] };

const coupleSteps: Step[] = [
    {
        n: '01',
        title: 'Create your studio — free',
        body: 'One account opens your private planning workspace. No credit card, no trial clock.',
        detail: [
            'Guest list with groups, plus-ones and meal choices',
            'RSVP tracking with a public reply page for guests',
            'Budget tracker — estimates, actuals and what is paid',
            'Checklist, timeline and a day-of schedule you can share',
        ],
    },
    {
        n: '02',
        title: 'Design your wedding website',
        body: 'Pick a template, add your photos and video, and publish a page guests will actually visit.',
        detail: [
            'Eight design themes — Classic, Modern, Botanical, Blush, Royal Gold and more',
            'Hero image or video, your story, a timeline of moments and a photo gallery',
            'Registry, travel & stays, save-the-dates and a multi-day schedule with per-event RSVP',
            'A free name.vownook.com web address and a live countdown to your date',
        ],
    },
    {
        n: '03',
        title: 'Browse the marketplace',
        body: 'Every vendor is reviewed before publishing. Filter by category, city, province and budget.',
        detail: [
            'Portfolios with galleries, packages and transparent starting prices',
            'Verified reviews from couples who actually booked — never pay-to-play',
            'No account needed to browse; sign in only when you want to reach out',
        ],
    },
    {
        n: '04',
        title: 'Request quotes — in one minute',
        body: 'Send your date, guest count and budget once. Vendors reply with structured offers, not vague DMs.',
        detail: [
            'One open inquiry per vendor keeps your inbox clean',
            'A message thread with each vendor, all in one place',
            'Offers arrive itemised: line items, deposit, terms and an expiry date',
        ],
    },
    {
        n: '05',
        title: 'Compare offers side by side',
        body: 'Quotes group by category so you can weigh photographers against photographers, not apples against venues.',
        detail: [
            'Totals, deposits and line items aligned for honest comparison',
            'Accept the offer that fits — or decline with one click, no awkward emails',
        ],
    },
    {
        n: '06',
        title: 'Book — and keep planning',
        body: 'Accepting an offer creates the booking and drops the vendor straight into your planning workspace.',
        detail: [
            'The booked vendor appears in your vendor list with cost already filled in',
            'Booking is free for couples — vendors pay a small success fee, never you',
            'After your day, leave a review tied to the real booking',
        ],
    },
];

const vendorSteps: Step[] = [
    {
        n: '01',
        title: 'List your business — $0',
        body: 'No subscription, no contract, no lock-in. Your listing costs nothing, forever.',
        detail: [
            'Portfolio page with logo, cover, gallery and your story',
            'Category, city and province so local couples find you',
            'Packages with fixed, starting-at or quote-only pricing',
        ],
    },
    {
        n: '02',
        title: 'Get reviewed & published',
        body: 'Every profile is checked by our team before it goes live — usually within a day.',
        detail: [
            'Moderation keeps the marketplace trustworthy, which keeps inquiry quality high',
            'Your public page is search-engine optimised with structured data, working for you on Google',
        ],
    },
    {
        n: '03',
        title: 'Manage your availability',
        body: 'A simple calendar — mark dates booked or blocked so inquiries match your real capacity.',
        detail: [
            'Click a date to cycle it: available, booked, blocked',
            'Toggle "accepting bookings" off any time you are at capacity',
        ],
    },
    {
        n: '04',
        title: 'Receive real inquiries',
        body: 'Couples come to you with a date, a guest count and a budget — not cold "how much?" messages.',
        detail: [
            'Email notification the moment an inquiry lands',
            'Every lead in one inbox with the full conversation thread',
        ],
    },
    {
        n: '05',
        title: 'Reply with a structured offer',
        body: 'Build an itemised quote in minutes: line items, deposit, terms, valid-until date.',
        detail: [
            'Replace or withdraw an offer any time before it is accepted',
            'Couples compare offers transparently — clear quotes win',
        ],
    },
    {
        n: '06',
        title: 'Win the booking, track earnings',
        body: 'When a couple accepts, the booking is confirmed and your earnings dashboard keeps score.',
        detail: [
            'Success fee only when you win: 8% up to $5,000, 5% above, capped at $1,000',
            'No booking, no fee — ever',
            'Reviews from real bookings build your profile; respond to each one publicly',
        ],
    },
];

type Faq = { q: string; a: string };

function StepList({ steps }: { steps: Step[] }) {
    return (
        <Stagger className="space-y-14">
            {steps.map((s) => (
                <StaggerItem key={s.n} className="grid gap-5 md:grid-cols-12">
                    <div className="flex items-start gap-5 md:col-span-5">
                        <span className={`${fraunces} text-4xl font-light text-[#1f5142]/50`}>{s.n}</span>
                        <div>
                            <h3 className={`${fraunces} text-2xl font-light`}>{s.title}</h3>
                            <p className="mt-2 text-[15px] leading-relaxed text-[#4b5850]">{s.body}</p>
                        </div>
                    </div>
                    <ul className="space-y-2.5 md:col-span-6 md:col-start-7">
                        {s.detail.map((d) => (
                            <li key={d} className="flex items-start gap-3 text-sm leading-relaxed text-[#4b5850]">
                                <span className="mt-2 size-1 shrink-0 rounded-full bg-[#1f5142]" />
                                {d}
                            </li>
                        ))}
                    </ul>
                </StaggerItem>
            ))}
        </Stagger>
    );
}

export default function HowItWorks({ faqs }: { faqs: Faq[] }) {
    return (
        <div className="min-h-screen bg-[#f1f0ea] font-['Instrument_Sans'] text-[#0f1c17] antialiased selection:bg-[#7fb79e]/40">
            {/* Description/canonical/OG are server-rendered in the blade head. */}
            <Head title="How it works" />

            {/* Header */}
            <header className="fixed inset-x-0 top-0 z-50 border-b border-[#0f1c17]/8 bg-[#f1f0ea]/85 backdrop-blur-md">
                <nav className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-4 md:px-12">
                    <Link href="/" className="flex items-center gap-2.5" aria-label="VowNook home">
                        <img src="/images/brand/logo-mark.svg" alt="" className="size-9 rounded-md border border-[#0f1c17]/10" />
                        <span className={`${fraunces} text-[22px] font-medium tracking-tight`}>VowNook</span>
                    </Link>
                    <div className="hidden items-center gap-9 md:flex">
                        <a href="#couples" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">For couples</a>
                        <a href="#vendors" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">For vendors</a>
                        <a href="#faq" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">FAQ</a>
                        <Link href="/marketplace" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">Marketplace</Link>
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
            <section className="px-5 pt-32 pb-16 md:px-12 md:pt-40 md:pb-24">
                <div className="mx-auto grid max-w-[1480px] items-end gap-12 md:grid-cols-12">
                    <Reveal className="md:col-span-6">
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">The field guide</p>
                        <h1 className={`${fraunces} text-5xl leading-[1.02] font-light sm:text-6xl md:text-7xl`}>
                            How it <em className="text-[#1f5142]">works.</em>
                        </h1>
                        <p className="mt-6 max-w-xl text-[15px] leading-relaxed text-[#4b5850]">
                            One platform, two sides of the aisle: a planning studio for couples and a
                            marketplace where great vendors meet couples ready to book. Here is the whole
                            journey, step by step.
                        </p>
                    </Reveal>
                    <Reveal delay={0.15} className="md:col-span-6">
                        <div className="relative -rotate-1 bg-white p-3 shadow-[0_30px_60px_-22px_rgba(25,22,19,0.35)]">
                            <img
                                src="/images/landing/reception.webp"
                                alt="Guests raising a toast at a golden-hour wedding reception"
                                className="aspect-[16/10] w-full object-cover"
                                loading="lazy"
                            />
                        </div>
                    </Reveal>
                </div>
            </section>

            {/* Couples */}
            <section id="couples" className="border-t border-[#0f1c17]/10 px-5 py-20 md:px-12 md:py-28">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mb-14">
                        <p className="mb-3 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">For couples</p>
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            From "yes" to <em className="text-[#1f5142]">"I do"</em> — organised.
                        </h2>
                    </Reveal>
                    <StepList steps={coupleSteps} />
                    <Reveal className="mt-14">
                        <Link
                            href="/register"
                            className="inline-flex items-center gap-3 cta-press px-9 py-4 text-[11px] font-semibold tracking-[0.22em] uppercase"
                        >
                            Start planning — free
                            <ArrowRight className="size-4" />
                        </Link>
                    </Reveal>
                </div>
            </section>

            {/* Vendors */}
            <section id="vendors" className="bg-[#0f1c17] px-5 py-20 text-[#f1f0ea] md:px-12 md:py-28">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mb-14">
                        <p className="mb-3 text-[11px] tracking-[0.3em] text-[#7fb79e] uppercase">For vendors</p>
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            Leads you can <em className="text-[#7fb79e]">actually close.</em>
                        </h2>
                        <p className="mt-4 max-w-xl text-[15px] text-white/70">
                            No subscriptions. No 12-month contracts. You pay a capped success fee only when
                            a couple books you — if we don't earn you business, we earn nothing.
                        </p>
                    </Reveal>

                    <Stagger className="space-y-14">
                        {vendorSteps.map((s) => (
                            <StaggerItem key={s.n} className="grid gap-5 md:grid-cols-12">
                                <div className="flex items-start gap-5 md:col-span-5">
                                    <span className={`${fraunces} text-4xl font-light text-[#7fb79e]/50`}>{s.n}</span>
                                    <div>
                                        <h3 className={`${fraunces} text-2xl font-light`}>{s.title}</h3>
                                        <p className="mt-2 text-[15px] leading-relaxed text-white/70">{s.body}</p>
                                    </div>
                                </div>
                                <ul className="space-y-2.5 md:col-span-6 md:col-start-7">
                                    {s.detail.map((d) => (
                                        <li key={d} className="flex items-start gap-3 text-sm leading-relaxed text-white/70">
                                            <span className="mt-2 size-1 shrink-0 rounded-full bg-[#7fb79e]" />
                                            {d}
                                        </li>
                                    ))}
                                </ul>
                            </StaggerItem>
                        ))}
                    </Stagger>

                    <Reveal className="mt-14">
                        <Link
                            href="/register?type=vendor"
                            className="inline-flex items-center gap-3 bg-[#7fb79e] px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#0f1c17] uppercase transition-colors hover:bg-[#f1f0ea]"
                        >
                            List your business — free
                            <ArrowRight className="size-4" />
                        </Link>
                    </Reveal>
                </div>
            </section>

            {/* FAQ */}
            <section id="faq" className="px-5 py-20 md:px-12 md:py-28">
                <div className="mx-auto max-w-[1480px]">
                    <Reveal className="mb-14">
                        <p className="mb-3 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">Questions, answered</p>
                        <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>
                            The fine print, <em className="text-[#1f5142]">in plain words.</em>
                        </h2>
                    </Reveal>
                    <Stagger className="grid gap-x-16 gap-y-10 md:grid-cols-2">
                        {faqs.map((f) => (
                            <StaggerItem key={f.q}>
                                <h3 className="text-sm font-bold tracking-wide">{f.q}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-[#4b5850]">{f.a}</p>
                            </StaggerItem>
                        ))}
                    </Stagger>
                </div>
            </section>

            {/* CTA + footer */}
            <section className="border-t border-[#0f1c17]/10 px-5 py-20 text-center md:py-24">
                <Reveal>
                    <h2 className={`${fraunces} text-3xl font-light sm:text-4xl`}>
                        Ready when <em className="text-[#1f5142]">you are.</em>
                    </h2>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href="/register"
                            className="cta-press px-10 py-4 text-[11px] font-semibold tracking-[0.22em] uppercase"
                        >
                            I'm planning a wedding
                        </Link>
                        <Link
                            href="/register?type=vendor"
                            className="border border-[#0f1c17] px-10 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#0f1c17] uppercase transition-colors hover:bg-[#0f1c17] hover:text-[#f1f0ea]"
                        >
                            I'm a vendor
                        </Link>
                    </div>
                </Reveal>
            </section>

            <footer className="border-t border-[#0f1c17]/10 py-10">
                <div className="mx-auto flex max-w-[1480px] flex-col items-center justify-between gap-4 px-5 md:flex-row md:px-12">
                    <Link href="/" className="flex items-center gap-2.5">
                        <img src="/images/brand/logo-mark.svg" alt="" className="size-8 rounded-md border border-[#0f1c17]/10" />
                        <span className={`${fraunces} text-xl`}>VowNook</span>
                    </Link>
                    <div className="flex items-center gap-6 text-[13px] text-[#4b5850]">
                        <Link href="/terms" className="hover:text-[#1f5142]">Terms</Link>
                        <Link href="/privacy" className="hover:text-[#1f5142]">Privacy</Link>
                        <Link href="/contact" className="hover:text-[#1f5142]">Contact</Link>
                    </div>
                    <p className="text-[11px] tracking-[0.15em] text-[#4b5850]/70 uppercase">
                        © {new Date().getFullYear()} VowNook
                    </p>
                </div>
            </footer>
        </div>
    );
}

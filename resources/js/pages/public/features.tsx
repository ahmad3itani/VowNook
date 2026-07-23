import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Rail } from '@/components/marketing/rail';
import { SiteHeader } from '@/components/public/site-header';
import { Reveal } from '@/components/motion/reveal';

const fraunces = "font-['Newsreader']";

type Tool = {
    id: string;
    chapter: string;
    name: string;
    tagline: string;
    body: string;
    bullets: string[];
    plan: 'Free' | 'Atelier' | 'Shop';
    img: string;
    alt: string;
    crop?: boolean;
};

const CHAPTERS = [
    { id: 'plan', label: 'Plan the day' },
    { id: 'share', label: 'Share it with guests' },
    { id: 'book', label: 'Book your vendors' },
    { id: 'finish', label: 'The finishing touches' },
];

const TOOLS: Tool[] = [
    {
        id: 'dashboard',
        chapter: 'plan',
        name: 'The dashboard',
        tagline: 'Your whole wedding on one calm screen',
        body: 'A live countdown, your RSVP rate, budget health, overdue tasks and unbooked vendors — everything that needs your attention, surfaced before you have to go looking for it.',
        bullets: [
            'Journey milestones show exactly what to do next',
            '"Needs attention" pulls overdue tasks and vendor gaps together',
            'Live stats: guests, budget, tasks and seating at a glance',
        ],
        plan: 'Free',
        img: '/images/tour/dashboard.webp',
        alt: 'The VowNook dashboard with countdown, stats and a needs-attention list',
    },
    {
        id: 'guests',
        chapter: 'plan',
        name: 'Guests & RSVPs',
        tagline: 'One list that answers every "who" question',
        body: 'Households, plus-ones, meal choices and dietary notes in one place. Guests reply on a beautiful public RSVP page — no accounts, no apps — and their answers flow straight into your seating chart and caterer counts.',
        bullets: [
            'Per-course meal selection you define, guests just pick',
            'One-click reminders to everyone who hasn\'t replied',
            'CSV and PDF exports for venues and caterers',
        ],
        plan: 'Free',
        img: '/images/tour/guests.webp',
        alt: 'The VowNook guest list with RSVP statuses and meal choices',
    },
    {
        id: 'budget',
        chapter: 'plan',
        name: 'The budget tracker',
        tagline: 'Estimated vs. actual, per vendor — where budgets are won',
        body: 'Most weddings run 8–12% over plan because "quoted" and "final invoice" quietly drift apart. VowNook tracks estimates, actuals, deposits and what\'s still owed for every line, so the drift never surprises you.',
        bullets: [
            'Category breakdowns with paid / owing at a glance',
            'Deposits and balances tracked per vendor',
            'Booked marketplace vendors drop in with costs pre-filled',
        ],
        plan: 'Free',
        img: '/images/tour/budget.webp',
        alt: 'The VowNook budget tracker with estimated versus actual spending',
    },
    {
        id: 'checklist',
        chapter: 'plan',
        name: 'Checklist & timeline',
        tagline: 'The right task at the right month',
        body: 'A date-aware checklist seeded from what actually needs doing, plus a day-of timeline that ties every moment to the vendor making it happen — exportable to your calendar and printable for your crew.',
        bullets: [
            'Tasks with due dates keyed to your wedding date',
            'Run-of-show timeline with locations and vendors attached',
            'Calendar (.ics) and PDF exports',
        ],
        plan: 'Free',
        img: '/images/tour/timeline.webp',
        alt: 'The VowNook day-of timeline with vendor-linked events',
    },
    {
        id: 'seating',
        chapter: 'plan',
        name: 'The seating studio',
        tagline: 'A real floor plan, not a spreadsheet',
        body: 'Size your actual room, place tables, dance floor, stage and DJ booth, then seat guests chair by chair. Capacity, side balance and meal counts update live — and everything exports as print-ready escort cards, place cards and table numbers.',
        bullets: [
            'Drag-and-drop tables and venue elements to scale',
            'Live infographics: capacity, side balance, meal totals',
            'Printables: escort cards, place cards, table numbers, F&B sheet',
        ],
        plan: 'Atelier',
        img: '/images/tour/seating.webp',
        alt: 'The VowNook seating studio with a floor plan and live stats',
    },
    {
        id: 'website',
        chapter: 'share',
        name: 'Your wedding website',
        tagline: 'The page your guests will actually visit',
        body: 'Eight designer templates, your photos and story, a live countdown, schedule, travel notes and RSVP — opened with a tap-to-reveal invitation and your own background music. Publishing is one click.',
        bullets: [
            'Tap-to-open invitation with your song',
            'Story, gallery, wedding party, FAQ and guestbook sections',
            'Guests RSVP right on the site — replies land in your list',
        ],
        plan: 'Atelier',
        img: '/images/tour/wedding-site.webp',
        alt: 'A published VowNook wedding website with a live countdown',
    },
    {
        id: 'editor',
        chapter: 'share',
        name: 'The website editor',
        tagline: 'Everything editable, nothing technical',
        body: 'Swap templates with one click, upload your hero photo and music, reorder the gallery, and let the AI fill your story and FAQ from a few notes. What you see is exactly what guests get.',
        bullets: [
            'Eight templates, all yours — switch anytime',
            'AI-fill drafts your story, FAQ and local guide',
            'Hero photo or video, gallery, timeline of moments',
        ],
        plan: 'Free',
        img: '/images/tour/website-editor.webp',
        alt: 'The VowNook website editor with template picker and sections',
    },
    {
        id: 'registry',
        chapter: 'share',
        name: 'Registry & gifts',
        tagline: 'Cash funds and gifts, without the awkwardness',
        body: 'Honeymoon and cash funds with progress bars, plus gift items linked to any store. Contributions are logged for your thank-you list — and the gift tracker keeps score of every thank-you note still owed.',
        bullets: [
            'Honeymoon, cash and custom funds with goals',
            'Gift items with store links and claim tracking',
            'Automatic gifts & thank-yous ledger after the day',
        ],
        plan: 'Atelier',
        img: '/images/tour/registry.webp',
        alt: 'The VowNook registry with cash funds and gift items',
    },
    {
        id: 'marketplace',
        chapter: 'book',
        name: 'The vendor marketplace',
        tagline: 'Compare real quotes, not Instagram DMs',
        body: 'Browse Ontario vendors with transparent starting prices, send one structured quote request, and get itemized offers you can compare side by side. Booking is free for couples — vendors pay a small success fee, never you.',
        bullets: [
            'Filter by category, city and budget',
            'Itemized offers: line items, deposit, terms, expiry',
            'Accepting an offer drops the vendor into your plan, cost pre-filled',
        ],
        plan: 'Free',
        img: '/images/tour/marketplace.webp',
        alt: 'The VowNook marketplace with Ontario wedding vendors',
    },
    {
        id: 'stationery',
        chapter: 'finish',
        name: 'The stationery studio',
        tagline: 'Type your names, watch your invitations draw themselves',
        body: 'Ten invitation designs in three colourways, personalised live in your browser — invitations, save-the-dates, menus, welcome signs, place cards and thank-yous, all matching, all print-ready.',
        bullets: [
            'Live preview: your names, date and venue on the real designs',
            'Whole-day sets from $14 — instant download',
            'Editable forever in free Canva or right in the browser',
        ],
        plan: 'Shop',
        img: '/images/tour/shop-personalizer.webp',
        alt: 'The VowNook stationery personaliser with a live invitation preview',
    },
];

const PLAN_STYLES: Record<Tool['plan'], string> = {
    Free: 'bg-[#eef4e8] text-[#42552f] border-[#cfdcbe]',
    Atelier: 'bg-[#f6ecd7] text-[#1f5142] border-[#bfd8cb]',
    Shop: 'bg-[#f3e7e2] text-[#a05c3f] border-[#e4c9bd]',
};

function BrowserFrame({ img, alt }: { img: string; alt: string }) {
    return (
        <div className="overflow-hidden rounded-xl border border-[#0f1c17]/10 bg-white shadow-[0_40px_80px_-40px_rgba(25,22,19,0.45)]">
            <div className="flex items-center gap-2 border-b border-[#0f1c17]/8 bg-[#eef1eb] px-4 py-2.5">
                <span className="size-2.5 rounded-full bg-[#cfd8d0]" />
                <span className="size-2.5 rounded-full bg-[#cfd8d0]" />
                <span className="size-2.5 rounded-full bg-[#cfd8d0]" />
                <span className="mx-auto flex h-5 w-1/2 items-center justify-center rounded bg-white/70 text-[10px] tracking-wide text-[#8c8478]">
                    vownook.com
                </span>
            </div>
            <img src={img} alt={alt} loading="lazy" className="block w-full" width={1440} height={900} />
        </div>
    );
}

export default function Features() {
    return (
        <div className="min-h-screen bg-[#f1f0ea] font-['Instrument_Sans'] text-[#0f1c17] antialiased selection:bg-[#7fb79e]/40">
            <Head title="Features" />

            {/* Header */}
            <SiteHeader />

            {/* Hero */}
            <section className="px-5 pt-14 pb-14 md:px-12 md:pt-20 md:pb-20">
                <Rail
                    n="N°01"
                    label={
                        <>
                            The full
                            <br />
                            tour
                        </>
                    }
                >
                    <Reveal className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-end">
                        <h1 className={`${fraunces} max-w-3xl text-5xl leading-[1.02] font-light sm:text-6xl md:text-[4.2rem]`}>
                            Every tool, shown <em className="text-[#1f5142]">exactly as it is.</em>
                        </h1>
                        <p className="text-[15px] leading-relaxed text-[#4b5850]">
                            These aren't mockups — every screenshot below is the real product with a real
                            wedding in it. This is what planning looks like inside VowNook, from the first
                            guest to the last thank-you note.
                        </p>
                    </Reveal>
                </Rail>
            </section>

            {/* Tool blocks */}
            {CHAPTERS.map((chapter, ci) => {
                const tools = TOOLS.filter((t) => t.chapter === chapter.id);
                return (
                    <section key={chapter.id} id={chapter.id} className={`scroll-mt-24 px-5 py-14 md:px-12 md:py-20 ${ci % 2 === 1 ? '' : 'border-t border-[#0f1c17]/10'}`}>
                        {/* The rail carries the chapter number, so the duplicated
                            "Chapter 0X" eyebrow above every heading is gone. */}
                        <Rail n={`N°${String(ci + 2).padStart(2, '0')}`} label={chapter.label}>
                            <Reveal className="mb-12">
                                <h2 className={`${fraunces} text-4xl font-light sm:text-5xl`}>{chapter.label}</h2>
                            </Reveal>

                            <div className="space-y-20 md:space-y-28">
                                {tools.map((tool, ti) => (
                                    <Reveal key={tool.id}>
                                        <div className={`grid items-center gap-10 md:grid-cols-12 md:gap-14`}>
                                            <div className={`md:col-span-7 ${ti % 2 === 1 ? 'md:order-2' : ''}`}>
                                                <BrowserFrame img={tool.img} alt={tool.alt} />
                                            </div>
                                            <div className={`md:col-span-5 ${ti % 2 === 1 ? 'md:order-1' : ''}`}>
                                                <span className={`inline-block rounded-full border px-3 py-1 text-[10px] font-semibold tracking-[0.14em] uppercase ${PLAN_STYLES[tool.plan]}`}>
                                                    {tool.plan === 'Free' ? 'Included free' : tool.plan === 'Atelier' ? 'Atelier · $99 once' : 'Stationery shop'}
                                                </span>
                                                <h3 className={`${fraunces} mt-4 text-3xl font-light sm:text-4xl`}>{tool.name}</h3>
                                                <p className="mt-1.5 text-[15px] font-medium text-[#1f5142]">{tool.tagline}</p>
                                                <p className="mt-4 text-[15px] leading-relaxed text-[#4b5850]">{tool.body}</p>
                                                <ul className="mt-5 space-y-2.5">
                                                    {tool.bullets.map((b) => (
                                                        <li key={b} className="flex gap-2.5 text-[14px] text-[#39433d]">
                                                            <span className="mt-[7px] size-1.5 flex-none rounded-full bg-[#1f5142]" />
                                                            {b}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        </div>
                                    </Reveal>
                                ))}
                            </div>
                        </Rail>
                    </section>
                );
            })}

            {/* CTA */}
            <section className="border-t border-[#0f1c17]/10 bg-[#0f1c17] px-5 py-20 text-center text-[#f1f0ea] md:px-12 md:py-28">
                <Reveal>
                    <h2 className={`${fraunces} mx-auto max-w-2xl text-4xl font-light sm:text-5xl`}>
                        All of this starts <em className="text-[#bfd8cb]">free.</em>
                    </h2>
                    <p className="mx-auto mt-5 max-w-lg text-[15px] leading-relaxed text-[#cfc6b6]">
                        No credit card, no trial clock. Plan with every core tool free, and unlock the
                        full suite with Atelier — $99 once, not a subscription.
                    </p>
                    <div className="mt-9 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href="/register"
                            className="inline-flex items-center gap-3 bg-[#bfd8cb] px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#0f1c17] uppercase transition-colors hover:bg-white"
                        >
                            Start planning free <ArrowRight className="size-3.5" />
                        </Link>
                        <Link
                            href="/pricing"
                            className="inline-flex items-center gap-3 border border-white/25 px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#f1f0ea] uppercase transition-colors hover:border-white"
                        >
                            See pricing
                        </Link>
                    </div>
                </Reveal>
            </section>

            {/* Footer */}
            <footer className="border-t border-[#0f1c17]/10 py-10">
                <div className="mx-auto flex max-w-[1480px] flex-wrap items-center justify-between gap-4 px-5 text-[13px] text-[#4b5850] md:px-12">
                    <span>© {new Date().getFullYear()} VowNook — made in Ontario.</span>
                    <div className="flex flex-wrap gap-6">
                        <Link href="/how-it-works" className="hover:text-[#1f5142]">How it works</Link>
                        <Link href="/pricing" className="hover:text-[#1f5142]">Pricing</Link>
                        <Link href="/marketplace" className="hover:text-[#1f5142]">Marketplace</Link>
                        <a href="/shop" className="hover:text-[#1f5142]">Shop</a>
                    </div>
                </div>
            </footer>
        </div>
    );
}

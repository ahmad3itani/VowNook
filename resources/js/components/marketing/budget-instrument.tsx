import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { type ComponentProps, useMemo, useState } from 'react';

/**
 * The homepage hero is a working tool, not a photograph.
 *
 * Every competitor in this category opens with a stock image and a soft line of
 * copy — Zola, Junebug and VowNook's own previous homepage are structurally the
 * same page. The one asset none of them have is real Ontario cost data, so the
 * hero hands the visitor an instrument instead of a poem: type a budget, pick a
 * city, and the allocation appears immediately, priced for that city.
 *
 * The arithmetic mirrors App\Support\Budget\BudgetAllocator (amount = total x
 * share; typical = (fixed + perGuest x guests) x cityIndex). Both are plain
 * multiplication, so nothing rounds out of sync — but if the PHP formula
 * changes, change it here too. Cost ranges are NOT recomputed here; they are
 * passed through verbatim from LocalCosts so the hero can never quote a figure
 * the /wedding-{category}/{city} pages contradict.
 */

type Band = { key: string; label: string; cents: number };
type CityCost = { noun: string; display: string };
type City = { slug: string; name: string; index: number; costs: CityCost[] };

export type BudgetModel = {
    fixed_cents: number;
    per_guest_cents: number;
    split: { label: string; share: number }[];
    bands: Band[];
    cities: City[];
};

const money = (cents: number) =>
    '$' + Math.round(cents / 100).toLocaleString('en-CA');

/** Categories worth surfacing a real market range for, in display order. */
const ANCHOR_NOUNS = ['Wedding Venues', 'Wedding Caterers', 'Wedding Photographers'];

/** Matches what Inertia's Link accepts, so wayfinder route objects pass through. */
type LinkHref = ComponentProps<typeof Link>['href'];

export function BudgetInstrument({ model, ctaHref }: { model: BudgetModel; ctaHref: LinkHref }) {
    const [total, setTotal] = useState(30000);
    const [citySlug, setCitySlug] = useState(model.cities[0]?.slug ?? 'toronto');
    const [guests, setGuests] = useState(100);

    const city = useMemo(
        () => model.cities.find((c) => c.slug === citySlug) ?? model.cities[0],
        [model.cities, citySlug],
    );

    const { rows, verdict, typical } = useMemo(() => {
        const cents = Math.max(0, total) * 100;

        const rows = model.split.map((s) => ({
            label: s.label,
            share: s.share,
            amount: Math.round(cents * s.share),
        }));

        const typical = Math.round(
            (model.fixed_cents + model.per_guest_cents * Math.max(0, guests)) * (city?.index ?? 1),
        );
        const ratio = typical > 0 ? cents / typical : 0;

        const verdict =
            ratio < 0.8
                ? ({ key: 'tight', line: 'On the tight side for this guest count — we’ll help you prioritise.' } as const)
                : ratio > 1.25
                  ? ({ key: 'generous', line: 'Room to spare. You can invest more where it matters most.' } as const)
                  : ({ key: 'comfortable', line: 'A comfortable fit for a wedding this size.' } as const);

        return { rows, verdict, typical };
    }, [total, guests, city, model]);

    const anchors = (city?.costs ?? []).filter((c) => ANCHOR_NOUNS.includes(c.noun));
    const largest = Math.max(...rows.map((r) => r.amount), 1);

    return (
        <div className="grid gap-px overflow-hidden border border-[#0f1c17]/12 bg-[#0f1c17]/12 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1fr)]">
            {/* ── Controls ─────────────────────────────────────────────── */}
            <div className="bg-[#f1f0ea] p-7 md:p-10">
                <p className="eyebrow text-[#1b4638]">Bring your budget</p>
                <h1 className="mt-4 font-['Newsreader'] text-[clamp(2.1rem,4.6vw,3.4rem)] leading-[1.02] font-light text-[#0f1c17]">
                    We&rsquo;ll make it{' '}
                    <em className="font-normal text-[#1b4638]">work.</em>
                </h1>
                <p className="mt-4 max-w-sm text-[15px] leading-relaxed text-[#4b5850]">
                    Real Ontario prices, by city. Change a number and watch the whole
                    wedding re-plan itself.
                </p>

                <div className="mt-8 space-y-6">
                    <div>
                        <div className="flex items-baseline justify-between">
                            <label htmlFor="hero-budget" className="eyebrow text-[#4b5850]">
                                Total budget
                            </label>
                            <output
                                htmlFor="hero-budget"
                                className="tabular font-['Newsreader'] text-2xl text-[#0f1c17]"
                            >
                                {money(total * 100)}
                            </output>
                        </div>
                        <input
                            id="hero-budget"
                            type="range"
                            min={8000}
                            max={90000}
                            step={1000}
                            value={total}
                            onChange={(e) => setTotal(+e.target.value)}
                            className="mt-3 h-1 w-full cursor-pointer appearance-none rounded-none bg-[#0f1c17]/15 accent-[#c4502e]"
                            aria-describedby="hero-verdict"
                        />
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label htmlFor="hero-city" className="eyebrow text-[#4b5850]">
                                City
                            </label>
                            <select
                                id="hero-city"
                                value={citySlug}
                                onChange={(e) => setCitySlug(e.target.value)}
                                className="mt-2 w-full border-b border-[#0f1c17]/25 bg-transparent py-2 font-['Newsreader'] text-lg text-[#0f1c17] focus:border-[#c4502e] focus:outline-none"
                            >
                                {model.cities.map((c) => (
                                    <option key={c.slug} value={c.slug}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="hero-guests" className="eyebrow text-[#4b5850]">
                                Guests
                            </label>
                            <input
                                id="hero-guests"
                                type="number"
                                min={10}
                                max={400}
                                step={5}
                                value={guests}
                                onChange={(e) => setGuests(+e.target.value)}
                                className="tabular mt-2 w-full border-b border-[#0f1c17]/25 bg-transparent py-2 font-['Newsreader'] text-lg text-[#0f1c17] focus:border-[#c4502e] focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <p
                    id="hero-verdict"
                    aria-live="polite"
                    className="mt-7 border-l-2 border-[#c4502e] pl-4 text-[14px] leading-relaxed text-[#4b5850]"
                >
                    <span className="text-[#0f1c17]">{verdict.line}</span>{' '}
                    A {guests}-guest wedding in {city?.name} typically runs about{' '}
                    <span className="tabular whitespace-nowrap text-[#0f1c17]">{money(typical)}</span>.
                </p>

                <Link
                    href={ctaHref}
                    className="cta-press group mt-7 inline-flex items-center gap-3 px-8 py-4 text-[11px] font-semibold tracking-[0.22em] uppercase"
                >
                    <span className="relative z-10 flex items-center gap-3">
                        Save this plan — free
                        <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                    </span>
                </Link>
                <p className="mt-3 text-[11px] tracking-[0.14em] text-[#4b5850]/70 uppercase">
                    No credit card · Estimates, not quotes
                </p>
            </div>

            {/* ── Live allocation ──────────────────────────────────────── */}
            <div className="bg-[#fafaf6] p-7 md:p-10">
                <div className="flex items-baseline justify-between border-b border-[#0f1c17]/12 pb-3">
                    <p className="eyebrow text-[#4b5850]">Your plan · {city?.name}</p>
                    <p className="eyebrow text-[#4b5850]">Allocation</p>
                </div>

                <ul className="mt-1">
                    {rows.map((r) => (
                        <li
                            key={r.label}
                            className="group relative border-b border-[#0f1c17]/8 py-2.5"
                        >
                            {/* Proportional bar — the shape of the budget, read at a glance. */}
                            <span
                                aria-hidden
                                className="absolute inset-y-0 left-0 bg-[#1b4638]/8 transition-[width] duration-500 ease-out"
                                style={{ width: `${(r.amount / largest) * 100}%` }}
                            />
                            <span className="relative flex items-baseline justify-between gap-4">
                                <span className="text-[14px] text-[#0f1c17]">{r.label}</span>
                                <span className="flex items-baseline gap-3">
                                    <span className="tabular text-[11px] text-[#4b5850]/70">
                                        {Math.round(r.share * 100)}%
                                    </span>
                                    <span className="tabular w-20 text-right text-[14px] text-[#0f1c17]">
                                        {money(r.amount)}
                                    </span>
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>

                {anchors.length > 0 && (
                    <div className="mt-7">
                        <p className="eyebrow text-[#4b5850]">
                            What that buys in {city?.name}
                        </p>
                        <dl className="mt-3 space-y-1.5">
                            {anchors.map((a) => (
                                <div
                                    key={a.noun}
                                    className="flex items-baseline justify-between gap-4 text-[13px]"
                                >
                                    <dt className="text-[#4b5850]">{a.noun}</dt>
                                    <dd className="tabular text-[#0f1c17]">{a.display}</dd>
                                </div>
                            ))}
                        </dl>
                        <p className="mt-4 text-[12px] leading-relaxed text-[#4b5850]/75">
                            Typical market ranges for {city?.name}, from the same data behind
                            our{' '}
                            <Link href="/wedding-venues" className="link-draw text-[#1b4638]">
                                venue
                            </Link>{' '}
                            and{' '}
                            <Link href="/wedding-photographers" className="link-draw text-[#1b4638]">
                                photographer
                            </Link>{' '}
                            guides.
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}

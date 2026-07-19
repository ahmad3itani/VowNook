import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    Check,
    Gift,
    Hotel,
    Plane,
    RotateCcw,
    Sparkles,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Day = { title: string; plan: string; spend_cents: number };
type Tier = 'essential' | 'signature' | 'dream';
type LivePrice = {
    found: boolean;
    price_cents: number | null;
    currency: string;
};
type Live = { configured: boolean; flight: LivePrice; hotel: LivePrice } | null;
type Experience = {
    name: string;
    blurb: string;
    est_cents: number;
    url: string;
};
type Pkg = {
    tier: Tier;
    destination: string;
    airport: string;
    why: string;
    hotel_name: string;
    flight_cents: number;
    hotel_cents: number;
    activities_cents: number;
    food_cents: number;
    total_cents: number;
    nights: number;
    days: Day[];
};

type PageProps = {
    preferences: {
        vibe?: string | null;
        budget?: number | null;
        departure?: string | null;
        interests?: string | null;
    };
    dates: { start: string | null; end: string | null };
    packages: Pkg[];
    chosen_tier: Tier | null;
    stays_url: string | null;
    flights_url: string | null;
    live: Live;
    experiences: Experience[];
    registry_added: boolean;
    experiences_partner: string;
    affiliate_partner: string;
    flights_partner: string;
    ai_enabled: boolean;
};

const TIER_META: Record<
    Tier,
    { label: string; blurb: string; featured?: boolean }
> = {
    essential: { label: 'Essential', blurb: 'Comfortably under budget' },
    signature: {
        label: 'Signature',
        blurb: 'Right at your budget — your best fit',
        featured: true,
    },
    dream: { label: 'Dream', blurb: 'An aspirational stretch' },
};

const money = (cents: number) =>
    (cents / 100).toLocaleString('en-CA', {
        style: 'currency',
        currency: 'CAD',
        maximumFractionDigits: 0,
    });

export default function HoneymoonIndex({
    preferences,
    dates,
    packages,
    chosen_tier,
    stays_url,
    flights_url,
    live,
    experiences,
    registry_added,
    experiences_partner,
    affiliate_partner,
    flights_partner,
    ai_enabled,
}: PageProps) {
    const brief = useForm({
        vibe: preferences.vibe ?? '',
        budget: preferences.budget ? String(preferences.budget) : '',
        departure: preferences.departure ?? '',
        start_date: dates.start ?? '',
        end_date: dates.end ?? '',
        interests: preferences.interests ?? '',
    });

    const [choosing, setChoosing] = useState<Tier | null>(null);

    const chosen = chosen_tier
        ? (packages.find((p) => p.tier === chosen_tier) ?? null)
        : null;

    function craft(e: FormEvent) {
        e.preventDefault();
        brief.post('/honeymoon/generate', {
            preserveScroll: true,
            onError: (errs) =>
                toast.error(
                    errs.ai ?? 'Could not craft a plan. Please try again.',
                ),
        });
    }

    function choose(tier: Tier) {
        setChoosing(tier);
        router.put(
            '/honeymoon/choose',
            { tier },
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(
                        'Locked in — book your flight & hotel below.',
                    ),
                onFinish: () => setChoosing(null),
            },
        );
    }

    function startOver() {
        if (!confirm('Start a new brief? This clears the crafted options.'))
            return;
        router.delete('/honeymoon', { preserveScroll: true });
    }

    return (
        <>
            <Head title="Honeymoon concierge" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Honeymoon concierge"
                    description="Tell us your dream and budget — we’ll craft three ready-to-book honeymoons to choose from."
                />

                {packages.length === 0 ? (
                    <Brief
                        brief={brief}
                        aiEnabled={ai_enabled}
                        onSubmit={craft}
                    />
                ) : (
                    <>
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Three ways to honeymoon, tailored to your brief.
                                Pick the one you love.
                            </p>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={startOver}
                                className="text-muted-foreground"
                            >
                                <RotateCcw className="size-3.5" /> New brief
                            </Button>
                        </div>

                        <div className="grid gap-4 lg:grid-cols-3">
                            {packages.map((p) => (
                                <PackageCard
                                    key={p.tier}
                                    pkg={p}
                                    chosen={chosen_tier === p.tier}
                                    choosing={choosing === p.tier}
                                    disabled={choosing !== null}
                                    onChoose={() => choose(p.tier)}
                                />
                            ))}
                        </div>

                        {chosen && (
                            <ChosenBooking
                                pkg={chosen}
                                staysUrl={stays_url}
                                flightsUrl={flights_url}
                                live={live}
                                experiences={experiences}
                                registryAdded={registry_added}
                                experiencesPartner={experiences_partner}
                                affiliatePartner={affiliate_partner}
                                flightsPartner={flights_partner}
                            />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function Brief({
    brief,
    aiEnabled,
    onSubmit,
}: {
    brief: ReturnType<typeof useForm<Record<string, string>>>;
    aiEnabled: boolean;
    onSubmit: (e: FormEvent) => void;
}) {
    return (
        <Card className="border-[#1b4638]/25 bg-[#f7f7f2]">
            <CardContent className="py-6">
                <form onSubmit={onSubmit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="vibe">
                            What’s your dream honeymoon?
                        </Label>
                        <Textarea
                            id="vibe"
                            rows={2}
                            value={brief.data.vibe}
                            onChange={(e) =>
                                brief.setData('vibe', e.target.value)
                            }
                            placeholder="e.g. a relaxing beach getaway with great food and a little adventure"
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="budget">Total budget (CAD)</Label>
                            <Input
                                id="budget"
                                type="number"
                                min={0}
                                value={brief.data.budget}
                                onChange={(e) =>
                                    brief.setData('budget', e.target.value)
                                }
                                placeholder="e.g. 9000"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="departure">Flying from</Label>
                            <Input
                                id="departure"
                                value={brief.data.departure}
                                onChange={(e) =>
                                    brief.setData('departure', e.target.value)
                                }
                                placeholder="e.g. Toronto"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="start">Leaving</Label>
                            <Input
                                id="start"
                                type="date"
                                value={brief.data.start_date}
                                onChange={(e) =>
                                    brief.setData('start_date', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end">Returning</Label>
                            <Input
                                id="end"
                                type="date"
                                value={brief.data.end_date}
                                onChange={(e) =>
                                    brief.setData('end_date', e.target.value)
                                }
                            />
                            {brief.errors.end_date && (
                                <p className="text-xs text-destructive">
                                    {brief.errors.end_date}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="interests">
                            Anything you love? (optional)
                        </Label>
                        <Input
                            id="interests"
                            value={brief.data.interests}
                            onChange={(e) =>
                                brief.setData('interests', e.target.value)
                            }
                            placeholder="snorkelling, fine dining, hiking, spas…"
                        />
                    </div>

                    {aiEnabled ? (
                        <div>
                            <Button
                                type="submit"
                                disabled={brief.processing}
                                className="bg-[#1b4638] hover:bg-[#16382c]"
                            >
                                {brief.processing ? (
                                    <Spinner />
                                ) : (
                                    <Sparkles className="size-4" />
                                )}
                                {brief.processing
                                    ? 'Crafting your honeymoons…'
                                    : 'Craft my honeymoon'}
                            </Button>
                            {brief.processing && (
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Designing three tailored options — this
                                    takes a few seconds.
                                </p>
                            )}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            The AI concierge isn’t available on this server yet.
                        </p>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}

function PackageCard({
    pkg,
    chosen,
    choosing,
    disabled,
    onChoose,
}: {
    pkg: Pkg;
    chosen: boolean;
    choosing: boolean;
    disabled: boolean;
    onChoose: () => void;
}) {
    const meta = TIER_META[pkg.tier];
    const nights = pkg.nights > 0 ? pkg.nights : 0;
    const perNight = nights > 0 ? Math.round(pkg.total_cents / nights) : 0;

    return (
        <Card
            className={`relative overflow-visible ${meta.featured ? 'border-2 border-[#1b4638]' : ''}`}
        >
            {meta.featured && (
                <span className="absolute -top-2.5 left-1/2 -translate-x-1/2 rounded-full bg-[#1b4638] px-3 py-0.5 text-[10px] font-medium text-white">
                    Best for you
                </span>
            )}
            <CardContent className="flex h-full flex-col gap-2.5 py-5">
                <p className="text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase">
                    {meta.label}
                </p>
                <p className="font-serif text-xl text-[#12211b]">
                    {pkg.destination}
                </p>
                <p className="text-xs leading-relaxed text-muted-foreground">
                    {pkg.why}
                </p>

                <div className="mt-1 flex flex-col gap-1.5 border-t pt-3 text-sm">
                    <p className="flex items-center gap-2">
                        <Plane className="size-3.5 shrink-0 text-[#1b4638]" />
                        <span className="text-muted-foreground">
                            {pkg.airport} ·
                        </span>
                        <span className="font-medium">
                            {money(pkg.flight_cents)}
                        </span>
                    </p>
                    <p className="flex items-start gap-2">
                        <Hotel className="mt-0.5 size-3.5 shrink-0 text-[#1b4638]" />
                        <span className="min-w-0">
                            <span className="block truncate">
                                {pkg.hotel_name}
                            </span>
                            <span className="font-medium">
                                {money(pkg.hotel_cents)}
                            </span>
                        </span>
                    </p>
                    {nights > 0 && (
                        <p className="flex items-center gap-2 text-muted-foreground">
                            <CalendarDays className="size-3.5 shrink-0 text-[#1b4638]" />
                            {nights}-night trip · ~{money(perNight)}/night
                        </p>
                    )}
                </div>

                <div className="mt-auto flex items-baseline justify-between pt-3">
                    <span className="text-xs text-muted-foreground">Total</span>
                    <span className="font-serif text-2xl text-[#1b4638]">
                        {money(pkg.total_cents)}
                    </span>
                </div>

                <Button
                    onClick={onChoose}
                    disabled={disabled}
                    variant={chosen ? 'outline' : 'default'}
                    className={
                        chosen
                            ? 'border-[#1b4638] text-[#1b4638]'
                            : 'bg-[#1b4638] hover:bg-[#16382c]'
                    }
                >
                    {choosing ? (
                        <>
                            <Spinner /> Planning your days…
                        </>
                    ) : chosen ? (
                        <>
                            <Check className="size-4" /> Chosen
                        </>
                    ) : (
                        'Choose this'
                    )}
                </Button>
            </CardContent>
        </Card>
    );
}

function LivePrice({
    label,
    live,
}: {
    label: string;
    live: LivePrice | undefined;
}) {
    if (!live?.found || live.price_cents === null) return null;
    return (
        <p className="text-sm">
            <span className="text-muted-foreground">{label} from </span>
            <span className="font-semibold text-[#1b4638]">
                {money(live.price_cents)}
            </span>
            <span className="ml-1.5 rounded-full bg-[#1b4638]/10 px-1.5 py-0.5 text-[10px] font-medium tracking-wide text-[#1b4638] uppercase">
                live
            </span>
        </p>
    );
}

function ChosenBooking({
    pkg,
    staysUrl,
    flightsUrl,
    live,
    experiences,
    registryAdded,
    experiencesPartner,
    affiliatePartner,
    flightsPartner,
}: {
    pkg: Pkg;
    staysUrl: string | null;
    flightsUrl: string | null;
    live: Live;
    experiences: Experience[];
    registryAdded: boolean;
    experiencesPartner: string;
    affiliatePartner: string;
    flightsPartner: string;
}) {
    function fundWithRegistry() {
        router.post(
            '/honeymoon/registry',
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(
                        'Added to your registry — guests can chip in now.',
                    ),
            },
        );
    }

    const breakdown = [
        { label: 'Flights', cents: pkg.flight_cents },
        { label: 'Hotel', cents: pkg.hotel_cents },
        { label: 'Activities', cents: pkg.activities_cents },
        { label: 'Food & dining', cents: pkg.food_cents },
    ].filter((b) => b.cents > 0);

    return (
        <Card className="border-[#1b4638]/30">
            <CardContent className="flex flex-col gap-5 py-6">
                <div>
                    <p className="text-xs tracking-[0.2em] text-[#1b4638] uppercase">
                        Your honeymoon
                    </p>
                    <h2 className="font-serif text-3xl text-[#12211b]">
                        {pkg.destination}
                    </h2>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        {pkg.why}
                    </p>
                </div>

                {/* Day by day */}
                {pkg.days.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <h3 className="text-sm font-medium">Your day-by-day</h3>
                        <ol className="flex flex-col gap-2">
                            {pkg.days.map((d, i) => (
                                <li
                                    key={i}
                                    className="flex gap-3 rounded-lg border p-3"
                                >
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-[#1b4638]/10 text-xs font-semibold text-[#1b4638]">
                                        {i + 1}
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium">
                                            {d.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {d.plan}
                                        </p>
                                    </div>
                                    {d.spend_cents > 0 && (
                                        <span className="shrink-0 text-sm text-[#1b4638]">
                                            {money(d.spend_cents)}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </div>
                )}

                {/* Experiences */}
                {experiences.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <h3 className="text-sm font-medium">
                            Experiences to book
                        </h3>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {experiences.map((x, i) => (
                                <div
                                    key={i}
                                    className="flex flex-col gap-1 rounded-lg border p-3"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <p className="text-sm font-medium">
                                            {x.name}
                                        </p>
                                        {x.est_cents > 0 && (
                                            <span className="shrink-0 text-sm text-[#1b4638]">
                                                {money(x.est_cents)}
                                            </span>
                                        )}
                                    </div>
                                    {x.blurb && (
                                        <p className="text-xs text-muted-foreground">
                                            {x.blurb}
                                        </p>
                                    )}
                                    <a
                                        href={x.url}
                                        target="_blank"
                                        rel="noopener noreferrer sponsored"
                                        className="mt-1 w-fit text-xs font-medium text-[#1b4638] hover:underline"
                                    >
                                        Find &amp; book →
                                    </a>
                                </div>
                            ))}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Experiences via {experiencesPartner}. VowNook may
                            earn a small commission if you book.
                        </p>
                    </div>
                )}

                {/* Budget breakdown */}
                <div className="flex flex-col gap-1.5 rounded-xl border p-4">
                    <h3 className="mb-1 text-sm font-medium">What it costs</h3>
                    {breakdown.map((b) => (
                        <div
                            key={b.label}
                            className="flex items-center justify-between text-sm"
                        >
                            <span className="text-muted-foreground">
                                {b.label}
                            </span>
                            <span>{money(b.cents)}</span>
                        </div>
                    ))}
                    <div className="mt-1 flex items-center justify-between border-t pt-2 text-sm font-semibold">
                        <span>Total</span>
                        <span className="text-[#1b4638]">
                            {money(pkg.total_cents)}
                        </span>
                    </div>
                </div>

                {/* Registry tie-in — the unique bit: guests fund the real trip */}
                <div className="flex flex-col items-start gap-2 rounded-xl border border-[#1b4638]/30 bg-[#f7f7f2] p-4">
                    <h3 className="text-sm font-medium">
                        Let your guests fund it
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        Turn this honeymoon into registry gifts — flights, your
                        stay, and each experience — so guests can chip in toward
                        the real trip.
                    </p>
                    {registryAdded ? (
                        <a
                            href="/registry"
                            className="inline-flex items-center gap-1.5 text-sm font-medium text-[#1b4638] hover:underline"
                        >
                            <Check className="size-4" /> Added to your registry
                            — manage it
                        </a>
                    ) : (
                        <Button
                            onClick={fundWithRegistry}
                            className="bg-[#1b4638] hover:bg-[#16382c]"
                        >
                            <Gift className="size-4" /> Add to our registry
                        </Button>
                    )}
                </div>

                {/* Book it */}
                <div className="flex flex-col gap-3">
                    <h3 className="text-sm font-medium">Book it</h3>
                    {flightsUrl && (
                        <div className="flex flex-col gap-1.5">
                            <LivePrice label="Flights" live={live?.flight} />
                            <Button
                                asChild
                                className="w-fit bg-[#1b4638] hover:bg-[#16382c]"
                            >
                                <a
                                    href={flightsUrl}
                                    target="_blank"
                                    rel="noopener noreferrer sponsored"
                                >
                                    <Plane className="size-4" /> Search & book
                                    flights
                                </a>
                            </Button>
                        </div>
                    )}
                    {staysUrl && (
                        <div className="flex flex-col gap-1.5">
                            <LivePrice label="Stays" live={live?.hotel} />
                            <div className="overflow-hidden rounded-xl border">
                                <iframe
                                    src={staysUrl}
                                    title={`Stays in ${pkg.destination}`}
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                    className="h-[440px] w-full border-0"
                                    allow="geolocation"
                                />
                            </div>
                        </div>
                    )}
                    {!staysUrl && !flightsUrl && (
                        <p className="text-sm text-muted-foreground">
                            Booking links appear here once our travel partners
                            are connected.
                        </p>
                    )}
                    <p className="text-xs leading-relaxed text-muted-foreground">
                        Flights via {flightsPartner}, stays via{' '}
                        {affiliatePartner}. VowNook may earn a small commission
                        if you book — at no extra cost to you.
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

HoneymoonIndex.layout = {
    breadcrumbs: [{ title: 'Honeymoon', href: '/honeymoon' }],
};

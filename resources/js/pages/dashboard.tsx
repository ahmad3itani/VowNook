import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { animate, motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { formatMoney } from '@/lib/format';
import InputError from '@/components/input-error';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    AlertTriangle,
    Armchair,
    ArrowRight,
    Briefcase,
    CalendarClock,
    CalendarHeart,
    Check,
    CheckCircle2,
    Circle,
    Clock,
    ListChecks,
    MessageSquare,
    UtensilsCrossed,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type OverdueTask = { id: number; title: string; priority: string; days_overdue: number };
type UpcomingTask = { id: number; title: string; priority: string; due_date: string };
type UnbookedVendor = { id: number; name: string; category: string; status: string };
type Milestone = { key: string; label: string; done: boolean; href: string };

type DashboardProps = {
    milestones?: Milestone[];
    summary: {
        name: string;
        event_date: string | null;
        days_until: number | null;
    } | null;
    guests?: { total: number; attending: number; declined: number; maybe: number; pending: number };
    budget?: { estimated: number; actual: number; paid: number };
    tasks?: { total: number; completed: number; outstanding: number; overdue: number };
    counts?: { vendors: number; events: number; tables: number; seated: number };
    attention?: {
        overdue_tasks: OverdueTask[];
        upcoming_tasks: UpcomingTask[];
        unbooked_vendors: UnbookedVendor[];
        no_meal_count: number;
        unseated_count: number;
    };
    quotes?: {
        open: number;
        offers_awaiting: number;
        items: { id: number; vendor_name: string | null }[];
    };
};

function StatCard({
    title,
    icon: Icon,
    href,
    children,
}: {
    title: string;
    icon: LucideIcon;
    href: string;
    children: React.ReactNode;
}) {
    return (
        <Link href={href} className="group">
            <Card className="lift h-full transition-all group-hover:border-[#775a19]/50 group-hover:shadow-atelier">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-xs font-medium tracking-[0.15em] text-muted-foreground uppercase">
                        {title}
                    </CardTitle>
                    <span className="flex size-7 items-center justify-center rounded-full bg-[#775a19]/10 text-[#775a19] transition-colors group-hover:bg-[#775a19]/15">
                        <Icon className="size-3.5" />
                    </span>
                </CardHeader>
                <CardContent>{children}</CardContent>
            </Card>
        </Link>
    );
}

function Bar({ value, total, danger }: { value: number; total: number; danger?: boolean }) {
    const pct = total > 0 ? Math.min(100, Math.round((value / total) * 100)) : 0;
    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
            <div
                className={`h-full rounded-full ${danger ? 'bg-destructive' : 'bg-gradient-to-r from-[#8a651c] to-[#c5a059]'}`}
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}

/** Animated count-up so the hero numbers feel alive on load. */
function CountUp({ value, duration = 1.2 }: { value: number; duration?: number }) {
    const [display, setDisplay] = useState(0);

    useEffect(() => {
        const controls = animate(0, value, {
            duration,
            ease: [0.22, 1, 0.36, 1],
            onUpdate: (v) => setDisplay(Math.round(v)),
        });
        return () => controls.stop();
    }, [value, duration]);

    return <>{display}</>;
}

/** The planning-progress ring: an animated gold arc with the % in the centre. */
function ProgressRing({ pct }: { pct: number }) {
    const R = 52;
    const C = 2 * Math.PI * R;

    return (
        <div className="relative size-36 shrink-0">
            <svg viewBox="0 0 120 120" className="size-full -rotate-90">
                <circle cx="60" cy="60" r={R} fill="none" stroke="#191613" strokeOpacity="0.08" strokeWidth="7" />
                <motion.circle
                    cx="60"
                    cy="60"
                    r={R}
                    fill="none"
                    stroke="url(#ring-gold)"
                    strokeWidth="7"
                    strokeLinecap="round"
                    strokeDasharray={C}
                    initial={{ strokeDashoffset: C }}
                    animate={{ strokeDashoffset: C - (C * pct) / 100 }}
                    transition={{ duration: 1.4, ease: [0.22, 1, 0.36, 1], delay: 0.3 }}
                />
                <defs>
                    <linearGradient id="ring-gold" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stopColor="#c5a059" />
                        <stop offset="100%" stopColor="#8a651c" />
                    </linearGradient>
                </defs>
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="font-serif text-3xl font-light text-[#8a651c]">
                    <CountUp value={pct} />%
                </span>
                <span className="text-[9px] tracking-[0.22em] text-muted-foreground uppercase">planned</span>
            </div>
        </div>
    );
}

function timeGreeting(): string {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
}

const PRIORITY_DOT: Record<string, string> = {
    high: 'bg-destructive',
    medium: 'bg-amber-500',
    low: 'bg-muted-foreground',
};

const STATUS_BADGE: Record<string, string> = {
    Researching: 'bg-muted text-muted-foreground',
    Contacted: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    Quoted: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
};

function CreateWeddingForm() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        event_date: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/weddings');
    }

    return (
        <form onSubmit={submit} className="mt-4 w-full max-w-sm space-y-4 text-left">
            <div className="grid gap-2">
                <Label htmlFor="new-wedding-name">Wedding name</Label>
                <Input
                    id="new-wedding-name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Olivia & Noah"
                    required
                />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="new-wedding-date">Event date (optional)</Label>
                <Input
                    id="new-wedding-date"
                    type="date"
                    value={data.event_date}
                    onChange={(e) => setData('event_date', e.target.value)}
                />
                <InputError message={errors.event_date} />
            </div>
            <Button type="submit" disabled={processing} className="w-full">
                {processing ? 'Creating…' : 'Create my wedding'}
            </Button>
        </form>
    );
}

function formatDate(dateStr: string) {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-CA', {
        month: 'short',
        day: 'numeric',
    });
}

export default function Dashboard({ milestones = [], summary, guests, budget, tasks, counts, attention, quotes }: DashboardProps) {
    const { auth } = usePage().props;
    const firstName = ((auth?.user?.name as string | undefined) ?? '').split(' ')[0];
    if (!summary || !guests || !budget || !tasks || !counts || !attention) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="flex flex-1 flex-col items-center justify-center gap-2 p-12 text-center">
                    <CalendarHeart className="size-10 text-[#775a19]" />
                    <h1 className="font-serif text-2xl">No wedding yet</h1>
                    <p className="text-muted-foreground">Create your wedding to start planning.</p>
                    <CreateWeddingForm />
                </div>
            </>
        );
    }

    const replied = guests.attending + guests.declined + guests.maybe;
    const days = summary.days_until;
    const rsvpPct = guests.total > 0 ? Math.round((replied / guests.total) * 100) : 0;

    const offersAwaiting = quotes?.offers_awaiting ?? 0;

    const doneCount = milestones.filter((m) => m.done).length;
    const progressPct = milestones.length > 0 ? Math.round((doneCount / milestones.length) * 100) : 0;
    const nextStep = milestones.find((m) => !m.done) ?? null;

    const hasAttention =
        attention.overdue_tasks.length > 0 ||
        attention.unbooked_vendors.length > 0 ||
        attention.no_meal_count > 0 ||
        attention.unseated_count > 0 ||
        offersAwaiting > 0;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4">

                {/* Hero — greeting, countdown, planning progress */}
                <Reveal y={16}>
                    <div className="relative overflow-hidden rounded-2xl border border-[#8a651c]/20 bg-gradient-to-br from-[#fdf8ee] via-white to-[#f6eedd] p-6 shadow-atelier md:p-8">
                        {days !== null && days > 0 && (
                            <span
                                aria-hidden
                                className="pointer-events-none absolute -right-2 -bottom-12 hidden font-serif text-[11rem] leading-none font-light text-[#8a651c]/[0.07] select-none lg:block"
                            >
                                {days}
                            </span>
                        )}

                        <div className="relative flex flex-wrap items-center justify-between gap-8">
                            <div className="min-w-0 max-w-2xl">
                                <p className="text-[11px] tracking-[0.25em] text-[#8a651c] uppercase">
                                    {timeGreeting()}
                                    {firstName ? `, ${firstName}` : ''}
                                </p>
                                <h1 className="mt-1.5 font-serif text-4xl leading-tight font-light tracking-tight">{summary.name}</h1>

                                <p className="mt-3 text-sm text-muted-foreground">
                                    {days === null ? (
                                        'Set your wedding date to start the countdown.'
                                    ) : days > 0 ? (
                                        <>
                                            <span className="font-serif text-2xl text-[#8a651c]">
                                                <CountUp value={days} />
                                            </span>{' '}
                                            days to go
                                            {summary.event_date && (
                                                <> · {new Date(summary.event_date + 'T00:00:00').toLocaleDateString('en-CA', { month: 'long', day: 'numeric', year: 'numeric' })}</>
                                            )}
                                        </>
                                    ) : days === 0 ? (
                                        'Today is the day. Enjoy every minute.'
                                    ) : (
                                        `Married ${Math.abs(days)} days ago.`
                                    )}
                                    <span className="mx-2 text-border">|</span>
                                    {rsvpPct}% RSVP’d · {formatMoney(budget.paid * 100)} paid
                                </p>

                                {nextStep && (
                                    <Link
                                        href={nextStep.href}
                                        className="mt-5 inline-flex items-center gap-2 bg-[#191613] px-5 py-2.5 text-[11px] font-semibold tracking-[0.18em] text-[#faf6ef] uppercase transition-colors hover:bg-[#8a651c]"
                                    >
                                        Next step: {nextStep.label}
                                        <ArrowRight className="size-3.5" />
                                    </Link>
                                )}

                                {milestones.length > 0 && (
                                    <div className="mt-5 flex flex-wrap gap-1.5">
                                        {milestones.map((m) => (
                                            <Link
                                                key={m.key}
                                                href={m.href}
                                                className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs transition-colors ${
                                                    m.done
                                                        ? 'border-[#8a651c]/30 bg-[#8a651c]/10 text-[#8a651c]'
                                                        : 'border-border bg-white/60 text-muted-foreground hover:border-[#8a651c]/40 hover:text-[#8a651c]'
                                                }`}
                                            >
                                                {m.done ? <Check className="size-3" /> : <Circle className="size-2" />}
                                                {m.label}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {milestones.length > 0 && <ProgressRing pct={progressPct} />}
                        </div>
                    </div>
                </Reveal>

                {/* Module cards */}
                <Stagger className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StaggerItem><StatCard title="Guests" icon={Users} href="/guests">
                        <div className="font-serif text-3xl">{guests.total}</div>
                        <p className="text-xs text-muted-foreground">
                            {guests.attending} attending · {guests.pending} awaiting reply
                        </p>
                        <div className="mt-3">
                            <Bar value={replied} total={guests.total} />
                        </div>
                    </StatCard></StaggerItem>

                    <StaggerItem><StatCard title="Budget" icon={Wallet} href="/budget">
                        <div className="font-serif text-3xl">{formatMoney(budget.estimated * 100)}</div>
                        <p className="text-xs text-muted-foreground">
                            {formatMoney(budget.paid * 100)} paid of {formatMoney(budget.actual * 100)} actual
                        </p>
                        <div className="mt-3">
                            <Bar value={budget.paid} total={budget.actual} />
                        </div>
                    </StatCard></StaggerItem>

                    <StaggerItem><StatCard title="Tasks" icon={ListChecks} href="/checklist">
                        <div className="font-serif text-3xl">
                            {tasks.completed}
                            <span className="text-base text-muted-foreground">/{tasks.total}</span>
                        </div>
                        <p className={`text-xs ${tasks.overdue > 0 ? 'font-medium text-destructive' : 'text-muted-foreground'}`}>
                            {tasks.overdue > 0 ? `${tasks.overdue} overdue` : `${tasks.outstanding} outstanding`}
                        </p>
                        <div className="mt-3">
                            <Bar value={tasks.completed} total={tasks.total} danger={tasks.overdue > 0} />
                        </div>
                    </StatCard></StaggerItem>

                    <StaggerItem><StatCard title="Seating" icon={Armchair} href="/seating">
                        <div className="font-serif text-3xl">{counts.seated}</div>
                        <p className="text-xs text-muted-foreground">
                            seated across {counts.tables} {counts.tables === 1 ? 'table' : 'tables'}
                        </p>
                        <div className="mt-3">
                            <Bar value={counts.seated} total={guests.attending || guests.total} />
                        </div>
                    </StatCard></StaggerItem>
                </Stagger>

                {/* Needs Attention + Upcoming */}
                <div className="grid gap-4 lg:grid-cols-3">

                    {/* Needs Attention */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="font-serif text-xl font-medium">Needs attention</CardTitle>
                            <CardDescription>Action items across your wedding plan</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-5">
                            {!hasAttention && (
                                <div className="flex items-center gap-3 rounded-lg border border-border bg-muted/30 px-4 py-5">
                                    <CheckCircle2 className="size-5 shrink-0 text-[#775a19]" />
                                    <p className="text-sm text-muted-foreground">You're all caught up — nothing needs attention right now.</p>
                                </div>
                            )}

                            {/* Overdue tasks */}
                            {attention.overdue_tasks.length > 0 && (
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <AlertTriangle className="size-3.5 text-destructive" />
                                        <span className="text-xs font-semibold tracking-[0.15em] text-destructive uppercase">
                                            {attention.overdue_tasks.length} overdue {attention.overdue_tasks.length === 1 ? 'task' : 'tasks'}
                                        </span>
                                    </div>
                                    <div className="flex flex-col divide-y divide-border rounded-lg border border-border">
                                        {attention.overdue_tasks.map((t) => (
                                            <Link
                                                key={t.id}
                                                href="/checklist"
                                                className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/40"
                                            >
                                                <div className="flex items-center gap-2.5">
                                                    <span className={`size-2 shrink-0 rounded-full ${PRIORITY_DOT[t.priority] ?? 'bg-muted-foreground'}`} />
                                                    <span className="text-sm">{t.title}</span>
                                                </div>
                                                <span className="shrink-0 text-xs text-destructive">
                                                    {t.days_overdue}d overdue
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Unbooked vendors */}
                            {attention.unbooked_vendors.length > 0 && (
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <Briefcase className="size-3.5 text-amber-600" />
                                        <span className="text-xs font-semibold tracking-[0.15em] text-amber-600 uppercase">
                                            {attention.unbooked_vendors.length} {attention.unbooked_vendors.length === 1 ? 'vendor' : 'vendors'} not booked
                                        </span>
                                    </div>
                                    <div className="flex flex-col divide-y divide-border rounded-lg border border-border">
                                        {attention.unbooked_vendors.map((v) => (
                                            <Link
                                                key={v.id}
                                                href="/vendors"
                                                className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/40"
                                            >
                                                <div className="flex items-center gap-2.5">
                                                    <Circle className="size-2 shrink-0 text-muted-foreground" />
                                                    <span className="text-sm">{v.name}</span>
                                                    <span className="text-xs text-muted-foreground">· {v.category}</span>
                                                </div>
                                                <span className={`shrink-0 rounded px-2 py-0.5 text-[11px] font-medium ${STATUS_BADGE[v.status] ?? 'bg-muted text-muted-foreground'}`}>
                                                    {v.status}
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Marketplace offers awaiting a decision */}
                            {offersAwaiting > 0 && quotes && (
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <MessageSquare className="size-3.5 text-[#775a19]" />
                                        <span className="text-xs font-semibold tracking-[0.15em] text-[#775a19] uppercase">
                                            {offersAwaiting} {offersAwaiting === 1 ? 'offer' : 'offers'} awaiting your response
                                        </span>
                                    </div>
                                    <div className="flex flex-col divide-y divide-border rounded-lg border border-border">
                                        {quotes.items.map((q) => (
                                            <Link
                                                key={q.id}
                                                href={`/vendors/quotes/${q.id}`}
                                                className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/40"
                                            >
                                                <div className="flex items-center gap-2.5">
                                                    <Circle className="size-2 shrink-0 text-[#775a19]" />
                                                    <span className="text-sm">{q.vendor_name ?? 'Vendor'}</span>
                                                </div>
                                                <span className="shrink-0 rounded bg-[#775a19]/10 px-2 py-0.5 text-[11px] font-medium text-[#775a19]">
                                                    Review offer
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Guest gaps */}
                            {(attention.no_meal_count > 0 || attention.unseated_count > 0) && (
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <Users className="size-3.5 text-[#775a19]" />
                                        <span className="text-xs font-semibold tracking-[0.15em] text-[#775a19] uppercase">
                                            Guest gaps
                                        </span>
                                    </div>
                                    <div className="flex flex-col divide-y divide-border rounded-lg border border-border">
                                        {attention.no_meal_count > 0 && (
                                            <Link href="/guests" className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/40">
                                                <div className="flex items-center gap-2.5">
                                                    <UtensilsCrossed className="size-3.5 shrink-0 text-muted-foreground" />
                                                    <span className="text-sm">
                                                        {attention.no_meal_count} attending {attention.no_meal_count === 1 ? 'guest has' : 'guests have'} no meal choice
                                                    </span>
                                                </div>
                                                <span className="shrink-0 text-xs text-muted-foreground">→ Guests</span>
                                            </Link>
                                        )}
                                        {attention.unseated_count > 0 && (
                                            <Link href="/seating" className="flex items-center justify-between px-4 py-2.5 hover:bg-muted/40">
                                                <div className="flex items-center gap-2.5">
                                                    <Armchair className="size-3.5 shrink-0 text-muted-foreground" />
                                                    <span className="text-sm">
                                                        {attention.unseated_count} attending {attention.unseated_count === 1 ? 'guest is' : 'guests are'} unseated
                                                    </span>
                                                </div>
                                                <span className="shrink-0 text-xs text-muted-foreground">→ Seating</span>
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Upcoming tasks */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="font-serif text-xl font-medium">Coming up</CardTitle>
                            <CardDescription>Tasks due in the next 14 days</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {attention.upcoming_tasks.length === 0 ? (
                                <div className="flex flex-col items-center gap-2 py-6 text-center">
                                    <CalendarClock className="size-7 text-muted-foreground opacity-40" />
                                    <p className="text-sm text-muted-foreground">Nothing due in the next two weeks.</p>
                                </div>
                            ) : (
                                <div className="flex flex-col gap-1">
                                    {attention.upcoming_tasks.map((t, i) => {
                                        const showDate =
                                            i === 0 || t.due_date !== attention.upcoming_tasks[i - 1].due_date;
                                        return (
                                            <div key={t.id}>
                                                {showDate && (
                                                    <div className="mb-1 mt-3 flex items-center gap-2 first:mt-0">
                                                        <Clock className="size-3 text-[#775a19]" />
                                                        <span className="text-[10px] font-semibold tracking-[0.15em] text-[#775a19] uppercase">
                                                            {formatDate(t.due_date)}
                                                        </span>
                                                    </div>
                                                )}
                                                <Link
                                                    href="/checklist"
                                                    className="flex items-center gap-2.5 rounded px-2 py-1.5 hover:bg-muted/50"
                                                >
                                                    <span className={`size-2 shrink-0 rounded-full ${PRIORITY_DOT[t.priority] ?? 'bg-muted-foreground'}`} />
                                                    <span className="text-sm leading-snug">{t.title}</span>
                                                </Link>
                                            </div>
                                        );
                                    })}
                                    <Link
                                        href="/checklist"
                                        className="mt-3 block border-t border-border pt-3 text-center text-xs text-[#775a19] hover:underline"
                                    >
                                        View all tasks →
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* RSVP breakdown */}
                <Card>
                    <CardHeader>
                        <CardTitle className="font-serif text-xl font-medium">RSVP breakdown</CardTitle>
                        <CardDescription>{replied} of {guests.total} guests have replied</CardDescription>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        {[
                            { label: 'Attending', value: guests.attending, color: 'text-[#775a19]' },
                            { label: 'Maybe', value: guests.maybe, color: 'text-amber-600' },
                            { label: 'Declined', value: guests.declined, color: 'text-muted-foreground' },
                            { label: 'Pending', value: guests.pending, color: 'text-muted-foreground' },
                        ].map((s) => (
                            <Link key={s.label} href="/guests" className="group border border-border p-4 hover:border-[#775a19]/40">
                                <div className={`font-serif text-3xl ${s.color}`}>{s.value}</div>
                                <div className="mt-1 text-xs tracking-[0.15em] text-muted-foreground uppercase">{s.label}</div>
                            </Link>
                        ))}
                    </CardContent>
                </Card>

            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};

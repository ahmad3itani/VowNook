import { Head, Link } from '@inertiajs/react';
import {
    Armchair,
    Briefcase,
    CalendarClock,
    CalendarHeart,
    CheckCircle2,
    ListChecks,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type DashboardProps = {
    summary: {
        name: string;
        event_date: string | null;
        days_until: number | null;
    } | null;
    guests?: { total: number; attending: number; declined: number; maybe: number; pending: number };
    budget?: { estimated: number; actual: number; paid: number };
    tasks?: { total: number; completed: number; outstanding: number; overdue: number };
    counts?: { vendors: number; events: number; tables: number; seated: number };
};

const currency = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 0,
});

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
            <Card className="h-full transition-colors group-hover:border-[#775a19]/50">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-xs font-medium tracking-[0.15em] text-muted-foreground uppercase">
                        {title}
                    </CardTitle>
                    <Icon className="size-4 text-[#775a19]" />
                </CardHeader>
                <CardContent>{children}</CardContent>
            </Card>
        </Link>
    );
}

function Bar({ value, total }: { value: number; total: number }) {
    const pct = total > 0 ? Math.min(100, Math.round((value / total) * 100)) : 0;

    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
            <div className="h-full rounded-full bg-[#775a19]" style={{ width: `${pct}%` }} />
        </div>
    );
}

function QuickStat({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex flex-col items-center border border-border bg-card p-8 text-center">
            <span className="mb-2 text-xs tracking-[0.2em] text-muted-foreground uppercase">{label}</span>
            <p className="font-serif text-4xl text-foreground">{value}</p>
        </div>
    );
}

export default function Dashboard({ summary, guests, budget, tasks, counts }: DashboardProps) {
    if (!summary || !guests || !budget || !tasks || !counts) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="flex flex-1 flex-col items-center justify-center gap-2 p-12 text-center">
                    <CalendarHeart className="size-10 text-[#775a19]" />
                    <h1 className="font-serif text-2xl">No wedding yet</h1>
                    <p className="text-muted-foreground">Create or join a wedding to see your overview.</p>
                </div>
            </>
        );
    }

    const replied = guests.attending + guests.declined + guests.maybe;
    const days = summary.days_until;
    const rsvpPct = guests.total > 0 ? Math.round((replied / guests.total) * 100) : 0;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <p className="text-xs tracking-[0.2em] text-[#775a19] uppercase">Welcome back</p>
                    <h1 className="mt-1 font-serif text-3xl tracking-tight">{summary.name}</h1>
                    <div className="mt-3 h-px w-12 bg-[#775a19]/50" />
                </div>

                {/* Quick stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <QuickStat
                        label="Days to go"
                        value={
                            days === null ? '—' : days > 0 ? String(days) : days === 0 ? 'Today' : `${Math.abs(days)}d ago`
                        }
                    />
                    <QuickStat label="RSVPs received" value={`${rsvpPct}%`} />
                    <QuickStat label="Budget paid" value={currency.format(budget.paid)} />
                </div>

                {/* Module cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Guests" icon={Users} href="/guests">
                        <div className="font-serif text-3xl">{guests.total}</div>
                        <p className="text-xs text-muted-foreground">
                            {guests.attending} attending · {guests.pending} awaiting reply
                        </p>
                        <div className="mt-3">
                            <Bar value={replied} total={guests.total} />
                        </div>
                    </StatCard>

                    <StatCard title="Budget" icon={Wallet} href="/budget">
                        <div className="font-serif text-3xl">{currency.format(budget.estimated)}</div>
                        <p className="text-xs text-muted-foreground">
                            {currency.format(budget.paid)} paid of {currency.format(budget.actual)} actual
                        </p>
                        <div className="mt-3">
                            <Bar value={budget.paid} total={budget.actual} />
                        </div>
                    </StatCard>

                    <StatCard title="Tasks" icon={ListChecks} href="/checklist">
                        <div className="font-serif text-3xl">
                            {tasks.completed}
                            <span className="text-base text-muted-foreground">/{tasks.total}</span>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {tasks.outstanding} outstanding{tasks.overdue > 0 ? ` · ${tasks.overdue} overdue` : ''}
                        </p>
                        <div className="mt-3">
                            <Bar value={tasks.completed} total={tasks.total} />
                        </div>
                    </StatCard>

                    <StatCard title="Seating" icon={Armchair} href="/seating">
                        <div className="font-serif text-3xl">{counts.seated}</div>
                        <p className="text-xs text-muted-foreground">
                            seated across {counts.tables} {counts.tables === 1 ? 'table' : 'tables'}
                        </p>
                        <div className="mt-3">
                            <Bar value={counts.seated} total={guests.total} />
                        </div>
                    </StatCard>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="font-serif text-xl font-medium">RSVP breakdown</CardTitle>
                            <CardDescription>
                                {replied} of {guests.total} guests have replied
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {[
                                { label: 'Attending', value: guests.attending },
                                { label: 'Maybe', value: guests.maybe },
                                { label: 'Declined', value: guests.declined },
                                { label: 'Pending', value: guests.pending },
                            ].map((s) => (
                                <div key={s.label} className="border border-border p-4">
                                    <div className="font-serif text-3xl">{s.value}</div>
                                    <div className="mt-1 text-xs tracking-[0.15em] text-muted-foreground uppercase">
                                        {s.label}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="font-serif text-xl font-medium">Planning</CardTitle>
                            <CardDescription>Everything coming together</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4 text-sm">
                            {[
                                { href: '/vendors', icon: Briefcase, label: 'Vendors', value: String(counts.vendors) },
                                {
                                    href: '/timeline',
                                    icon: CalendarClock,
                                    label: 'Timeline events',
                                    value: String(counts.events),
                                },
                                {
                                    href: '/checklist',
                                    icon: CheckCircle2,
                                    label: 'Tasks done',
                                    value: `${tasks.completed}/${tasks.total}`,
                                },
                            ].map((row) => (
                                <Link
                                    key={row.href}
                                    href={row.href}
                                    className="flex items-center justify-between border-b border-border pb-3 last:border-0 last:pb-0 hover:text-[#775a19]"
                                >
                                    <span className="flex items-center gap-2 text-muted-foreground">
                                        <row.icon className="size-4 text-[#775a19]" /> {row.label}
                                    </span>
                                    <span className="font-serif text-lg">{row.value}</span>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};

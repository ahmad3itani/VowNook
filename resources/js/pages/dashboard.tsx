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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

type DashboardProps = {
    summary: {
        name: string;
        event_date: string | null;
        days_until: number | null;
    } | null;
    guests?: {
        total: number;
        attending: number;
        declined: number;
        maybe: number;
        pending: number;
    };
    budget?: { estimated: number; actual: number; paid: number };
    tasks?: {
        total: number;
        completed: number;
        outstanding: number;
        overdue: number;
    };
    counts?: {
        vendors: number;
        events: number;
        tables: number;
        seated: number;
    };
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
            <Card className="h-full transition-colors group-hover:border-primary/40">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        {title}
                    </CardTitle>
                    <Icon className="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>{children}</CardContent>
            </Card>
        </Link>
    );
}

function Bar({
    value,
    total,
    className,
}: {
    value: number;
    total: number;
    className: string;
}) {
    const pct =
        total > 0 ? Math.min(100, Math.round((value / total) * 100)) : 0;

    return (
        <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
            <div
                className={`h-full rounded-full ${className}`}
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}

export default function Dashboard({
    summary,
    guests,
    budget,
    tasks,
    counts,
}: DashboardProps) {
    if (!summary || !guests || !budget || !tasks || !counts) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="flex flex-1 flex-col items-center justify-center gap-2 p-12 text-center">
                    <CalendarHeart className="size-10 text-muted-foreground" />
                    <h1 className="text-xl font-semibold">No wedding yet</h1>
                    <p className="text-muted-foreground">
                        Create or join a wedding to see your overview.
                    </p>
                </div>
            </>
        );
    }

    const replied = guests.attending + guests.declined + guests.maybe;
    const days = summary.days_until;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold">{summary.name}</h1>
                    <p className="text-muted-foreground">
                        {days === null
                            ? 'Set your event date to start the countdown.'
                            : days > 0
                              ? `${days} ${days === 1 ? 'day' : 'days'} to go`
                              : days === 0
                                ? 'Today is the day! 🎉'
                                : `${Math.abs(days)} ${Math.abs(days) === 1 ? 'day' : 'days'} ago`}
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Guests" icon={Users} href="/guests">
                        <div className="text-2xl font-bold">{guests.total}</div>
                        <p className="text-xs text-muted-foreground">
                            {guests.attending} attending · {guests.pending}{' '}
                            awaiting reply
                        </p>
                        <div className="mt-3">
                            <Bar
                                value={replied}
                                total={guests.total}
                                className="bg-rose-400"
                            />
                        </div>
                    </StatCard>

                    <StatCard title="Budget" icon={Wallet} href="/budget">
                        <div className="text-2xl font-bold">
                            {currency.format(budget.estimated)}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {currency.format(budget.paid)} paid of{' '}
                            {currency.format(budget.actual)} actual
                        </p>
                        <div className="mt-3">
                            <Bar
                                value={budget.paid}
                                total={budget.actual}
                                className="bg-emerald-400"
                            />
                        </div>
                    </StatCard>

                    <StatCard title="Tasks" icon={ListChecks} href="/checklist">
                        <div className="text-2xl font-bold">
                            {tasks.completed}
                            <span className="text-base font-normal text-muted-foreground">
                                /{tasks.total}
                            </span>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {tasks.outstanding} outstanding
                            {tasks.overdue > 0
                                ? ` · ${tasks.overdue} overdue`
                                : ''}
                        </p>
                        <div className="mt-3">
                            <Bar
                                value={tasks.completed}
                                total={tasks.total}
                                className="bg-indigo-400"
                            />
                        </div>
                    </StatCard>

                    <StatCard title="Seating" icon={Armchair} href="/seating">
                        <div className="text-2xl font-bold">
                            {counts.seated}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            seated across {counts.tables}{' '}
                            {counts.tables === 1 ? 'table' : 'tables'}
                        </p>
                        <div className="mt-3">
                            <Bar
                                value={counts.seated}
                                total={guests.total}
                                className="bg-amber-400"
                            />
                        </div>
                    </StatCard>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>RSVP breakdown</CardTitle>
                            <CardDescription>
                                {replied} of {guests.total} guests have replied
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {[
                                {
                                    label: 'Attending',
                                    value: guests.attending,
                                    tone: 'text-emerald-500',
                                },
                                {
                                    label: 'Maybe',
                                    value: guests.maybe,
                                    tone: 'text-amber-500',
                                },
                                {
                                    label: 'Declined',
                                    value: guests.declined,
                                    tone: 'text-rose-500',
                                },
                                {
                                    label: 'Pending',
                                    value: guests.pending,
                                    tone: 'text-muted-foreground',
                                },
                            ].map((s) => (
                                <div
                                    key={s.label}
                                    className="rounded-lg border p-4"
                                >
                                    <div
                                        className={`text-2xl font-bold ${s.tone}`}
                                    >
                                        {s.value}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {s.label}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Planning</CardTitle>
                            <CardDescription>
                                Everything coming together
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3 text-sm">
                            <Link
                                href="/vendors"
                                className="flex items-center justify-between hover:text-primary"
                            >
                                <span className="flex items-center gap-2 text-muted-foreground">
                                    <Briefcase className="size-4" /> Vendors
                                </span>
                                <span className="font-semibold">
                                    {counts.vendors}
                                </span>
                            </Link>
                            <Link
                                href="/timeline"
                                className="flex items-center justify-between hover:text-primary"
                            >
                                <span className="flex items-center gap-2 text-muted-foreground">
                                    <CalendarClock className="size-4" />{' '}
                                    Timeline events
                                </span>
                                <span className="font-semibold">
                                    {counts.events}
                                </span>
                            </Link>
                            <Link
                                href="/checklist"
                                className="flex items-center justify-between hover:text-primary"
                            >
                                <span className="flex items-center gap-2 text-muted-foreground">
                                    <CheckCircle2 className="size-4" /> Tasks
                                    done
                                </span>
                                <span className="font-semibold">
                                    {tasks.completed}/{tasks.total}
                                </span>
                            </Link>
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

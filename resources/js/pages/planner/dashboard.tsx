import { Head, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    CalendarDays,
    ClipboardList,
    Inbox,
    Plus,
    Users,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { formatMoney } from '@/lib/format';

type WeddingCard = {
    id: number;
    slug: string;
    name: string;
    event_date: string | null;
    days_until: number | null;
    role: string | null;
    guests: { total: number; attending: number; pending: number };
    budget: { estimated_cents: number; paid_cents: number };
    tasks_outstanding: number;
    tasks_overdue: number;
    offers_awaiting: number;
};

type AttentionTask = {
    id: number;
    title: string;
    priority: string;
    days_overdue?: number;
    due_date?: string;
    wedding_id: number;
    wedding_name: string;
    wedding_slug: string;
};

type AttentionOffer = {
    id: number;
    vendor_name: string | null;
    wedding_id: number;
    wedding_name: string;
    wedding_slug: string;
};

type Props = {
    weddings: WeddingCard[];
    attention: {
        overdue_tasks: AttentionTask[];
        due_this_week: AttentionTask[];
        offers_awaiting: AttentionOffer[];
    };
    totals: {
        weddings: number;
        upcoming: number;
        overdue_tasks: number;
        offers_awaiting: number;
    };
    listing: {
        exists: boolean;
        status: string | null;
        status_label: string | null;
        edit_url: string | null;
        public_url: string | null;
    };
};

/** Switch the active wedding, then land on a page inside it. */
function openWedding(slug: string, target = '/dashboard?workspace=1') {
    router.post(`/weddings/${slug}/switch`, {}, {
        preserveScroll: true,
        onSuccess: () => router.visit(target),
    });
}

function NewWeddingDialog() {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        event_date: '',
        couple_email: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/weddings', { onSuccess: () => { reset(); setOpen(false); } });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus className="mr-1.5 size-4" />
                    New client wedding
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>New client wedding</DialogTitle>
                    <DialogDescription>
                        Creates a full planning workspace. You can invite the couple now or later
                        from Collaborators.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="wedding-name">Wedding name</Label>
                        <Input
                            id="wedding-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Olivia & Noah"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="wedding-date">Event date (optional)</Label>
                        <Input
                            id="wedding-date"
                            type="date"
                            value={data.event_date}
                            onChange={(e) => setData('event_date', e.target.value)}
                        />
                        <InputError message={errors.event_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="couple-email">Couple's email (optional)</Label>
                        <Input
                            id="couple-email"
                            type="email"
                            value={data.couple_email}
                            onChange={(e) => setData('couple_email', e.target.value)}
                            placeholder="They need an account first"
                        />
                        <InputError message={errors.couple_email} />
                    </div>
                    <Button type="submit" disabled={processing} className="w-full">
                        {processing ? 'Creating…' : 'Create workspace'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function StatChip({ icon: Icon, label, value }: { icon: typeof Users; label: string; value: number }) {
    return (
        <div className="flex items-center gap-3 rounded-xl border bg-card px-4 py-3">
            <Icon className="size-4 text-[#1b4638]" />
            <div>
                <p className="text-lg font-semibold leading-none">{value}</p>
                <p className="mt-1 text-xs text-muted-foreground">{label}</p>
            </div>
        </div>
    );
}

export default function PlannerDashboard({ weddings, attention, totals, listing }: Props) {
    const hasAttention =
        attention.overdue_tasks.length > 0 ||
        attention.due_this_week.length > 0 ||
        attention.offers_awaiting.length > 0;

    return (
        <div className="space-y-8 p-4 md:p-6">
            <Head title="Planner HQ" />

            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p className="text-[11px] tracking-[0.25em] text-[#1f5142] uppercase">Planner HQ</p>
                    <h1 className="mt-1 font-serif text-3xl font-light tracking-tight">Your weddings</h1>
                    <p className="mt-1.5 text-sm text-muted-foreground">
                        Everything that needs you, across every client.
                    </p>
                    <div className="rule-gold mt-3" />
                </div>
                <NewWeddingDialog />
            </div>

            {/* Public marketplace listing — get discovered by couples. */}
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#1f5142]/30 bg-[#1f5142]/5 p-4">
                <div>
                    <p className="text-sm font-semibold">Your public planner listing</p>
                    <p className="text-sm text-muted-foreground">
                        {listing.exists
                            ? `Showcase your work on the marketplace and rank on Google. Status: ${listing.status_label}.`
                            : 'Get discovered by couples — list your services, portfolio and reviews on the public marketplace.'}
                    </p>
                </div>
                <div className="flex gap-2">
                    {listing.public_url && (
                        <Button variant="outline" asChild>
                            <a href={listing.public_url} target="_blank" rel="noreferrer">View live</a>
                        </Button>
                    )}
                    {listing.exists ? (
                        <Button asChild><a href={listing.edit_url!}>Edit listing</a></Button>
                    ) : (
                        <Button onClick={() => router.post('/planner/listing')}>Create public listing</Button>
                    )}
                </div>
            </div>

            {/* Totals */}
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <StatChip icon={CalendarDays} label="Weddings" value={totals.weddings} />
                <StatChip icon={Users} label="Upcoming" value={totals.upcoming} />
                <StatChip icon={AlertTriangle} label="Overdue tasks" value={totals.overdue_tasks} />
                <StatChip icon={Inbox} label="Offers to review" value={totals.offers_awaiting} />
            </div>

            {/* Attention feed */}
            {hasAttention && (
                <section className="grid gap-4 lg:grid-cols-3">
                    <div className="rounded-xl border bg-card p-4">
                        <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold">
                            <AlertTriangle className="size-4 text-red-600" /> Overdue
                        </h2>
                        <ul className="space-y-2">
                            {attention.overdue_tasks.length === 0 && (
                                <li className="text-sm text-muted-foreground">Nothing overdue. 🎉</li>
                            )}
                            {attention.overdue_tasks.map((t) => (
                                <li key={t.id}>
                                    <button
                                        type="button"
                                        onClick={() => openWedding(t.wedding_slug, '/checklist')}
                                        className="w-full rounded-lg border px-3 py-2 text-left text-sm hover:bg-muted"
                                    >
                                        <span className="font-medium">{t.title}</span>
                                        <span className="mt-0.5 block text-xs text-muted-foreground">
                                            {t.wedding_name} · {t.days_overdue}d overdue
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="rounded-xl border bg-card p-4">
                        <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold">
                            <ClipboardList className="size-4 text-[#1b4638]" /> Due this week
                        </h2>
                        <ul className="space-y-2">
                            {attention.due_this_week.length === 0 && (
                                <li className="text-sm text-muted-foreground">A quiet week ahead.</li>
                            )}
                            {attention.due_this_week.map((t) => (
                                <li key={t.id}>
                                    <button
                                        type="button"
                                        onClick={() => openWedding(t.wedding_slug, '/checklist')}
                                        className="w-full rounded-lg border px-3 py-2 text-left text-sm hover:bg-muted"
                                    >
                                        <span className="font-medium">{t.title}</span>
                                        <span className="mt-0.5 block text-xs text-muted-foreground">
                                            {t.wedding_name} · due {t.due_date}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="rounded-xl border bg-card p-4">
                        <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold">
                            <Inbox className="size-4 text-[#1b4638]" /> Offers awaiting a decision
                        </h2>
                        <ul className="space-y-2">
                            {attention.offers_awaiting.length === 0 && (
                                <li className="text-sm text-muted-foreground">No open offers.</li>
                            )}
                            {attention.offers_awaiting.map((o) => (
                                <li key={o.id}>
                                    <button
                                        type="button"
                                        onClick={() => openWedding(o.wedding_slug, `/vendors/quotes/${o.id}`)}
                                        className="w-full rounded-lg border px-3 py-2 text-left text-sm hover:bg-muted"
                                    >
                                        <span className="font-medium">{o.vendor_name ?? 'Vendor offer'}</span>
                                        <span className="mt-0.5 block text-xs text-muted-foreground">{o.wedding_name}</span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            )}

            {/* Portfolio */}
            {weddings.length === 0 ? (
                <div className="rounded-xl border border-dashed p-12 text-center">
                    <CalendarDays className="mx-auto mb-3 size-10 text-muted-foreground/40" />
                    <h2 className="text-lg font-semibold">No client weddings yet</h2>
                    <p className="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                        Create your first client workspace — guest list, budget, checklist, seating
                        and the vendor marketplace, all in one place.
                    </p>
                </div>
            ) : (
                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {weddings.map((w) => {
                        const budgetPct =
                            w.budget.estimated_cents > 0
                                ? Math.min(100, Math.round((w.budget.paid_cents / w.budget.estimated_cents) * 100))
                                : 0;
                        const past = (w.days_until ?? 0) < 0;

                        return (
                            <div key={w.id} className="flex flex-col justify-between rounded-xl border bg-card p-5">
                                <div>
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 className="font-semibold">{w.name}</h3>
                                            <p className="text-sm text-muted-foreground">
                                                {w.event_date ?? 'Date to be set'}
                                                {w.days_until !== null && !past && (
                                                    <span className="ml-1 font-medium text-[#1b4638]">
                                                        · {w.days_until}d to go
                                                    </span>
                                                )}
                                                {past && <span className="ml-1">· past</span>}
                                            </p>
                                        </div>
                                        {w.role && (
                                            <Badge variant="outline" className="shrink-0 capitalize">{w.role}</Badge>
                                        )}
                                    </div>

                                    <div className="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                        <span className="flex items-center gap-1.5 text-muted-foreground">
                                            <Users className="size-3.5" />
                                            {w.guests.attending}/{w.guests.total} attending
                                        </span>
                                        <span className="flex items-center gap-1.5 text-muted-foreground">
                                            <ClipboardList className="size-3.5" />
                                            {w.tasks_outstanding} open
                                            {w.tasks_overdue > 0 && (
                                                <span className="font-medium text-red-600">({w.tasks_overdue} late)</span>
                                            )}
                                        </span>
                                        <span className="col-span-2 flex items-center gap-1.5 text-muted-foreground">
                                            <Wallet className="size-3.5" />
                                            {formatMoney(w.budget.paid_cents)} of {formatMoney(w.budget.estimated_cents)} paid
                                        </span>
                                    </div>

                                    {/* Budget progress */}
                                    <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-[#1b4638]"
                                            style={{ width: `${budgetPct}%` }}
                                        />
                                    </div>

                                    {w.offers_awaiting > 0 && (
                                        <p className="mt-3 text-xs font-medium text-[#1b4638]">
                                            {w.offers_awaiting} offer{w.offers_awaiting !== 1 ? 's' : ''} awaiting a decision
                                        </p>
                                    )}
                                </div>

                                <Button
                                    variant="outline"
                                    className="mt-5 w-full"
                                    onClick={() => openWedding(w.slug)}
                                >
                                    Open workspace
                                    <ArrowRight className="ml-1.5 size-4" />
                                </Button>
                            </div>
                        );
                    })}
                </section>
            )}
        </div>
    );
}

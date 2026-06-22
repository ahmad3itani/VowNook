import { Head, useForm } from '@inertiajs/react';
import { Palmtree, Plane, Plus, Trash2 } from 'lucide-react';
import { FormEvent } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type BudgetItem = { label: string; amount_cents: number };

type PageProps = {
    plan: {
        destination: string | null;
        airport: string | null;
        start_date: string | null;
        end_date: string | null;
        budget_items: BudgetItem[];
        notes: string | null;
    };
    stays_url: string | null;
    flights_url: string | null;
    affiliate_partner: string;
    flights_partner: string;
    affiliate_enabled: boolean;
    flights_enabled: boolean;
};

const money = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 0,
});

export default function HoneymoonIndex({
    plan,
    stays_url,
    flights_url,
    affiliate_partner,
    flights_partner,
    affiliate_enabled,
    flights_enabled,
}: PageProps) {
    const form = useForm({
        destination: plan.destination ?? '',
        airport: plan.airport ?? '',
        start_date: plan.start_date ?? '',
        end_date: plan.end_date ?? '',
        notes: plan.notes ?? '',
        budget_items: (plan.budget_items ?? []) as BudgetItem[],
    });

    function save(e: FormEvent) {
        e.preventDefault();
        form.put('/honeymoon', {
            preserveScroll: true,
            onSuccess: () => toast.success('Honeymoon plan saved.'),
        });
    }

    function addBudget() {
        form.setData('budget_items', [
            ...form.data.budget_items,
            { label: '', amount_cents: 0 },
        ]);
    }

    function updateBudget(i: number, patch: Partial<BudgetItem>) {
        form.setData(
            'budget_items',
            form.data.budget_items.map((b, idx) =>
                idx === i ? { ...b, ...patch } : b,
            ),
        );
    }

    function removeBudget(i: number) {
        form.setData(
            'budget_items',
            form.data.budget_items.filter((_, idx) => idx !== i),
        );
    }

    const total = form.data.budget_items.reduce(
        (s, b) => s + (b.amount_cents || 0),
        0,
    );

    return (
        <>
            <Head title="Honeymoon planner" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Honeymoon planner"
                    description="Pick your destination and dates, set a budget, then book stays and flights right here."
                />

                {/* Trip details */}
                <Card>
                    <CardContent className="flex flex-col gap-4 py-5">
                        <form onSubmit={save} className="flex flex-col gap-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="destination">
                                        Destination
                                    </Label>
                                    <Input
                                        id="destination"
                                        value={form.data.destination}
                                        onChange={(e) =>
                                            form.setData(
                                                'destination',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. Maui, Hawaii"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="airport">
                                        Destination airport (code)
                                    </Label>
                                    <Input
                                        id="airport"
                                        value={form.data.airport}
                                        onChange={(e) =>
                                            form.setData(
                                                'airport',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="e.g. OGG"
                                        className="uppercase"
                                        maxLength={60}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="start">Leaving</Label>
                                    <Input
                                        id="start"
                                        type="date"
                                        value={form.data.start_date}
                                        onChange={(e) =>
                                            form.setData(
                                                'start_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="end">Returning</Label>
                                    <Input
                                        id="end"
                                        type="date"
                                        value={form.data.end_date}
                                        onChange={(e) =>
                                            form.setData(
                                                'end_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {form.errors.end_date && (
                                        <p className="text-xs text-destructive">
                                            {form.errors.end_date}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Notes (optional)</Label>
                                <Textarea
                                    id="notes"
                                    rows={2}
                                    value={form.data.notes}
                                    onChange={(e) =>
                                        form.setData('notes', e.target.value)
                                    }
                                    placeholder="Must-dos, who's booking what, passport reminders…"
                                />
                            </div>

                            {/* Budget */}
                            <div className="flex flex-col gap-3 border-t pt-4">
                                <div className="flex items-center justify-between">
                                    <h2 className="font-medium">
                                        Honeymoon budget
                                    </h2>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addBudget}
                                    >
                                        <Plus className="size-4" /> Add line
                                    </Button>
                                </div>

                                {form.data.budget_items.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Add flights, your stay, activities,
                                        spending money…
                                    </p>
                                ) : (
                                    <div className="flex flex-col gap-2">
                                        {form.data.budget_items.map((b, i) => (
                                            <div
                                                key={i}
                                                className="flex items-center gap-2"
                                            >
                                                <Input
                                                    value={b.label}
                                                    onChange={(e) =>
                                                        updateBudget(i, {
                                                            label: e.target
                                                                .value,
                                                        })
                                                    }
                                                    placeholder="e.g. Flights"
                                                    className="flex-1"
                                                />
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    value={
                                                        Math.round(
                                                            b.amount_cents /
                                                                100,
                                                        ) || ''
                                                    }
                                                    onChange={(e) =>
                                                        updateBudget(i, {
                                                            amount_cents:
                                                                Math.round(
                                                                    Number(
                                                                        e.target
                                                                            .value,
                                                                    ) * 100,
                                                                ),
                                                        })
                                                    }
                                                    placeholder="0"
                                                    className="w-32"
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        removeBudget(i)
                                                    }
                                                    aria-label="Remove line"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        ))}
                                        <div className="flex items-center justify-end gap-3 pt-1 text-sm">
                                            <span className="text-muted-foreground">
                                                Estimated total
                                            </span>
                                            <span className="font-semibold">
                                                {money.format(total / 100)}
                                            </span>
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />} Save plan
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Stays at the destination */}
                {stays_url && (
                    <Card>
                        <CardContent className="flex flex-col gap-3 py-5">
                            <div className="flex items-center gap-2">
                                <Palmtree className="size-5 text-[#775a19]" />
                                <h2 className="font-medium">
                                    Where to stay
                                    {form.data.destination
                                        ? ` in ${form.data.destination}`
                                        : ''}
                                </h2>
                            </div>
                            <div className="overflow-hidden rounded-xl border">
                                <iframe
                                    src={stays_url}
                                    title="Stays at your honeymoon destination"
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                    className="h-[460px] w-full border-0"
                                    allow="geolocation"
                                />
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Stays from our travel partner,{' '}
                                {affiliate_partner}. VowNook may earn a small
                                commission if you book — at no extra cost to
                                you.
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Flights to the destination */}
                {flights_url && (
                    <Card>
                        <CardContent className="flex flex-col items-start gap-3 py-5">
                            <div className="flex items-center gap-2">
                                <Plane className="size-5 text-[#775a19]" />
                                <h2 className="font-medium">
                                    Find your flights
                                </h2>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                We’ve pre-set a search to{' '}
                                {form.data.airport || 'your destination'} for
                                your dates — just add your departure city.
                            </p>
                            <Button asChild>
                                <a
                                    href={flights_url}
                                    target="_blank"
                                    rel="noopener noreferrer sponsored"
                                >
                                    Search flights
                                </a>
                            </Button>
                            <p className="text-xs text-muted-foreground">
                                Flight search by {flights_partner}. VowNook may
                                earn a small commission if you book — at no
                                extra cost to you.
                            </p>
                        </CardContent>
                    </Card>
                )}

                {(!affiliate_enabled || !flights_enabled) &&
                    (!stays_url || !flights_url) && (
                        <p className="text-sm text-muted-foreground">
                            Tip: add your destination
                            {!flights_url ? ' and its airport code' : ''} above,
                            then save — your
                            {affiliate_enabled ? ' hotel map' : ''}
                            {affiliate_enabled && flights_enabled ? ' and' : ''}
                            {flights_enabled ? ' flight search' : ''} will
                            appear here.
                        </p>
                    )}
            </div>
        </>
    );
}

HoneymoonIndex.layout = {
    breadcrumbs: [{ title: 'Honeymoon', href: '/honeymoon' }],
};

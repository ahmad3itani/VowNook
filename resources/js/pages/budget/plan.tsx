import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRight, Sparkles, Wallet } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney } from '@/lib/format';

type Band = { key: string; label: string; cents: number };
type City = { slug: string; name: string };
type AllocationRow = { label: string; percent: number; amount_cents: number; vendor_categories: string[] };
type Verdict = 'tight' | 'comfortable' | 'generous' | 'unknown';
type Realism = { typical_cents: number; ratio: number; verdict: Verdict; message: string };

type PageProps = {
    wedding: { total_budget_cents: number | null; city: string | null; city_name: string | null; guest_count: number };
    bands: Band[];
    cities: City[];
    allocation: AllocationRow[] | null;
    realism: Realism | null;
    has_budget_items: boolean;
};

const VERDICT_STYLES: Record<Verdict, string> = {
    tight: 'border-amber-300 bg-amber-50 text-amber-900',
    comfortable: 'border-emerald-300 bg-emerald-50 text-emerald-900',
    generous: 'border-sky-300 bg-sky-50 text-sky-900',
    unknown: 'border-border bg-muted text-muted-foreground',
};

export default function BudgetPlan({ wedding, bands, cities, allocation, realism, has_budget_items }: PageProps) {
    const [applying, setApplying] = useState(false);

    const form = useForm({
        band: '' as string,
        exact_dollars: wedding.total_budget_cents ? String(Math.round(wedding.total_budget_cents / 100)) : '',
        city: wedding.city ?? '',
    });

    const chooseBand = (key: string) => form.setData({ ...form.data, band: key, exact_dollars: '' });
    const typeExact = (value: string) => form.setData({ ...form.data, exact_dollars: value, band: '' });

    const save = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/budget/plan', {
            preserveScroll: true,
            onSuccess: () => toast.success('Budget saved — here’s how it works'),
        });
    };

    const apply = () => {
        setApplying(true);
        router.post('/budget/plan/apply', {}, {
            preserveScroll: true,
            onFinish: () => setApplying(false),
            onSuccess: () => toast.success('Added to your budget tracker'),
        });
    };

    const hasBudget = wedding.total_budget_cents !== null && allocation !== null;

    return (
        <>
            <Head title="Your budget" />

            <div className="mx-auto w-full max-w-4xl px-4 py-6">
                <Heading
                    title="Bring your budget, we’ll make it work"
                    description="Tell us your number and your city. We’ll split it across your wedding and show you what’s realistic — then help you find vendors that fit."
                />

                {/* Capture */}
                <Card className="mt-6">
                    <CardContent className="p-5">
                        <form onSubmit={save} className="space-y-5">
                            <div>
                                <Label className="text-sm font-medium">What’s your total budget?</Label>
                                <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-5">
                                    {bands.map((b) => {
                                        const active = form.data.band === b.key;
                                        return (
                                            <button
                                                key={b.key}
                                                type="button"
                                                onClick={() => chooseBand(b.key)}
                                                className={`rounded-lg border px-2 py-2.5 text-center text-sm transition-colors ${
                                                    active
                                                        ? 'border-[#1b4638] bg-[#1b4638]/5 font-medium ring-1 ring-[#1b4638]'
                                                        : 'border-border hover:bg-muted'
                                                }`}
                                            >
                                                {b.label}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="flex flex-col gap-4 sm:flex-row">
                                <div className="flex-1">
                                    <Label htmlFor="exact" className="text-xs text-muted-foreground">
                                        Or enter an exact amount (CAD)
                                    </Label>
                                    <div className="relative mt-1">
                                        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
                                        <Input
                                            id="exact"
                                            type="number"
                                            min={1000}
                                            step={500}
                                            value={form.data.exact_dollars}
                                            onChange={(e) => typeExact(e.target.value)}
                                            placeholder="30,000"
                                            className="pl-7"
                                        />
                                    </div>
                                    <InputError message={form.errors.exact_dollars} />
                                </div>

                                <div className="flex-1">
                                    <Label htmlFor="city" className="text-xs text-muted-foreground">Where’s the wedding?</Label>
                                    <Select value={form.data.city || undefined} onValueChange={(v) => form.setData('city', v)}>
                                        <SelectTrigger id="city" className="mt-1">
                                            <SelectValue placeholder="Choose a city" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {cities.map((c) => (
                                                <SelectItem key={c.slug} value={c.slug}>{c.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.city} />
                                </div>
                            </div>

                            <Button type="submit" disabled={form.processing || (!form.data.band && !form.data.exact_dollars)}>
                                {form.processing && <Spinner />}
                                <Wallet className="size-4" />
                                {hasBudget ? 'Update my budget' : 'Make it work'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Allocation + realism */}
                {hasBudget && (
                    <div className="mt-6 space-y-4">
                        {realism && realism.verdict !== 'unknown' && (
                            <div className={`rounded-xl border p-4 text-sm leading-relaxed ${VERDICT_STYLES[realism.verdict]}`}>
                                {realism.message}
                            </div>
                        )}
                        {realism && realism.verdict === 'unknown' && (
                            <div className={`rounded-xl border p-4 text-sm ${VERDICT_STYLES.unknown}`}>{realism.message}</div>
                        )}

                        <Card>
                            <CardContent className="p-5">
                                <div className="mb-4 flex items-baseline justify-between">
                                    <h2 className="text-lg font-semibold">Your {formatMoney(wedding.total_budget_cents!)} plan</h2>
                                    <span className="text-sm text-muted-foreground">
                                        {wedding.city_name ?? 'Ontario'}
                                        {wedding.guest_count > 0 ? ` · ${wedding.guest_count} guests` : ''}
                                    </span>
                                </div>

                                <div className="space-y-3">
                                    {allocation!.map((row) => (
                                        <div key={row.label}>
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="font-medium">{row.label}</span>
                                                <span className="tabular-nums">
                                                    {formatMoney(row.amount_cents)}
                                                    <span className="ml-1.5 text-xs text-muted-foreground">{Math.round(row.percent * 100)}%</span>
                                                </span>
                                            </div>
                                            <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-muted">
                                                <div className="h-full rounded-full bg-[#1f5142]" style={{ width: `${Math.max(2, row.percent * 100)}%` }} />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <p className="mt-4 text-xs text-muted-foreground">
                                    These are estimates to start from — tune every line in your budget tracker.
                                </p>

                                <div className="mt-4 flex flex-col gap-2 sm:flex-row">
                                    <Button onClick={apply} disabled={applying} variant="default">
                                        {applying && <Spinner />}
                                        <Sparkles className="size-4" />
                                        {has_budget_items ? 'Refresh my budget tracker' : 'Add to my budget tracker'}
                                    </Button>
                                    <Button variant="outline" onClick={() => router.visit('/vendors/marketplace')}>
                                        Find vendors that fit
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </>
    );
}

BudgetPlan.layout = {
    breadcrumbs: [
        { title: 'Budget', href: '/budget' },
        { title: 'Plan', href: '/budget/plan' },
    ],
};

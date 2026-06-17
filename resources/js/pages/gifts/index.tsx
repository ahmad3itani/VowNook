import { Head, router, useForm } from '@inertiajs/react';
import { Gift as GiftIcon, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/format';
import { usePermissions } from '@/hooks/use-permissions';

type GuestRef = { id: number; name: string };
type Gift = {
    id: number;
    from_name: string | null;
    kind: string;
    amount_cents: number | null;
    received_at: string | null;
    thank_you_sent: boolean;
    notes: string | null;
    guest: GuestRef | null;
    from_registry: boolean;
};

const KIND_LABELS: Record<string, string> = { fund: 'Fund gift', item: 'Registry item', cash: 'Cash', physical: 'Gift' };

const dollars = (cents: number | null | undefined) => (cents ? (cents / 100).toString() : '');

export default function GiftsIndex({
    gifts,
    summary,
    kinds,
    guests,
}: {
    gifts: Gift[];
    summary: { total: number; pending: number; cash_cents: number };
    kinds: string[];
    guests: GuestRef[];
}) {
    const { canWrite } = usePermissions();
    const writable = canWrite('website');

    const [filter, setFilter] = useState<'all' | 'pending'>('all');
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Gift | null>(null);

    const form = useForm<{ from_name: string; kind: string; amount: string; received_at: string; guest_id: string; notes: string }>(
        { from_name: '', kind: 'physical', amount: '', received_at: '', guest_id: '', notes: '' },
    );

    const shown = filter === 'pending' ? gifts.filter((g) => !g.thank_you_sent) : gifts;

    function openSheet(g: Gift | null) {
        setEditing(g);
        form.clearErrors();
        form.setData({
            from_name: g?.from_name ?? '', kind: g?.kind ?? 'physical', amount: dollars(g?.amount_cents),
            received_at: g?.received_at ?? '', guest_id: g?.guest?.id ? String(g.guest.id) : '', notes: g?.notes ?? '',
        });
        setOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((d) => ({
            from_name: d.from_name,
            kind: d.kind,
            amount_cents: d.amount ? Math.round(parseFloat(d.amount) * 100) : null,
            received_at: d.received_at || null,
            guest_id: d.guest_id ? parseInt(d.guest_id, 10) : null,
            notes: d.notes,
            ...(editing ? { _method: 'put' } : {}),
        }));
        const opts = { preserveScroll: true, onSuccess: () => { setOpen(false); toast.success('Gift saved.'); } };
        if (editing) form.post(`/gifts/${editing.id}`, opts);
        else form.post('/gifts', opts);
    }

    function toggleThankYou(g: Gift) {
        router.patch(`/gifts/${g.id}/thank-you`, { thank_you_sent: !g.thank_you_sent }, {
            preserveScroll: true,
            onSuccess: () => toast.success(g.thank_you_sent ? 'Marked as not sent.' : 'Thank-you marked sent.'),
        });
    }

    function destroy(g: Gift) {
        if (!confirm('Delete this gift?')) return;
        router.delete(`/gifts/${g.id}`, { preserveScroll: true, onSuccess: () => toast.success('Gift deleted.') });
    }

    return (
        <>
            <Head title="Gifts & thank-yous" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Gifts & thank-yous"
                        description="Every gift in one place — registry contributions flow in automatically, and you can add cards and cash by hand. Tick off thank-you notes as you send them."
                    />
                    {writable && (
                        <Button onClick={() => openSheet(null)}>
                            <Plus className="size-4" /> Add gift
                        </Button>
                    )}
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    <Card><CardContent className="pt-5"><p className="text-2xl font-semibold">{summary.total}</p><p className="text-sm text-muted-foreground">Gifts received</p></CardContent></Card>
                    <Card><CardContent className="pt-5"><p className="text-2xl font-semibold text-[#775a19]">{summary.pending}</p><p className="text-sm text-muted-foreground">Thank-yous to send</p></CardContent></Card>
                    <Card><CardContent className="pt-5"><p className="text-2xl font-semibold">{formatMoney(summary.cash_cents)}</p><p className="text-sm text-muted-foreground">Cash &amp; funds</p></CardContent></Card>
                </div>

                <div className="flex gap-2">
                    {(['all', 'pending'] as const).map((f) => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`rounded-full border px-4 py-1.5 text-sm transition-colors ${
                                filter === f ? 'border-[#775a19] bg-[#fed488]/20 font-medium text-[#1e1b18]' : 'border-[#cec5bd]/60 hover:border-[#775a19]/50'
                            }`}
                        >
                            {f === 'all' ? `All (${gifts.length})` : `To thank (${summary.pending})`}
                        </button>
                    ))}
                </div>

                {shown.length === 0 ? (
                    <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">
                        {filter === 'pending' ? 'All caught up — every thank-you is sent. 🤍' : 'No gifts logged yet.'}
                    </CardContent></Card>
                ) : (
                    <div className="flex flex-col gap-2">
                        {shown.map((g) => (
                            <Card key={g.id}>
                                <CardContent className="flex flex-wrap items-center gap-4 py-3">
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={g.thank_you_sent}
                                            onChange={() => writable && toggleThankYou(g)}
                                            disabled={!writable}
                                            className="size-4 accent-[#775a19]"
                                        />
                                        <span className="sr-only">Thank-you sent</span>
                                    </label>
                                    <div className="min-w-0 flex-1">
                                        <p className={`font-medium ${g.thank_you_sent ? 'text-muted-foreground line-through' : ''}`}>
                                            {g.from_name || g.guest?.name || 'Anonymous'}
                                        </p>
                                        <p className="flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                                            <span>{KIND_LABELS[g.kind] ?? g.kind}</span>
                                            {g.amount_cents != null && <span>· {formatMoney(g.amount_cents)}</span>}
                                            {g.received_at && <span>· {g.received_at}</span>}
                                            {g.from_registry && <span className="rounded-full bg-[#f6efe1] px-2 py-0.5 text-[#775a19]">from registry</span>}
                                        </p>
                                        {g.notes && <p className="mt-0.5 line-clamp-1 text-xs text-muted-foreground italic">“{g.notes}”</p>}
                                    </div>
                                    <span className={`text-xs ${g.thank_you_sent ? 'text-[#775a19]' : 'text-muted-foreground'}`}>
                                        {g.thank_you_sent ? 'Thanked ✓' : 'To thank'}
                                    </span>
                                    {writable && (
                                        <div className="flex gap-1">
                                            <Button variant="ghost" size="icon" className="size-7" onClick={() => openSheet(g)}><Pencil className="size-3.5" /></Button>
                                            <Button variant="ghost" size="icon" className="size-7" onClick={() => destroy(g)}><Trash2 className="size-3.5" /></Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader><SheetTitle>{editing ? 'Edit gift' : 'Add a gift'}</SheetTitle></SheetHeader>
                    <form onSubmit={submit} className="flex flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label>From</Label>
                            <Input value={form.data.from_name} onChange={(e) => form.setData('from_name', e.target.value)} placeholder="Aunt May & Uncle Ben" />
                            <InputError message={form.errors.from_name} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Kind</Label>
                                <Select value={form.data.kind} onValueChange={(v) => form.setData('kind', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>{kinds.map((k) => <SelectItem key={k} value={k}>{KIND_LABELS[k] ?? k}</SelectItem>)}</SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Value ($)</Label>
                                <Input type="number" min={0} value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} placeholder="100" />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>Received on</Label>
                            <Input type="date" value={form.data.received_at} onChange={(e) => form.setData('received_at', e.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Link to a guest (optional)</Label>
                            <Select value={form.data.guest_id || 'none'} onValueChange={(v) => form.setData('guest_id', v === 'none' ? '' : v)}>
                                <SelectTrigger><SelectValue placeholder="Choose a guest" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">No one in particular</SelectItem>
                                    {guests.map((g) => <SelectItem key={g.id} value={String(g.id)}>{g.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Notes</Label>
                            <Textarea value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} rows={2} placeholder="A lovely note, what they gave…" />
                        </div>
                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={form.processing}>{form.processing && <Spinner />} Save gift</Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

GiftsIndex.layout = {
    breadcrumbs: [{ title: 'Gifts & thank-yous', href: '/gifts' }],
};

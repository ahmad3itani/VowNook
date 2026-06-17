import { Head, router, useForm } from '@inertiajs/react';
import { Gift, Pencil, Plus, Trash2, Wallet } from 'lucide-react';
import { useRef, useState } from 'react';
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

type Fund = {
    id: number;
    title: string;
    blurb: string | null;
    type: string;
    goal_cents: number | null;
    raised_cents: number;
    payout_url: string | null;
    is_active: boolean;
    contributions_count: number;
    image_url: string | null;
};

type Item = {
    id: number;
    name: string;
    blurb: string | null;
    price_cents: number | null;
    store_url: string | null;
    quantity: number;
    claimed_count: number;
    image_url: string | null;
};

const FUND_TYPES = [
    { value: 'cash', label: 'Cash gift' },
    { value: 'honeymoon', label: 'Honeymoon' },
    { value: 'custom', label: 'Custom fund' },
];

const dollars = (cents: number | null | undefined) => (cents ? (cents / 100).toString() : '');

export default function RegistryIndex({ funds, items }: { funds: Fund[]; items: Item[] }) {
    const { canWrite } = usePermissions();
    const writable = canWrite('website');

    const [fundOpen, setFundOpen] = useState(false);
    const [editFund, setEditFund] = useState<Fund | null>(null);
    const [itemOpen, setItemOpen] = useState(false);
    const [editItem, setEditItem] = useState<Item | null>(null);
    const fundImg = useRef<HTMLInputElement>(null);
    const itemImg = useRef<HTMLInputElement>(null);

    const fundForm = useForm<{
        title: string; type: string; blurb: string; goal: string; payout_url: string; is_active: boolean; image: File | null;
    }>({ title: '', type: 'cash', blurb: '', goal: '', payout_url: '', is_active: true, image: null });

    const itemForm = useForm<{
        name: string; blurb: string; price: string; store_url: string; quantity: string; image: File | null;
    }>({ name: '', blurb: '', price: '', store_url: '', quantity: '1', image: null });

    function openFund(f: Fund | null) {
        setEditFund(f);
        fundForm.clearErrors();
        fundForm.setData({
            title: f?.title ?? '', type: f?.type ?? 'cash', blurb: f?.blurb ?? '',
            goal: dollars(f?.goal_cents), payout_url: f?.payout_url ?? '', is_active: f?.is_active ?? true, image: null,
        });
        if (fundImg.current) fundImg.current.value = '';
        setFundOpen(true);
    }

    function submitFund(e: React.FormEvent) {
        e.preventDefault();
        fundForm.transform((d) => ({
            ...d,
            goal_cents: d.goal ? Math.round(parseFloat(d.goal) * 100) : null,
            ...(editFund ? { _method: 'put' } : {}),
        }));
        const opts = { preserveScroll: true, onSuccess: () => { setFundOpen(false); toast.success('Fund saved.'); } };
        if (editFund) fundForm.post(`/registry/funds/${editFund.id}`, opts);
        else fundForm.post('/registry/funds', opts);
    }

    function openItem(i: Item | null) {
        setEditItem(i);
        itemForm.clearErrors();
        itemForm.setData({
            name: i?.name ?? '', blurb: i?.blurb ?? '', price: dollars(i?.price_cents),
            store_url: i?.store_url ?? '', quantity: String(i?.quantity ?? 1), image: null,
        });
        if (itemImg.current) itemImg.current.value = '';
        setItemOpen(true);
    }

    function submitItem(e: React.FormEvent) {
        e.preventDefault();
        itemForm.transform((d) => ({
            ...d,
            price_cents: d.price ? Math.round(parseFloat(d.price) * 100) : null,
            quantity: parseInt(d.quantity || '1', 10),
            ...(editItem ? { _method: 'put' } : {}),
        }));
        const opts = { preserveScroll: true, onSuccess: () => { setItemOpen(false); toast.success('Item saved.'); } };
        if (editItem) itemForm.post(`/registry/items/${editItem.id}`, opts);
        else itemForm.post('/registry/items', opts);
    }

    function destroyFund(f: Fund) {
        if (!confirm(`Delete the "${f.title}" fund?`)) return;
        router.delete(`/registry/funds/${f.id}`, { preserveScroll: true, onSuccess: () => toast.success('Fund deleted.') });
    }

    function destroyItem(i: Item) {
        if (!confirm(`Delete "${i.name}"?`)) return;
        router.delete(`/registry/items/${i.id}`, { preserveScroll: true, onSuccess: () => toast.success('Item deleted.') });
    }

    return (
        <>
            <Head title="Registry" />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <Heading
                    title="Registry"
                    description="Set up cash funds and gift items. Guests contribute to your funds through your own payout link — no fees held by us."
                />

                {/* Funds */}
                <section className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Wallet className="size-5 text-[#8a651c]" /> Cash & honeymoon funds
                        </h2>
                        {writable && (
                            <Button onClick={() => openFund(null)}>
                                <Plus className="size-4" /> Add fund
                            </Button>
                        )}
                    </div>

                    {funds.length === 0 ? (
                        <Card><CardContent className="py-10 text-center text-sm text-muted-foreground">No funds yet. Add a honeymoon or cash fund.</CardContent></Card>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {funds.map((f) => {
                                const pct = f.goal_cents ? Math.min(100, Math.round((f.raised_cents / f.goal_cents) * 100)) : null;
                                return (
                                    <Card key={f.id} className="overflow-hidden">
                                        {f.image_url && <img src={f.image_url} alt="" className="h-32 w-full object-cover" />}
                                        <CardContent className="space-y-2 pt-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <p className="font-semibold">{f.title}</p>
                                                    <p className="text-xs text-muted-foreground capitalize">{f.type}{f.is_active ? '' : ' · hidden'}</p>
                                                </div>
                                                {writable && (
                                                    <div className="flex gap-1">
                                                        <Button variant="ghost" size="icon" className="size-7" onClick={() => openFund(f)}><Pencil className="size-3.5" /></Button>
                                                        <Button variant="ghost" size="icon" className="size-7" onClick={() => destroyFund(f)}><Trash2 className="size-3.5" /></Button>
                                                    </div>
                                                )}
                                            </div>
                                            <p className="text-sm font-medium text-[#775a19]">
                                                {formatMoney(f.raised_cents)} raised{f.goal_cents ? ` of ${formatMoney(f.goal_cents)}` : ''}
                                            </p>
                                            {pct !== null && (
                                                <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                                                    <div className="h-full rounded-full bg-[#8a651c]" style={{ width: `${pct}%` }} />
                                                </div>
                                            )}
                                            <p className="text-xs text-muted-foreground">{f.contributions_count} contribution{f.contributions_count === 1 ? '' : 's'}{f.payout_url ? '' : ' · ⚠ add a payout link'}</p>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    )}
                </section>

                {/* Items */}
                <section className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="flex items-center gap-2 text-lg font-semibold">
                            <Gift className="size-5 text-[#8a651c]" /> Gift items
                        </h2>
                        {writable && (
                            <Button onClick={() => openItem(null)}>
                                <Plus className="size-4" /> Add item
                            </Button>
                        )}
                    </div>

                    {items.length === 0 ? (
                        <Card><CardContent className="py-10 text-center text-sm text-muted-foreground">No items yet. Add gifts with a link to where guests can buy them.</CardContent></Card>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {items.map((i) => (
                                <Card key={i.id} className="overflow-hidden">
                                    {i.image_url && <img src={i.image_url} alt="" className="aspect-square w-full object-cover" />}
                                    <CardContent className="space-y-1 pt-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <p className="font-medium leading-tight">{i.name}</p>
                                            {writable && (
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" className="size-7" onClick={() => openItem(i)}><Pencil className="size-3.5" /></Button>
                                                    <Button variant="ghost" size="icon" className="size-7" onClick={() => destroyItem(i)}><Trash2 className="size-3.5" /></Button>
                                                </div>
                                            )}
                                        </div>
                                        {i.price_cents != null && <p className="text-sm text-[#775a19]">{formatMoney(i.price_cents)}</p>}
                                        <p className="text-xs text-muted-foreground">{i.claimed_count}/{i.quantity} claimed</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            {/* Fund sheet */}
            <Sheet open={fundOpen} onOpenChange={setFundOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader><SheetTitle>{editFund ? 'Edit fund' : 'New fund'}</SheetTitle></SheetHeader>
                    <form onSubmit={submitFund} className="flex flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label>Title</Label>
                            <Input value={fundForm.data.title} onChange={(e) => fundForm.setData('title', e.target.value)} placeholder="Our honeymoon in Italy" />
                            <InputError message={fundForm.errors.title} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Type</Label>
                            <Select value={fundForm.data.type} onValueChange={(v) => fundForm.setData('type', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>{FUND_TYPES.map((t) => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Description</Label>
                            <Textarea value={fundForm.data.blurb} onChange={(e) => fundForm.setData('blurb', e.target.value)} rows={3} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Goal (optional, $)</Label>
                            <Input type="number" min={0} value={fundForm.data.goal} onChange={(e) => fundForm.setData('goal', e.target.value)} placeholder="5000" />
                        </div>
                        <div className="grid gap-2">
                            <Label>Payout link (where guests pay you)</Label>
                            <Input value={fundForm.data.payout_url} onChange={(e) => fundForm.setData('payout_url', e.target.value)} placeholder="https://paypal.me/yourname" />
                            <p className="text-xs text-muted-foreground">PayPal.me, Venmo, an Interac e-transfer page, or a GoFundMe. Guests pay you directly.</p>
                            <InputError message={fundForm.errors.payout_url} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Photo (optional)</Label>
                            <input ref={fundImg} type="file" accept="image/*" onChange={(e) => fundForm.setData('image', e.target.files?.[0] ?? null)} className="text-sm" />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={fundForm.data.is_active} onChange={(e) => fundForm.setData('is_active', e.target.checked)} />
                            Show on the wedding website
                        </label>
                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={fundForm.processing}>{fundForm.processing && <Spinner />} Save fund</Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            {/* Item sheet */}
            <Sheet open={itemOpen} onOpenChange={setItemOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader><SheetTitle>{editItem ? 'Edit item' : 'New item'}</SheetTitle></SheetHeader>
                    <form onSubmit={submitItem} className="flex flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label>Name</Label>
                            <Input value={itemForm.data.name} onChange={(e) => itemForm.setData('name', e.target.value)} placeholder="Stand mixer" />
                            <InputError message={itemForm.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Description</Label>
                            <Textarea value={itemForm.data.blurb} onChange={(e) => itemForm.setData('blurb', e.target.value)} rows={2} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Price ($)</Label>
                                <Input type="number" min={0} value={itemForm.data.price} onChange={(e) => itemForm.setData('price', e.target.value)} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Quantity</Label>
                                <Input type="number" min={1} value={itemForm.data.quantity} onChange={(e) => itemForm.setData('quantity', e.target.value)} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>Store link</Label>
                            <Input value={itemForm.data.store_url} onChange={(e) => itemForm.setData('store_url', e.target.value)} placeholder="https://…" />
                            <InputError message={itemForm.errors.store_url} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Photo (optional)</Label>
                            <input ref={itemImg} type="file" accept="image/*" onChange={(e) => itemForm.setData('image', e.target.files?.[0] ?? null)} className="text-sm" />
                        </div>
                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={itemForm.processing}>{itemForm.processing && <Spinner />} Save item</Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

RegistryIndex.layout = {
    breadcrumbs: [{ title: 'Registry', href: '/registry' }],
};

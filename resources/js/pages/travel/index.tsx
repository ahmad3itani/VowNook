import { Head, router, useForm } from '@inertiajs/react';
import { BedDouble, Car, Home, MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
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
import { usePermissions } from '@/hooks/use-permissions';

type Stay = {
    id: number;
    name: string;
    type: string;
    address: string | null;
    blurb: string | null;
    booking_url: string | null;
    block_code: string | null;
    price_note: string | null;
    distance_note: string | null;
    is_active: boolean;
    image_url: string | null;
};

const TYPE_LABELS: Record<string, string> = { hotel: 'Hotel', rental: 'Rental', transport: 'Transport' };
const TYPE_ICON: Record<string, typeof BedDouble> = { hotel: BedDouble, rental: Home, transport: Car };

export default function TravelIndex({
    accommodations,
    travel_notes,
    types,
}: {
    accommodations: Stay[];
    travel_notes: string;
    types: string[];
}) {
    const { canWrite } = usePermissions();
    const writable = canWrite('website');

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Stay | null>(null);
    const imgRef = useRef<HTMLInputElement>(null);

    const form = useForm<{
        name: string; type: string; address: string; blurb: string; booking_url: string;
        block_code: string; price_note: string; distance_note: string; is_active: boolean; image: File | null;
    }>({
        name: '', type: 'hotel', address: '', blurb: '', booking_url: '',
        block_code: '', price_note: '', distance_note: '', is_active: true, image: null,
    });

    const notesForm = useForm({ travel_notes: travel_notes ?? '' });

    function openSheet(s: Stay | null) {
        setEditing(s);
        form.clearErrors();
        form.setData({
            name: s?.name ?? '', type: s?.type ?? 'hotel', address: s?.address ?? '', blurb: s?.blurb ?? '',
            booking_url: s?.booking_url ?? '', block_code: s?.block_code ?? '', price_note: s?.price_note ?? '',
            distance_note: s?.distance_note ?? '', is_active: s?.is_active ?? true, image: null,
        });
        if (imgRef.current) imgRef.current.value = '';
        setOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { setOpen(false); toast.success('Saved.'); } };
        if (editing) {
            form.transform((d) => ({ ...d, _method: 'put' }));
            form.post(`/travel/${editing.id}`, opts);
        } else {
            form.transform((d) => d);
            form.post('/travel', opts);
        }
    }

    function destroy(s: Stay) {
        if (!confirm(`Delete "${s.name}"?`)) return;
        router.delete(`/travel/${s.id}`, { preserveScroll: true, onSuccess: () => toast.success('Deleted.') });
    }

    function saveNotes(e: React.FormEvent) {
        e.preventDefault();
        notesForm.put('/travel/notes', { preserveScroll: true, onSuccess: () => toast.success('Travel notes saved.') });
    }

    return (
        <>
            <Head title="Travel & stays" />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Travel & stays"
                        description="Help out-of-town guests with hotel room blocks, rentals, and getting-around tips. Shows on your wedding website."
                    />
                    {writable && (
                        <Button onClick={() => openSheet(null)}>
                            <Plus className="size-4" /> Add place
                        </Button>
                    )}
                </div>

                {accommodations.length === 0 ? (
                    <Card>
                        <CardContent className="py-12 text-center text-sm text-muted-foreground">
                            No stays yet. Add a hotel block, a rental, or a shuttle option.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {accommodations.map((s) => {
                            const Icon = TYPE_ICON[s.type] ?? BedDouble;
                            return (
                                <Card key={s.id} className="overflow-hidden">
                                    {s.image_url && <img src={s.image_url} alt="" className="h-32 w-full object-cover" />}
                                    <CardContent className="space-y-2 pt-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="font-semibold">{s.name}</p>
                                                <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <Icon className="size-3.5" /> {TYPE_LABELS[s.type] ?? s.type}
                                                    {s.is_active ? '' : ' · hidden'}
                                                </p>
                                            </div>
                                            {writable && (
                                                <div className="flex gap-1">
                                                    <Button variant="ghost" size="icon" className="size-7" onClick={() => openSheet(s)}><Pencil className="size-3.5" /></Button>
                                                    <Button variant="ghost" size="icon" className="size-7" onClick={() => destroy(s)}><Trash2 className="size-3.5" /></Button>
                                                </div>
                                            )}
                                        </div>
                                        {s.price_note && <p className="text-sm text-[#775a19]">{s.price_note}</p>}
                                        {s.distance_note && <p className="flex items-center gap-1 text-xs text-muted-foreground"><MapPin className="size-3" /> {s.distance_note}</p>}
                                        {s.block_code && <p className="text-xs text-muted-foreground">Code: <span className="font-medium">{s.block_code}</span></p>}
                                        {s.booking_url && <p className="text-xs text-muted-foreground">Booking link added ✓</p>}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* Travel notes */}
                <form onSubmit={saveNotes} className="flex flex-col gap-3 border-t pt-6">
                    <div>
                        <h2 className="text-lg font-semibold">Getting there</h2>
                        <p className="text-sm text-muted-foreground">Parking, shuttles, airports, directions — anything that helps guests arrive.</p>
                    </div>
                    <Textarea
                        value={notesForm.data.travel_notes}
                        onChange={(e) => notesForm.setData('travel_notes', e.target.value)}
                        rows={4}
                        disabled={!writable}
                        placeholder="Free parking is available on-site. A shuttle runs from the host hotel at 3:30 PM…"
                    />
                    {writable && (
                        <div>
                            <Button type="submit" disabled={notesForm.processing}>{notesForm.processing && <Spinner />} Save notes</Button>
                        </div>
                    )}
                </form>
            </div>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader><SheetTitle>{editing ? 'Edit place' : 'New place'}</SheetTitle></SheetHeader>
                    <form onSubmit={submit} className="flex flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label>Name</Label>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="The Walper Hotel" />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Type</Label>
                            <Select value={form.data.type} onValueChange={(v) => form.setData('type', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>{types.map((t) => <SelectItem key={t} value={t}>{TYPE_LABELS[t] ?? t}</SelectItem>)}</SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Address</Label>
                            <Input value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} placeholder="20 Queen St S, Kitchener" />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Price note</Label>
                                <Input value={form.data.price_note} onChange={(e) => form.setData('price_note', e.target.value)} placeholder="from $159/night" />
                            </div>
                            <div className="grid gap-2">
                                <Label>Distance</Label>
                                <Input value={form.data.distance_note} onChange={(e) => form.setData('distance_note', e.target.value)} placeholder="5 min from venue" />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>Group / block code (optional)</Label>
                            <Input value={form.data.block_code} onChange={(e) => form.setData('block_code', e.target.value)} placeholder="SMITHWED2026" />
                        </div>
                        <div className="grid gap-2">
                            <Label>Booking link</Label>
                            <Input value={form.data.booking_url} onChange={(e) => form.setData('booking_url', e.target.value)} placeholder="https://…" />
                            <InputError message={form.errors.booking_url} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Description</Label>
                            <Textarea value={form.data.blurb} onChange={(e) => form.setData('blurb', e.target.value)} rows={2} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Photo (optional)</Label>
                            <input ref={imgRef} type="file" accept="image/*" onChange={(e) => form.setData('image', e.target.files?.[0] ?? null)} className="text-sm" />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
                            Show on the wedding website
                        </label>
                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={form.processing}>{form.processing && <Spinner />} Save</Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

TravelIndex.layout = {
    breadcrumbs: [{ title: 'Travel & stays', href: '/travel' }],
};

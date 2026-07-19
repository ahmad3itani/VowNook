import { Head, router, useForm } from '@inertiajs/react';
import { CalendarDays, MapPin, Pencil, Plus, Shirt, Trash2, Users } from 'lucide-react';
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
import { usePermissions } from '@/hooks/use-permissions';

type WeddingEvent = {
    id: number;
    name: string;
    type: string;
    event_date: string | null;
    start_time: string | null;
    end_time: string | null;
    venue_name: string | null;
    address: string | null;
    dress_code: string | null;
    description: string | null;
    is_rsvpable: boolean;
    attending_count: number;
};

const TYPE_LABELS: Record<string, string> = {
    ceremony: 'Ceremony',
    reception: 'Reception',
    rehearsal: 'Rehearsal dinner',
    welcome: 'Welcome party',
    brunch: 'Farewell brunch',
    party: 'After-party',
    other: 'Other',
};

function prettyDate(d: string | null): string {
    if (!d) return '';
    const date = new Date(`${d}T00:00:00`);
    return date.toLocaleDateString('en-CA', { weekday: 'short', month: 'short', day: 'numeric' });
}

export default function EventsIndex({ events, types }: { events: WeddingEvent[]; types: string[] }) {
    const { canWrite } = usePermissions();
    const writable = canWrite('guests');

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<WeddingEvent | null>(null);

    const form = useForm<{
        name: string; type: string; event_date: string; start_time: string; end_time: string;
        venue_name: string; address: string; dress_code: string; description: string; is_rsvpable: boolean;
    }>({
        name: '', type: 'ceremony', event_date: '', start_time: '', end_time: '',
        venue_name: '', address: '', dress_code: '', description: '', is_rsvpable: true,
    });

    function openSheet(e: WeddingEvent | null) {
        setEditing(e);
        form.clearErrors();
        form.setData({
            name: e?.name ?? '', type: e?.type ?? 'ceremony', event_date: e?.event_date ?? '',
            start_time: e?.start_time ?? '', end_time: e?.end_time ?? '', venue_name: e?.venue_name ?? '',
            address: e?.address ?? '', dress_code: e?.dress_code ?? '', description: e?.description ?? '',
            is_rsvpable: e?.is_rsvpable ?? true,
        });
        setOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { setOpen(false); toast.success('Event saved.'); } };
        if (editing) {
            form.transform((d) => ({ ...d, _method: 'put' }));
            form.post(`/events/${editing.id}`, opts);
        } else {
            form.transform((d) => d);
            form.post('/events', opts);
        }
    }

    function destroy(ev: WeddingEvent) {
        if (!confirm(`Delete "${ev.name}"?`)) return;
        router.delete(`/events/${ev.id}`, { preserveScroll: true, onSuccess: () => toast.success('Event deleted.') });
    }

    return (
        <>
            <Head title="Schedule" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Celebration schedule"
                        description="Add every event of your weekend — rehearsal dinner, welcome party, ceremony, reception, brunch. Each can collect its own RSVPs and shows on your wedding website."
                    />
                    {writable && (
                        <Button onClick={() => openSheet(null)}>
                            <Plus className="size-4" /> Add event
                        </Button>
                    )}
                </div>

                {events.length === 0 ? (
                    <Card>
                        <CardContent className="py-12 text-center text-sm text-muted-foreground">
                            No events yet. Add your ceremony, reception, and any extra gatherings.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-3">
                        {events.map((ev) => (
                            <Card key={ev.id} className="overflow-hidden">
                                <CardContent className="flex flex-wrap items-start gap-4 py-4">
                                    <div className="flex size-12 shrink-0 flex-col items-center justify-center rounded-lg bg-[#eaede5] text-[#1b4638]">
                                        <CalendarDays className="size-5" />
                                    </div>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold">{ev.name}</p>
                                            <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                {TYPE_LABELS[ev.type] ?? ev.type}
                                            </span>
                                            {ev.is_rsvpable && (
                                                <span className="inline-flex items-center gap-1 rounded-full bg-[#eaede5] px-2 py-0.5 text-xs text-[#1b4638]">
                                                    <Users className="size-3" /> {ev.attending_count} attending
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                            {(ev.event_date || ev.start_time) && (
                                                <span>{prettyDate(ev.event_date)}{ev.start_time ? ` · ${ev.start_time}` : ''}{ev.end_time ? `–${ev.end_time}` : ''}</span>
                                            )}
                                            {ev.venue_name && <span className="inline-flex items-center gap-1"><MapPin className="size-3.5" /> {ev.venue_name}</span>}
                                            {ev.dress_code && <span className="inline-flex items-center gap-1"><Shirt className="size-3.5" /> {ev.dress_code}</span>}
                                        </div>
                                        {!ev.is_rsvpable && <p className="text-xs text-muted-foreground">No RSVP — informational only</p>}
                                    </div>
                                    {writable && (
                                        <div className="flex gap-1">
                                            <Button variant="ghost" size="icon" className="size-8" onClick={() => openSheet(ev)}><Pencil className="size-4" /></Button>
                                            <Button variant="ghost" size="icon" className="size-8" onClick={() => destroy(ev)}><Trash2 className="size-4" /></Button>
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
                    <SheetHeader><SheetTitle>{editing ? 'Edit event' : 'New event'}</SheetTitle></SheetHeader>
                    <form onSubmit={submit} className="flex flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label>Name</Label>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="Welcome cocktails" />
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
                            <Label>Date</Label>
                            <Input type="date" value={form.data.event_date} onChange={(e) => form.setData('event_date', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Start time</Label>
                                <Input value={form.data.start_time} onChange={(e) => form.setData('start_time', e.target.value)} placeholder="4:00 PM" />
                            </div>
                            <div className="grid gap-2">
                                <Label>End time</Label>
                                <Input value={form.data.end_time} onChange={(e) => form.setData('end_time', e.target.value)} placeholder="11:00 PM" />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>Venue name</Label>
                            <Input value={form.data.venue_name} onChange={(e) => form.setData('venue_name', e.target.value)} placeholder="The Grand Ballroom" />
                        </div>
                        <div className="grid gap-2">
                            <Label>Address</Label>
                            <Input value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} placeholder="123 King St W, Toronto" />
                        </div>
                        <div className="grid gap-2">
                            <Label>Dress code</Label>
                            <Input value={form.data.dress_code} onChange={(e) => form.setData('dress_code', e.target.value)} placeholder="Black tie" />
                        </div>
                        <div className="grid gap-2">
                            <Label>Description (optional)</Label>
                            <Textarea value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} rows={3} />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.is_rsvpable} onChange={(e) => form.setData('is_rsvpable', e.target.checked)} />
                            Collect RSVPs for this event
                        </label>
                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={form.processing}>{form.processing && <Spinner />} Save event</Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

EventsIndex.layout = {
    breadcrumbs: [{ title: 'Schedule', href: '/events' }],
};

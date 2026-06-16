import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarClock,
    Clock,
    Download,
    FileText,
    MapPin,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';

type Option = { value: string; label: string };
type VendorRef = { id: number; name: string };

type TimelineEvent = {
    id: number;
    title: string;
    type: string;
    starts_at: string;
    ends_at: string | null;
    location: string | null;
    notes: string | null;
    vendor_id: number | null;
    vendor_name: string | null;
};

type Stats = {
    total: number;
    linked: number;
    locations: number;
    days: number;
};

type PageProps = {
    events: TimelineEvent[];
    stats: Stats;
    options: { types: Option[] };
    vendors: VendorRef[];
};

const NO_VENDOR = 'none';

const dayFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
});

const timeFormat = new Intl.DateTimeFormat('en-CA', {
    hour: 'numeric',
    minute: '2-digit',
});

const dayKey = (iso: string) => iso.slice(0, 10);

/** ISO string → the "YYYY-MM-DDTHH:MM" shape a datetime-local input expects. */
const toLocalInput = (iso: string | null) => (iso ? iso.slice(0, 16) : '');

type EventFormData = {
    title: string;
    type: string;
    starts_at: string;
    ends_at: string;
    location: string;
    vendor_id: string;
    notes: string;
};

function emptyForm(options: PageProps['options']): EventFormData {
    return {
        title: '',
        type: options.types[0]?.value ?? 'other',
        starts_at: '',
        ends_at: '',
        location: '',
        vendor_id: NO_VENDOR,
        notes: '',
    };
}

export default function TimelineIndex({
    events,
    stats,
    options,
    vendors,
}: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('timeline');

    const [search, setSearch] = useState('');
    const [typeFilter, setTypeFilter] = useState('all');
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<EventFormData>(emptyForm(options));

    const labelFor = (list: Option[], value: string) =>
        list.find((o) => o.value === value)?.label ?? value;

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return events.filter((e) => {
            const matchesType = typeFilter === 'all' || e.type === typeFilter;
            const matchesSearch =
                term === '' ||
                e.title.toLowerCase().includes(term) ||
                (e.location ?? '').toLowerCase().includes(term);

            return matchesType && matchesSearch;
        });
    }, [events, search, typeFilter]);

    const grouped = useMemo(() => {
        const map = new Map<string, TimelineEvent[]>();

        for (const event of filtered) {
            const key = dayKey(event.starts_at);
            const bucket = map.get(key) ?? [];
            bucket.push(event);
            map.set(key, bucket);
        }

        return [...map.entries()].sort(([a], [b]) => a.localeCompare(b));
    }, [filtered]);

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(event: TimelineEvent) {
        form.clearErrors();
        form.setData({
            title: event.title,
            type: event.type,
            starts_at: toLocalInput(event.starts_at),
            ends_at: toLocalInput(event.ends_at),
            location: event.location ?? '',
            vendor_id:
                event.vendor_id !== null ? String(event.vendor_id) : NO_VENDOR,
            notes: event.notes ?? '',
        });
        setEditingId(event.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            ends_at: data.ends_at === '' ? null : data.ends_at,
            vendor_id: data.vendor_id === NO_VENDOR ? null : data.vendor_id,
        }));

        const onSuccess = () => {
            toast.success(editingId ? 'Event updated.' : 'Event added.');
            setSheetOpen(false);
        };

        if (editingId) {
            form.put(`/timeline/${editingId}`, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post('/timeline', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(event: TimelineEvent) {
        if (!confirm(`Delete “${event.title}”?`)) {
            return;
        }

        router.delete(`/timeline/${event.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Event deleted.'),
        });
    }

    return (
        <>
            <Head title="Timeline" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Timeline"
                        description="Build the run-of-show and tie each moment to the vendors who make it happen."
                    />
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button variant="outline" asChild>
                            <a href="/exports/timeline">
                                <Download className="size-4" />
                                Calendar
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href="/exports/timeline/pdf">
                                <FileText className="size-4" />
                                PDF
                            </a>
                        </Button>
                        {writable && (
                            <Button onClick={openCreate} data-test="add-event">
                                <Plus className="size-4" />
                                Add event
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Events" value={String(stats.total)} />
                    <StatCard
                        label="Vendor-linked"
                        value={String(stats.linked)}
                    />
                    <StatCard
                        label="Locations"
                        value={String(stats.locations)}
                    />
                    <StatCard label="Days" value={String(stats.days)} />
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative max-w-xs flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search events, locations…"
                            className="pl-9"
                        />
                    </div>
                    <Select value={typeFilter} onValueChange={setTypeFilter}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All types</SelectItem>
                            {options.types.map((t) => (
                                <SelectItem key={t.value} value={t.value}>
                                    {t.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {grouped.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                            <CalendarClock className="size-8 opacity-40" />
                            {events.length === 0
                                ? 'No events yet. Add your first moment to start the run-of-show.'
                                : 'No events match your filters.'}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-6">
                        {grouped.map(([day, dayEvents]) => (
                            <div key={day} className="flex flex-col gap-3">
                                <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                    {dayFormat.format(new Date(day))}
                                </h2>
                                <Card>
                                    <CardContent className="flex flex-col divide-y p-0">
                                        {dayEvents.map((event) => (
                                            <div
                                                key={event.id}
                                                className="flex items-start gap-4 p-4"
                                            >
                                                <div className="flex w-20 shrink-0 flex-col text-sm text-muted-foreground tabular-nums">
                                                    <span className="flex items-center gap-1 font-medium text-foreground">
                                                        <Clock className="size-3.5" />
                                                        {timeFormat.format(
                                                            new Date(
                                                                event.starts_at,
                                                            ),
                                                        )}
                                                    </span>
                                                    {event.ends_at && (
                                                        <span className="pl-[1.125rem] text-xs">
                                                            {timeFormat.format(
                                                                new Date(
                                                                    event.ends_at,
                                                                ),
                                                            )}
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="font-medium">
                                                            {event.title}
                                                        </span>
                                                        <Badge variant="secondary">
                                                            {labelFor(
                                                                options.types,
                                                                event.type,
                                                            )}
                                                        </Badge>
                                                    </div>
                                                    <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                                        {event.location && (
                                                            <span className="flex items-center gap-1">
                                                                <MapPin className="size-3" />
                                                                {event.location}
                                                            </span>
                                                        )}
                                                        {event.vendor_name && (
                                                            <span>
                                                                {
                                                                    event.vendor_name
                                                                }
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                                {writable && (
                                                    <div className="flex shrink-0 gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                openEdit(event)
                                                            }
                                                            aria-label="Edit event"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                destroy(event)
                                                            }
                                                            aria-label="Delete event"
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>
                            {editingId ? 'Edit event' : 'Add event'}
                        </SheetTitle>
                        <SheetDescription>
                            Set the time, place, and the vendor responsible for
                            this moment.
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={submit}
                        className="flex flex-1 flex-col gap-4 px-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="title">Event</Label>
                            <Input
                                id="title"
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                                autoFocus
                            />
                            <InputError message={form.errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label>Type</Label>
                            <Select
                                value={form.data.type}
                                onValueChange={(v) => form.setData('type', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.types.map((o) => (
                                        <SelectItem
                                            key={o.value}
                                            value={o.value}
                                        >
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.type} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="starts_at">Starts</Label>
                                <Input
                                    id="starts_at"
                                    type="datetime-local"
                                    value={form.data.starts_at}
                                    onChange={(e) =>
                                        form.setData(
                                            'starts_at',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.starts_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="ends_at">Ends</Label>
                                <Input
                                    id="ends_at"
                                    type="datetime-local"
                                    value={form.data.ends_at}
                                    onChange={(e) =>
                                        form.setData('ends_at', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.ends_at} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="location">Location</Label>
                            <Input
                                id="location"
                                value={form.data.location}
                                onChange={(e) =>
                                    form.setData('location', e.target.value)
                                }
                            />
                            <InputError message={form.errors.location} />
                        </div>

                        <div className="grid gap-2">
                            <Label>Vendor</Label>
                            <Select
                                value={form.data.vendor_id}
                                onValueChange={(v) =>
                                    form.setData('vendor_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_VENDOR}>
                                        No vendor
                                    </SelectItem>
                                    {vendors.map((v) => (
                                        <SelectItem
                                            key={v.id}
                                            value={String(v.id)}
                                        >
                                            {v.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.vendor_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea
                                id="notes"
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                            />
                            <InputError message={form.errors.notes} />
                        </div>

                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {editingId ? 'Save changes' : 'Add event'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

function StatCard({
    label,
    value,
    accent,
}: {
    label: string;
    value: string;
    accent?: string;
}) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div
                    className={`mt-1 text-2xl font-semibold tabular-nums ${accent ?? ''}`}
                >
                    {value}
                </div>
            </CardContent>
        </Card>
    );
}

TimelineIndex.layout = {
    breadcrumbs: [{ title: 'Timeline', href: '/timeline' }],
};

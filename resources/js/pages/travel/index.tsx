import { Head, router, useForm } from '@inertiajs/react';
import {
    BedDouble,
    Car,
    Home,
    MapPin,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
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

const TYPE_LABELS: Record<string, string> = {
    hotel: 'Hotel',
    rental: 'Rental',
    transport: 'Transport',
};
const TYPE_ICON: Record<string, typeof BedDouble> = {
    hotel: BedDouble,
    rental: Home,
    transport: Car,
};

export default function TravelIndex({
    accommodations,
    travel_notes,
    types,
    affiliate_enabled,
    affiliate_partner,
    flights_enabled,
    flights_partner,
    show_travel_stays,
    nearest_airport,
    venue_name,
    has_venue,
    stays_preview_url,
    flights_preview_url,
}: {
    accommodations: Stay[];
    travel_notes: string;
    types: string[];
    affiliate_enabled: boolean;
    affiliate_partner: string;
    flights_enabled: boolean;
    flights_partner: string;
    show_travel_stays: boolean;
    nearest_airport: string | null;
    venue_name: string | null;
    has_venue: boolean;
    stays_preview_url: string | null;
    flights_preview_url: string | null;
}) {
    const { canWrite } = usePermissions();
    const writable = canWrite('website');

    const [showStays, setShowStays] = useState(show_travel_stays);

    function toggleStays(next: boolean) {
        setShowStays(next);
        router.put(
            '/travel/stays-visibility',
            { show_travel_stays: next },
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(
                        next
                            ? 'Travel suggestions on.'
                            : 'Travel suggestions hidden.',
                    ),
                onError: () => setShowStays(!next),
            },
        );
    }

    const airportForm = useForm({ nearest_airport: nearest_airport ?? '' });

    function saveAirport(e: React.FormEvent) {
        e.preventDefault();
        airportForm.put('/travel/airport', {
            preserveScroll: true,
            onSuccess: () => toast.success('Nearest airport saved.'),
        });
    }

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Stay | null>(null);
    const imgRef = useRef<HTMLInputElement>(null);

    const form = useForm<{
        name: string;
        type: string;
        address: string;
        blurb: string;
        booking_url: string;
        block_code: string;
        price_note: string;
        distance_note: string;
        is_active: boolean;
        image: File | null;
    }>({
        name: '',
        type: 'hotel',
        address: '',
        blurb: '',
        booking_url: '',
        block_code: '',
        price_note: '',
        distance_note: '',
        is_active: true,
        image: null,
    });

    const notesForm = useForm({ travel_notes: travel_notes ?? '' });

    function openSheet(s: Stay | null) {
        setEditing(s);
        form.clearErrors();
        form.setData({
            name: s?.name ?? '',
            type: s?.type ?? 'hotel',
            address: s?.address ?? '',
            blurb: s?.blurb ?? '',
            booking_url: s?.booking_url ?? '',
            block_code: s?.block_code ?? '',
            price_note: s?.price_note ?? '',
            distance_note: s?.distance_note ?? '',
            is_active: s?.is_active ?? true,
            image: null,
        });
        if (imgRef.current) imgRef.current.value = '';
        setOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const opts = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                toast.success('Saved.');
            },
        };
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
        router.delete(`/travel/${s.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Deleted.'),
        });
    }

    function saveNotes(e: React.FormEvent) {
        e.preventDefault();
        notesForm.put('/travel/notes', {
            preserveScroll: true,
            onSuccess: () => toast.success('Travel notes saved.'),
        });
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
                            No stays yet. Add a hotel block, a rental, or a
                            shuttle option.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {accommodations.map((s) => {
                            const Icon = TYPE_ICON[s.type] ?? BedDouble;
                            return (
                                <Card key={s.id} className="overflow-hidden">
                                    {s.image_url && (
                                        <img
                                            src={s.image_url}
                                            alt=""
                                            className="h-32 w-full object-cover"
                                        />
                                    )}
                                    <CardContent className="space-y-2 pt-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="font-semibold">
                                                    {s.name}
                                                </p>
                                                <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <Icon className="size-3.5" />{' '}
                                                    {TYPE_LABELS[s.type] ??
                                                        s.type}
                                                    {s.is_active
                                                        ? ''
                                                        : ' · hidden'}
                                                </p>
                                            </div>
                                            {writable && (
                                                <div className="flex gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7"
                                                        onClick={() =>
                                                            openSheet(s)
                                                        }
                                                    >
                                                        <Pencil className="size-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7"
                                                        onClick={() =>
                                                            destroy(s)
                                                        }
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                        {s.price_note && (
                                            <p className="text-sm text-[#1b4638]">
                                                {s.price_note}
                                            </p>
                                        )}
                                        {s.distance_note && (
                                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                <MapPin className="size-3" />{' '}
                                                {s.distance_note}
                                            </p>
                                        )}
                                        {s.block_code && (
                                            <p className="text-xs text-muted-foreground">
                                                Code:{' '}
                                                <span className="font-medium">
                                                    {s.block_code}
                                                </span>
                                            </p>
                                        )}
                                        {s.booking_url && (
                                            <p className="text-xs text-muted-foreground">
                                                Booking link added ✓
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* Travel notes */}
                <form
                    onSubmit={saveNotes}
                    className="flex flex-col gap-3 border-t pt-6"
                >
                    <div>
                        <h2 className="text-lg font-semibold">Getting there</h2>
                        <p className="text-sm text-muted-foreground">
                            Parking, shuttles, airports, directions — anything
                            that helps guests arrive.
                        </p>
                    </div>
                    <Textarea
                        value={notesForm.data.travel_notes}
                        onChange={(e) =>
                            notesForm.setData('travel_notes', e.target.value)
                        }
                        rows={4}
                        disabled={!writable}
                        placeholder="Free parking is available on-site. A shuttle runs from the host hotel at 3:30 PM…"
                    />
                    {writable && (
                        <div>
                            <Button
                                type="submit"
                                disabled={notesForm.processing}
                            >
                                {notesForm.processing && <Spinner />} Save notes
                            </Button>
                        </div>
                    )}
                </form>

                {/* Affiliate travel suggestions (hotels + flights) — only when a partner is configured. */}
                {(affiliate_enabled || flights_enabled) && (
                    <div className="flex flex-col gap-4 border-t pt-6">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Travel suggestions for your guests
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Help fly-in guests find a room and flights — and
                                earn VowNook a small commission when they book.
                            </p>
                        </div>

                        <label className="flex items-start gap-3 rounded-xl border p-4">
                            <input
                                type="checkbox"
                                className="mt-1"
                                checked={showStays}
                                disabled={!writable}
                                onChange={(e) => toggleStays(e.target.checked)}
                            />
                            <span className="text-sm">
                                <span className="font-medium">
                                    Show travel suggestions on our website
                                </span>
                                <span className="mt-1 block text-muted-foreground">
                                    {affiliate_enabled && flights_enabled
                                        ? `A live hotel map (${affiliate_partner}) and a flight search (${flights_partner}).`
                                        : affiliate_enabled
                                          ? `A live hotel map, powered by ${affiliate_partner}.`
                                          : `A flight search, powered by ${flights_partner}.`}{' '}
                                    A clear note tells guests it’s a partner
                                    suggestion — at no extra cost to them.
                                </span>
                            </span>
                        </label>

                        {affiliate_enabled && showStays && !has_venue && (
                            <p className="text-sm text-amber-700">
                                Add your venue under{' '}
                                <span className="font-medium">
                                    Website → Details
                                </span>{' '}
                                so the hotel map knows where to search.
                            </p>
                        )}

                        {flights_enabled && (
                            <form
                                onSubmit={saveAirport}
                                className="flex flex-wrap items-end gap-3 rounded-xl border p-4"
                            >
                                <div className="grid gap-1.5">
                                    <Label htmlFor="airport">
                                        Nearest airport (code)
                                    </Label>
                                    <Input
                                        id="airport"
                                        value={airportForm.data.nearest_airport}
                                        onChange={(e) =>
                                            airportForm.setData(
                                                'nearest_airport',
                                                e.target.value,
                                            )
                                        }
                                        disabled={!writable}
                                        placeholder="e.g. YYZ"
                                        className="w-32 uppercase"
                                        maxLength={60}
                                    />
                                </div>
                                {writable && (
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={airportForm.processing}
                                    >
                                        {airportForm.processing && <Spinner />}{' '}
                                        Save airport
                                    </Button>
                                )}
                                <p className="w-full text-xs text-muted-foreground">
                                    The 3-letter code of the airport guests fly
                                    into (Toronto = YYZ). The flight search
                                    appears once this is set and suggestions are
                                    on.
                                </p>
                            </form>
                        )}
                    </div>
                )}

                {/* Your own wedding-weekend travel — book it right here. */}
                {(stays_preview_url || flights_preview_url) && (
                    <div className="flex flex-col gap-3 border-t pt-6">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Book your own wedding-weekend travel
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                The same hotels &amp; flights your guests see —
                                find your own room and flights here.
                            </p>
                        </div>

                        {stays_preview_url && (
                            <div className="overflow-hidden rounded-xl border">
                                <iframe
                                    src={stays_preview_url}
                                    title="Hotels near your venue"
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                    className="h-[420px] w-full border-0"
                                    allow="geolocation"
                                />
                            </div>
                        )}

                        {flights_preview_url && (
                            <div>
                                <Button asChild variant="outline">
                                    <a
                                        href={flights_preview_url}
                                        target="_blank"
                                        rel="noopener noreferrer sponsored"
                                    >
                                        Search flights to your venue
                                    </a>
                                </Button>
                            </div>
                        )}

                        <p className="text-xs text-muted-foreground">
                            Suggestions from our travel partners (
                            {affiliate_partner} &amp; {flights_partner}).
                            VowNook may earn a small commission if you book — at
                            no extra cost to you.
                        </p>
                    </div>
                )}
            </div>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>
                            {editing ? 'Edit place' : 'New place'}
                        </SheetTitle>
                    </SheetHeader>
                    <form
                        onSubmit={submit}
                        className="flex flex-col gap-4 px-4"
                    >
                        <div className="grid gap-2">
                            <Label>Name</Label>
                            <Input
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="The Walper Hotel"
                            />
                            <InputError message={form.errors.name} />
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
                                    {types.map((t) => (
                                        <SelectItem key={t} value={t}>
                                            {TYPE_LABELS[t] ?? t}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <Label>Address</Label>
                            <Input
                                value={form.data.address}
                                onChange={(e) =>
                                    form.setData('address', e.target.value)
                                }
                                placeholder="20 Queen St S, Kitchener"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Price note</Label>
                                <Input
                                    value={form.data.price_note}
                                    onChange={(e) =>
                                        form.setData(
                                            'price_note',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="from $159/night"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Distance</Label>
                                <Input
                                    value={form.data.distance_note}
                                    onChange={(e) =>
                                        form.setData(
                                            'distance_note',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="5 min from venue"
                                />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label>Group / block code (optional)</Label>
                            <Input
                                value={form.data.block_code}
                                onChange={(e) =>
                                    form.setData('block_code', e.target.value)
                                }
                                placeholder="SMITHWED2026"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label>Booking link</Label>
                            <Input
                                value={form.data.booking_url}
                                onChange={(e) =>
                                    form.setData('booking_url', e.target.value)
                                }
                                placeholder="https://…"
                            />
                            <InputError message={form.errors.booking_url} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Description</Label>
                            <Textarea
                                value={form.data.blurb}
                                onChange={(e) =>
                                    form.setData('blurb', e.target.value)
                                }
                                rows={2}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label>Photo (optional)</Label>
                            <input
                                ref={imgRef}
                                type="file"
                                accept="image/*"
                                onChange={(e) =>
                                    form.setData(
                                        'image',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="text-sm"
                            />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(e) =>
                                    form.setData('is_active', e.target.checked)
                                }
                            />
                            Show on the wedding website
                        </label>
                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />} Save
                            </Button>
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

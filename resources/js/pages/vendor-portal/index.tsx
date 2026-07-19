import { Head, router } from '@inertiajs/react';
import { formatLongDate, formatMoney } from '@/lib/format';
import {
    CalendarDays,
    CheckCircle2,
    Clock,
    FileCheck,
    FileX,
    MapPin,
    ShieldCheck,
    ShieldX,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type VendorData = {
    id: number;
    name: string;
    category: string;
    status: string;
    status_label: string;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    cost: number | null;
    paid: number | null;
    notes: string | null;
    contract_status: string | null;
    coi_status: string | null;
    follow_up_at: string | null;
};

type TimelineItem = {
    id: number;
    title: string;
    event_type: string;
    start_time: string | null;
    end_time: string | null;
    location: string | null;
    notes: string | null;
};

type PageProps = {
    wedding: { name: string; event_date: string | null; slug: string } | null;
    vendor: VendorData | null;
    timeline: TimelineItem[];
};

const STATUS_OPTIONS = [
    { value: 'researching', label: 'Researching' },
    { value: 'contacted', label: 'Contacted' },
    { value: 'quoted', label: 'Quoted' },
    { value: 'booked', label: 'Booked' },
];

const CONTRACT_OPTIONS = [
    { value: 'pending', label: 'Pending signature' },
    { value: 'received', label: 'Sent to couple' },
    { value: 'signed', label: 'Signed ✓' },
];

const COI_OPTIONS = [
    { value: 'pending', label: 'Not yet provided' },
    { value: 'received', label: 'Submitted' },
    { value: 'on_file', label: 'On file ✓' },
];

function formatTime(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleTimeString('en-CA', { hour: '2-digit', minute: '2-digit' });
}

export default function VendorPortalIndex({ wedding, vendor, timeline }: PageProps) {
    const [notes, setNotes] = useState(vendor?.notes ?? '');
    const [saving, setSaving] = useState(false);

    if (!wedding || !vendor) {
        return (
            <>
                <Head title="Vendor Portal" />
                <div className="flex flex-1 items-center justify-center p-8 text-center text-muted-foreground">
                    <div>
                        <ShieldX className="mx-auto mb-4 size-12 opacity-30" />
                        <p className="text-lg font-medium">No vendor booking found.</p>
                        <p className="mt-1 text-sm">
                            Ask the couple to link your account to your vendor record.
                        </p>
                    </div>
                </div>
            </>
        );
    }

    function patchVendor(fields: Record<string, unknown>) {
        setSaving(true);
        router.patch(`/vendor-portal/${vendor!.id}`, fields as Record<string, string>, {
            preserveScroll: true,
            onSuccess: () => { setSaving(false); toast.success('Updated.'); },
            onError: () => setSaving(false),
        });
    }

    const balance = vendor.cost !== null && vendor.paid !== null
        ? vendor.cost - vendor.paid
        : null;

    const paidPct = vendor.cost && vendor.cost > 0 && vendor.paid !== null
        ? Math.min(100, Math.round((vendor.paid / vendor.cost) * 100))
        : 0;

    return (
        <>
            <Head title={`${vendor.name} — Vendor Portal`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div>
                    <div className="text-xs font-medium uppercase tracking-widest text-muted-foreground">
                        Vendor Portal
                    </div>
                    <h1 className="mt-1 text-2xl font-semibold">{vendor.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        {vendor.category} · {wedding.name}
                        {wedding.event_date && (
                            <> · <span className="font-medium">{formatLongDate(wedding.event_date)}</span></>
                        )}
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Left column: booking details */}
                    <div className="flex flex-col gap-4 lg:col-span-2">

                        {/* Booking status */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium">Booking status</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap items-center gap-4">
                                <Select
                                    value={vendor.status}
                                    onValueChange={(v) => patchVendor({ status: v })}
                                    disabled={saving}
                                >
                                    <SelectTrigger className="w-44">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {STATUS_OPTIONS.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Badge variant={vendor.status === 'booked' ? 'default' : 'secondary'}>
                                    {vendor.status_label}
                                </Badge>
                            </CardContent>
                        </Card>

                        {/* Payment */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium">Payment</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Contract total</span>
                                    <span className="font-semibold tabular-nums">
                                        {vendor.cost !== null ? formatMoney(vendor.cost * 100) : '—'}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Paid to date</span>
                                    <span className="font-semibold tabular-nums text-[#1b4638]">
                                        {vendor.paid !== null ? formatMoney(vendor.paid * 100) : '—'}
                                    </span>
                                </div>
                                {balance !== null && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">Balance owing</span>
                                        <span className={`font-semibold tabular-nums ${balance > 0 ? 'text-destructive' : 'text-[#1b4638]'}`}>
                                            {formatMoney(balance * 100)}
                                        </span>
                                    </div>
                                )}
                                {vendor.cost && vendor.cost > 0 && (
                                    <div className="mt-2">
                                        <div className="mb-1 flex justify-between text-xs text-muted-foreground">
                                            <span>Payment progress</span>
                                            <span>{paidPct}%</span>
                                        </div>
                                        <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-[#1b4638] transition-all"
                                                style={{ width: `${paidPct}%` }}
                                            />
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Documents */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium">Documents</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2 text-sm">
                                        {vendor.contract_status === 'signed'
                                            ? <FileCheck className="size-4 text-[#1b4638]" />
                                            : <FileX className="size-4 text-muted-foreground" />}
                                        <span>Service contract</span>
                                    </div>
                                    <Select
                                        value={vendor.contract_status ?? 'pending'}
                                        onValueChange={(v) => patchVendor({ contract_status: v })}
                                        disabled={saving}
                                    >
                                        <SelectTrigger className="w-44">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {CONTRACT_OPTIONS.map((o) => (
                                                <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2 text-sm">
                                        {vendor.coi_status === 'on_file'
                                            ? <ShieldCheck className="size-4 text-[#1b4638]" />
                                            : <ShieldX className="size-4 text-muted-foreground" />}
                                        <span>Certificate of insurance (COI)</span>
                                    </div>
                                    <Select
                                        value={vendor.coi_status ?? 'pending'}
                                        onValueChange={(v) => patchVendor({ coi_status: v })}
                                        disabled={saving}
                                    >
                                        <SelectTrigger className="w-44">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {COI_OPTIONS.map((o) => (
                                                <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Notes to couple */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium">Notes for the couple</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Textarea
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    placeholder="Leave a message, question, or update for the couple…"
                                    rows={4}
                                />
                                <Button
                                    size="sm"
                                    onClick={() => patchVendor({ notes })}
                                    disabled={saving || notes === vendor.notes}
                                >
                                    Save note
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right column: schedule */}
                    <div className="flex flex-col gap-4">
                        <Card className="flex-1">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                    <CalendarDays className="size-4" />
                                    Day-of schedule
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {timeline.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        The couple hasn't set up their timeline yet.
                                    </p>
                                ) : (
                                    <ol className="relative border-l border-border pl-5">
                                        {timeline.map((event) => (
                                            <li key={event.id} className="mb-6 last:mb-0">
                                                <div className="absolute -left-1.5 mt-1.5 size-3 rounded-full border border-background bg-[#1b4638]" />
                                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                    <Clock className="size-3" />
                                                    {event.start_time ? formatTime(event.start_time) : 'TBD'}
                                                    {event.end_time && ` – ${formatTime(event.end_time)}`}
                                                </div>
                                                <p className="mt-0.5 font-medium">{event.title}</p>
                                                {event.location && (
                                                    <div className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                                        <MapPin className="size-3" />
                                                        {event.location}
                                                    </div>
                                                )}
                                                {event.notes && (
                                                    <p className="mt-1 text-xs text-muted-foreground">{event.notes}</p>
                                                )}
                                            </li>
                                        ))}
                                    </ol>
                                )}
                            </CardContent>
                        </Card>

                        {/* Contact info */}
                        {(vendor.contact_name || vendor.email || vendor.phone) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium">Your contact on file</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-1 text-sm">
                                    {vendor.contact_name && <p className="font-medium">{vendor.contact_name}</p>}
                                    {vendor.email && <p className="text-muted-foreground">{vendor.email}</p>}
                                    {vendor.phone && <p className="text-muted-foreground">{vendor.phone}</p>}
                                </CardContent>
                            </Card>
                        )}

                        {/* All-clear when booked + signed */}
                        {vendor.status === 'booked' && vendor.contract_status === 'signed' && (
                            <div className="flex items-center gap-2 rounded-lg border border-[#1b4638]/30 bg-[#1b4638]/5 px-4 py-3 text-sm text-[#1b4638]">
                                <CheckCircle2 className="size-4 shrink-0" />
                                <span>You're all set — booked and contract signed.</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

VendorPortalIndex.layout = {
    breadcrumbs: [{ title: 'Vendor Portal', href: '/vendor-portal' }],
};

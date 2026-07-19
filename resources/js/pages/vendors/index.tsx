import { Head, Link, router, useForm } from '@inertiajs/react';
import { formatMoney } from '@/lib/format';
import {
    Briefcase,
    ExternalLink,
    FileCheck,
    Pencil,
    Plus,
    Scale,
    Search,
    ShieldCheck,
    Trash2,
    UserPlus,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { VendorsHubTabs } from '@/components/vendors-hub-tabs';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

type Vendor = {
    id: number;
    name: string;
    category: string;
    status: string;
    rating: number | null;
    price_level: number | null;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    cost: number | null;
    paid: number | null;
    notes: string | null;
    follow_up_at: string | null;
    contract_status: string | null;
    coi_status: string | null;
    vendor_user_id: number | null;
};

type Stats = {
    total: number;
    booked: number;
    contracted: number;
    paid: number;
};

type PageProps = {
    vendors: Vendor[];
    stats: Stats;
    options: { categories: Option[]; statuses: Option[] };
    quote_badge: number;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    booked: 'default',
    quoted: 'secondary',
    contacted: 'secondary',
    researching: 'outline',
    declined: 'destructive',
};

// Ordered pipeline stages (not including declined)
const PIPELINE_STAGES = ['researching', 'contacted', 'quoted', 'booked'] as const;

const STAGE_COLORS: Record<string, string> = {
    researching: 'bg-muted-foreground/30',
    contacted:   'bg-amber-400/70',
    quoted:      'bg-amber-600/80',
    booked:      'bg-[#1b4638]',
    declined:    'bg-destructive/40',
};

const STAGE_DOT: Record<string, string> = {
    researching: 'bg-muted-foreground/50',
    contacted:   'bg-amber-400',
    quoted:      'bg-amber-600',
    booked:      'bg-[#1b4638]',
    declined:    'bg-destructive',
};

// Pipeline sort order
const STAGE_ORDER: Record<string, number> = {
    researching: 0,
    contacted: 1,
    quoted: 2,
    booked: 3,
    declined: 4,
};

type VendorFormData = {
    name: string;
    category: string;
    status: string;
    rating: string;
    price_level: string;
    contact_name: string;
    email: string;
    phone: string;
    website: string;
    cost_amount: string;
    paid_amount: string;
    notes: string;
    follow_up_at: string;
    contract_status: string;
    coi_status: string;
};

function emptyForm(options: PageProps['options']): VendorFormData {
    return {
        name: '',
        category: options.categories[0]?.value ?? 'other',
        status: options.statuses[0]?.value ?? 'researching',
        rating: '',
        price_level: '',
        contact_name: '',
        email: '',
        phone: '',
        website: '',
        cost_amount: '',
        paid_amount: '0',
        notes: '',
        follow_up_at: '',
        contract_status: '',
        coi_status: '',
    };
}

export default function VendorsIndex({ vendors, stats, options, quote_badge }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('vendors');

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [categoryFilter, setCategoryFilter] = useState('all');
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<VendorFormData>(emptyForm(options));

    const labelFor = (list: Option[], value: string) =>
        list.find((o) => o.value === value)?.label ?? value;

    // Per-stage counts for pipeline bar
    const stageCounts = useMemo(() => {
        const counts: Record<string, number> = {};
        for (const v of vendors) {
            counts[v.status] = (counts[v.status] ?? 0) + 1;
        }
        return counts;
    }, [vendors]);

    const nonDeclinedTotal = vendors.filter((v) => v.status !== 'declined').length;

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();
        return vendors
            .filter((v) => {
                if (statusFilter !== 'all' && v.status !== statusFilter) return false;
                if (categoryFilter !== 'all' && v.category !== categoryFilter) return false;
                if (
                    term &&
                    !v.name.toLowerCase().includes(term) &&
                    !(v.contact_name ?? '').toLowerCase().includes(term) &&
                    !(v.email ?? '').toLowerCase().includes(term)
                ) return false;
                return true;
            })
            .sort((a, b) => (STAGE_ORDER[a.status] ?? 0) - (STAGE_ORDER[b.status] ?? 0));
    }, [vendors, search, statusFilter, categoryFilter]);

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(vendor: Vendor) {
        form.clearErrors();
        form.setData({
            name: vendor.name,
            category: vendor.category,
            status: vendor.status,
            rating: vendor.rating !== null ? String(vendor.rating) : '',
            price_level: vendor.price_level !== null ? String(vendor.price_level) : '',
            contact_name: vendor.contact_name ?? '',
            email: vendor.email ?? '',
            phone: vendor.phone ?? '',
            website: vendor.website ?? '',
            cost_amount: vendor.cost !== null ? String(vendor.cost) : '',
            paid_amount: String(vendor.paid ?? 0),
            notes: vendor.notes ?? '',
            follow_up_at: vendor.follow_up_at ?? '',
            contract_status: vendor.contract_status ?? '',
            coi_status: vendor.coi_status ?? '',
        });
        setEditingId(vendor.id);
        setSheetOpen(true);
    }

    function inviteVendor(vendor: Vendor) {
        if (!vendor.email) {
            toast.error('Add the vendor\'s email address first.');
            return;
        }
        router.post('/collaborators', { email: vendor.email, role: 'vendor' }, {
            preserveScroll: true,
            onSuccess: () => toast.success(`Invite sent to ${vendor.email}.`),
            onError: (e) => toast.error(Object.values(e)[0] as string),
        });
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            cost_amount: data.cost_amount === '' ? null : data.cost_amount,
            rating: data.rating === '' ? null : Number(data.rating),
            price_level: data.price_level === '' ? null : Number(data.price_level),
        }));
        const onSuccess = () => {
            toast.success(editingId ? 'Vendor updated.' : 'Vendor added.');
            setSheetOpen(false);
        };
        if (editingId) {
            form.put(`/vendors/${editingId}`, { preserveScroll: true, onSuccess });
        } else {
            form.post('/vendors', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(vendor: Vendor) {
        if (!confirm(`Remove ${vendor.name} from your vendors?`)) return;
        router.delete(`/vendors/${vendor.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Vendor removed.'),
        });
    }

    const bookedPct = stats.total > 0 ? Math.round((stats.booked / stats.total) * 100) : 0;

    return (
        <>
            <Head title="Vendors" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Vendors"
                        description="Track the people and businesses bringing your day to life."
                    />
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/vendors/compare">
                                <Scale className="size-4" />
                                Compare
                            </Link>
                        </Button>
                        {writable && (
                            <Button onClick={openCreate} data-test="add-vendor">
                                <Plus className="size-4" />
                                Add vendor
                            </Button>
                        )}
                    </div>
                </div>

                <VendorsHubTabs active="shortlist" quoteBadge={quote_badge} />

                {/* Stats row */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Vendors" value={String(stats.total)} />
                    <StatCard label="Booked" value={String(stats.booked)} accent="text-[#1b4638]" />
                    <StatCard label="Contracted" value={formatMoney(stats.contracted * 100)} />
                    <StatCard label="Paid" value={formatMoney(stats.paid * 100)} accent="text-[#1b4638]" />
                </div>

                {/* Pipeline funnel */}
                {vendors.length > 0 && (
                    <Card>
                        <CardHeader className="pb-3 pt-4">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Booking pipeline
                                </CardTitle>
                                <span className="text-xs text-muted-foreground">
                                    {bookedPct}% booked
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-0">
                            {/* Stacked progress bar */}
                            <div className="mb-4 flex h-3 w-full overflow-hidden rounded-full bg-muted">
                                {PIPELINE_STAGES.map((stage) => {
                                    const count = stageCounts[stage] ?? 0;
                                    const pct = nonDeclinedTotal > 0
                                        ? (count / nonDeclinedTotal) * 100
                                        : 0;
                                    if (pct === 0) return null;
                                    return (
                                        <button
                                            key={stage}
                                            type="button"
                                            title={`${labelFor(options.statuses, stage)}: ${count}`}
                                            onClick={() => setStatusFilter(statusFilter === stage ? 'all' : stage)}
                                            className={`h-full cursor-pointer transition-opacity hover:opacity-80 ${STAGE_COLORS[stage]}`}
                                            style={{ width: `${pct}%` }}
                                        />
                                    );
                                })}
                            </div>

                            {/* Stage legend */}
                            <div className="flex flex-wrap gap-x-6 gap-y-2">
                                {PIPELINE_STAGES.map((stage) => {
                                    const count = stageCounts[stage] ?? 0;
                                    const active = statusFilter === stage;
                                    return (
                                        <button
                                            key={stage}
                                            type="button"
                                            onClick={() => setStatusFilter(active ? 'all' : stage)}
                                            className={`flex items-center gap-1.5 text-xs transition-opacity ${
                                                active ? 'font-semibold' : 'text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            <span className={`size-2 rounded-full ${STAGE_DOT[stage]}`} />
                                            {labelFor(options.statuses, stage)}
                                            <span className="tabular-nums text-muted-foreground">({count})</span>
                                        </button>
                                    );
                                })}
                                {(stageCounts['declined'] ?? 0) > 0 && (
                                    <button
                                        type="button"
                                        onClick={() => setStatusFilter(statusFilter === 'declined' ? 'all' : 'declined')}
                                        className={`flex items-center gap-1.5 text-xs transition-opacity ${
                                            statusFilter === 'declined' ? 'font-semibold' : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        <span className={`size-2 rounded-full ${STAGE_DOT['declined']}`} />
                                        {labelFor(options.statuses, 'declined')}
                                        <span className="tabular-nums text-muted-foreground">({stageCounts['declined']})</span>
                                    </button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Filters */}
                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative max-w-xs flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search name, contact, email…"
                            className="pl-9"
                        />
                    </div>

                    <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            {options.categories.map((c) => (
                                <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {options.statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {filtered.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                                <Briefcase className="size-8 opacity-40" />
                                {vendors.length === 0
                                    ? 'No vendors yet. Add your first vendor to get started.'
                                    : 'No vendors match your filters.'}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Vendor</th>
                                            <th className="px-4 py-3 font-medium">Category</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 text-right font-medium">Cost</th>
                                            <th className="px-4 py-3 text-right font-medium">Paid</th>
                                            <th className="px-4 py-3 font-medium">Docs</th>
                                            {writable && <th className="px-4 py-3" />}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((v) => (
                                            <tr key={v.id} className="border-b last:border-0">
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2 font-medium">
                                                        <span
                                                            className={`size-2 shrink-0 rounded-full ${STAGE_DOT[v.status] ?? 'bg-muted'}`}
                                                        />
                                                        {v.name}
                                                        {v.website && (
                                                            <a
                                                                href={v.website}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="text-muted-foreground hover:text-foreground"
                                                            >
                                                                <ExternalLink className="size-3.5" />
                                                            </a>
                                                        )}
                                                    </div>
                                                    {v.contact_name && (
                                                        <span className="ml-3.5 text-xs text-muted-foreground">
                                                            {v.contact_name}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {labelFor(options.categories, v.category)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={STATUS_VARIANT[v.status] ?? 'secondary'}>
                                                        {labelFor(options.statuses, v.status)}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {v.cost !== null ? formatMoney(v.cost * 100) : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {formatMoney((v.paid ?? 0) * 100)}
                                                </td>
                                                {/* Contract + COI status indicators */}
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-1.5">
                                                        {v.contract_status === 'signed' && (
                                                            <span title="Contract signed">
                                                                <FileCheck className="size-3.5 text-[#1b4638]" />
                                                            </span>
                                                        )}
                                                        {v.coi_status === 'on_file' && (
                                                            <span title="COI on file">
                                                                <ShieldCheck className="size-3.5 text-[#1b4638]" />
                                                            </span>
                                                        )}
                                                        {v.follow_up_at && (
                                                            <span className="text-xs text-muted-foreground" title={`Follow up: ${v.follow_up_at}`}>
                                                                ↺ {v.follow_up_at}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                {writable && (
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            {!v.vendor_user_id && v.email && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => inviteVendor(v)}
                                                                    aria-label="Invite to vendor portal"
                                                                    title="Invite to vendor portal"
                                                                >
                                                                    <UserPlus className="size-4" />
                                                                </Button>
                                                            )}
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => openEdit(v)}
                                                                aria-label="Edit vendor"
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => destroy(v)}
                                                                aria-label="Remove vendor"
                                                            >
                                                                <Trash2 className="size-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>{editingId ? 'Edit vendor' : 'Add vendor'}</SheetTitle>
                        <SheetDescription>
                            Track contact details, booking status, and contract amounts.
                        </SheetDescription>
                    </SheetHeader>

                    <form onSubmit={submit} className="flex flex-1 flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Business name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                autoFocus
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Category</Label>
                                <Select value={form.data.category} onValueChange={(v) => form.setData('category', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {options.categories.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.category} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select value={form.data.status} onValueChange={(v) => form.setData('status', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {options.statuses.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.status} />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Rating</Label>
                                <Select
                                    value={form.data.rating || 'none'}
                                    onValueChange={(v) => form.setData('rating', v === 'none' ? '' : v)}
                                >
                                    <SelectTrigger><SelectValue placeholder="Not rated" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Not rated</SelectItem>
                                        {[1, 2, 3, 4, 5].map((n) => (
                                            <SelectItem key={n} value={String(n)}>
                                                {'★'.repeat(n)} ({n})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.rating} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Price level</Label>
                                <Select
                                    value={form.data.price_level || 'none'}
                                    onValueChange={(v) => form.setData('price_level', v === 'none' ? '' : v)}
                                >
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">—</SelectItem>
                                        {[1, 2, 3, 4].map((n) => (
                                            <SelectItem key={n} value={String(n)}>{'$'.repeat(n)}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.price_level} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="contact_name">Contact name</Label>
                            <Input
                                id="contact_name"
                                value={form.data.contact_name}
                                onChange={(e) => form.setData('contact_name', e.target.value)}
                            />
                            <InputError message={form.errors.contact_name} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                />
                                <InputError message={form.errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                />
                                <InputError message={form.errors.phone} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="website">Website</Label>
                            <Input
                                id="website"
                                type="url"
                                placeholder="https://"
                                value={form.data.website}
                                onChange={(e) => form.setData('website', e.target.value)}
                            />
                            <InputError message={form.errors.website} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="cost_amount">Contract cost</Label>
                                <Input
                                    id="cost_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.cost_amount}
                                    onChange={(e) => form.setData('cost_amount', e.target.value)}
                                />
                                <InputError message={form.errors.cost_amount} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="paid_amount">Paid</Label>
                                <Input
                                    id="paid_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.paid_amount}
                                    onChange={(e) => form.setData('paid_amount', e.target.value)}
                                />
                                <InputError message={form.errors.paid_amount} />
                            </div>
                        </div>

                        {/* Vendor management fields */}
                        <div className="grid grid-cols-3 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="follow_up_at">Follow up</Label>
                                <Input
                                    id="follow_up_at"
                                    type="date"
                                    value={form.data.follow_up_at}
                                    onChange={(e) => form.setData('follow_up_at', e.target.value)}
                                />
                                <InputError message={form.errors.follow_up_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Contract</Label>
                                <Select
                                    value={form.data.contract_status || 'none'}
                                    onValueChange={(v) => form.setData('contract_status', v === 'none' ? '' : v)}
                                >
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">—</SelectItem>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="received">Received</SelectItem>
                                        <SelectItem value="signed">Signed ✓</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>COI</Label>
                                <Select
                                    value={form.data.coi_status || 'none'}
                                    onValueChange={(v) => form.setData('coi_status', v === 'none' ? '' : v)}
                                >
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">—</SelectItem>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="received">Received</SelectItem>
                                        <SelectItem value="on_file">On file ✓</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea
                                id="notes"
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                            />
                            <InputError message={form.errors.notes} />
                        </div>

                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {editingId ? 'Save changes' : 'Add vendor'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

function StatCard({ label, value, accent }: { label: string; value: string; accent?: string }) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className={`mt-1 text-2xl font-semibold tabular-nums ${accent ?? ''}`}>{value}</div>
            </CardContent>
        </Card>
    );
}

VendorsIndex.layout = {
    breadcrumbs: [{ title: 'Vendors', href: '/vendors' }],
};

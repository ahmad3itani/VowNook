import { Head, router, useForm } from '@inertiajs/react';
import {
    Briefcase,
    ExternalLink,
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

type Vendor = {
    id: number;
    name: string;
    category: string;
    status: string;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    cost: number | null;
    paid: number | null;
    notes: string | null;
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
};

const STATUS_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    booked: 'default',
    quoted: 'secondary',
    contacted: 'secondary',
    researching: 'outline',
    declined: 'destructive',
};

const money = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 0,
});

type VendorFormData = {
    name: string;
    category: string;
    status: string;
    contact_name: string;
    email: string;
    phone: string;
    website: string;
    cost_amount: string;
    paid_amount: string;
    notes: string;
};

function emptyForm(options: PageProps['options']): VendorFormData {
    return {
        name: '',
        category: options.categories[0]?.value ?? 'other',
        status: options.statuses[0]?.value ?? 'researching',
        contact_name: '',
        email: '',
        phone: '',
        website: '',
        cost_amount: '',
        paid_amount: '0',
        notes: '',
    };
}

export default function VendorsIndex({ vendors, stats, options }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('vendors');

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<VendorFormData>(emptyForm(options));

    const labelFor = (list: Option[], value: string) =>
        list.find((o) => o.value === value)?.label ?? value;

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return vendors.filter((v) => {
            const matchesStatus =
                statusFilter === 'all' || v.status === statusFilter;
            const matchesSearch =
                term === '' ||
                v.name.toLowerCase().includes(term) ||
                (v.contact_name ?? '').toLowerCase().includes(term) ||
                (v.email ?? '').toLowerCase().includes(term);

            return matchesStatus && matchesSearch;
        });
    }, [vendors, search, statusFilter]);

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
            contact_name: vendor.contact_name ?? '',
            email: vendor.email ?? '',
            phone: vendor.phone ?? '',
            website: vendor.website ?? '',
            cost_amount: vendor.cost !== null ? String(vendor.cost) : '',
            paid_amount: String(vendor.paid ?? 0),
            notes: vendor.notes ?? '',
        });
        setEditingId(vendor.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            cost_amount: data.cost_amount === '' ? null : data.cost_amount,
        }));

        const onSuccess = () => {
            toast.success(editingId ? 'Vendor updated.' : 'Vendor added.');
            setSheetOpen(false);
        };

        if (editingId) {
            form.put(`/vendors/${editingId}`, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post('/vendors', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(vendor: Vendor) {
        if (!confirm(`Remove ${vendor.name} from your vendors?`)) {
            return;
        }

        router.delete(`/vendors/${vendor.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Vendor removed.'),
        });
    }

    return (
        <>
            <Head title="Vendors" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Vendors"
                        description="Track the people and businesses bringing your day to life."
                    />
                    {writable && (
                        <Button onClick={openCreate} data-test="add-vendor">
                            <Plus className="size-4" />
                            Add vendor
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Vendors" value={String(stats.total)} />
                    <StatCard
                        label="Booked"
                        value={String(stats.booked)}
                        accent="text-emerald-600"
                    />
                    <StatCard
                        label="Contracted"
                        value={money.format(stats.contracted)}
                    />
                    <StatCard
                        label="Paid"
                        value={money.format(stats.paid)}
                        accent="text-emerald-600"
                    />
                </div>

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
                    <Select
                        value={statusFilter}
                        onValueChange={setStatusFilter}
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {options.statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>
                                    {s.label}
                                </SelectItem>
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
                                            <th className="px-4 py-3 font-medium">
                                                Vendor
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Category
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Cost
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Paid
                                            </th>
                                            {writable && (
                                                <th className="px-4 py-3" />
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((v) => (
                                            <tr
                                                key={v.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2 font-medium">
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
                                                        <span className="text-xs text-muted-foreground">
                                                            {v.contact_name}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {labelFor(
                                                        options.categories,
                                                        v.category,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            STATUS_VARIANT[
                                                                v.status
                                                            ] ?? 'secondary'
                                                        }
                                                    >
                                                        {labelFor(
                                                            options.statuses,
                                                            v.status,
                                                        )}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {v.cost !== null
                                                        ? money.format(v.cost)
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {money.format(v.paid ?? 0)}
                                                </td>
                                                {writable && (
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    openEdit(v)
                                                                }
                                                                aria-label="Edit vendor"
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    destroy(v)
                                                                }
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
                        <SheetTitle>
                            {editingId ? 'Edit vendor' : 'Add vendor'}
                        </SheetTitle>
                        <SheetDescription>
                            Track contact details, booking status, and contract
                            amounts.
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={submit}
                        className="flex flex-1 flex-col gap-4 px-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Business name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                autoFocus
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Category</Label>
                                <Select
                                    value={form.data.category}
                                    onValueChange={(v) =>
                                        form.setData('category', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.categories.map((o) => (
                                            <SelectItem
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.category} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select
                                    value={form.data.status}
                                    onValueChange={(v) =>
                                        form.setData('status', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.statuses.map((o) => (
                                            <SelectItem
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.status} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="contact_name">Contact name</Label>
                            <Input
                                id="contact_name"
                                value={form.data.contact_name}
                                onChange={(e) =>
                                    form.setData('contact_name', e.target.value)
                                }
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
                                    onChange={(e) =>
                                        form.setData('email', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
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
                                onChange={(e) =>
                                    form.setData('website', e.target.value)
                                }
                            />
                            <InputError message={form.errors.website} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="cost_amount">
                                    Contract cost
                                </Label>
                                <Input
                                    id="cost_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.cost_amount}
                                    onChange={(e) =>
                                        form.setData(
                                            'cost_amount',
                                            e.target.value,
                                        )
                                    }
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
                                    onChange={(e) =>
                                        form.setData(
                                            'paid_amount',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.paid_amount} />
                            </div>
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
                                {editingId ? 'Save changes' : 'Add vendor'}
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

VendorsIndex.layout = {
    breadcrumbs: [{ title: 'Vendors', href: '/vendors' }],
};

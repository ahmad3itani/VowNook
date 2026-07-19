import { useForm } from '@inertiajs/react';
import { Head, router } from '@inertiajs/react';
import { formatMoney } from '@/lib/format';
import {
    Eye,
    EyeOff,
    PackageOpen,
    Pencil,
    Plus,
    Tag,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Service = {
    id: number;
    name: string;
    description: string | null;
    price_cents: number | null;
    price_unit: string | null;
    price_type: string;
    is_active: boolean;
    sort_order: number;
};

type ServicePayload = {
    name: string;
    description: string | null;
    price_type: string;
    price_cents: number | null;
    price_unit: string | null;
    is_active: boolean;
};

type PageProps = {
    services: Service[];
};

const PRICE_TYPES = [
    { value: 'fixed', label: 'Fixed price' },
    { value: 'from', label: 'Starting from' },
    { value: 'quote_only', label: 'Quote only' },
];

const PRICE_UNITS = [
    { value: 'per_event', label: 'Per event' },
    { value: 'per_hour', label: 'Per hour' },
    { value: 'per_person', label: 'Per person' },
];

function priceLabel(service: Service) {
    if (service.price_type === 'quote_only') return 'Quote only';
    if (!service.price_cents) return '—';
    const amt = formatMoney(service.price_cents);
    const unit = PRICE_UNITS.find((u) => u.value === service.price_unit)?.label ?? '';
    const prefix = service.price_type === 'from' ? 'From ' : '';
    return `${prefix}${amt}${unit ? ` / ${unit.toLowerCase()}` : ''}`;
}

function ServiceForm({
    initial,
    onSave,
    onCancel,
    submitLabel,
}: {
    initial?: Partial<Service>;
    onSave: (data: ServicePayload) => void;
    onCancel: () => void;
    submitLabel: string;
}) {
    const { data, setData, processing } = useForm({
        name: initial?.name ?? '',
        description: initial?.description ?? '',
        price_type: initial?.price_type ?? 'fixed',
        price_cents: initial?.price_cents ?? ('' as number | ''),
        price_unit: initial?.price_unit ?? 'per_event',
        is_active: initial?.is_active ?? true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        onSave({
            name: data.name,
            description: data.description || null,
            price_type: data.price_type,
            price_cents: data.price_cents === '' ? null : Number(data.price_cents),
            price_unit: data.price_cents === '' || data.price_type === 'quote_only' ? null : (data.price_unit || null),
            is_active: data.is_active,
        });
    }

    return (
        <form onSubmit={handleSubmit} className="grid gap-4 p-4">
            <div>
                <Label htmlFor="svc-name">Service name</Label>
                <Input
                    id="svc-name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                    className="mt-1"
                />
            </div>

            <div>
                <Label htmlFor="svc-desc">Description</Label>
                <Textarea
                    id="svc-desc"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    placeholder="What's included, any conditions…"
                    className="mt-1"
                />
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div>
                    <Label htmlFor="svc-price-type">Pricing type</Label>
                    <Select value={data.price_type} onValueChange={(v: string) => setData('price_type', v)}>
                        <SelectTrigger id="svc-price-type" className="mt-1">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {PRICE_TYPES.map((t) => (
                                <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {data.price_type !== 'quote_only' && (
                    <>
                        <div>
                            <Label htmlFor="svc-price">Amount (CAD cents)</Label>
                            <Input
                                id="svc-price"
                                type="number"
                                min={0}
                                value={data.price_cents}
                                onChange={(e) =>
                                    setData('price_cents', e.target.value === '' ? '' : Number(e.target.value))
                                }
                                placeholder="e.g. 250000"
                                className="mt-1"
                            />
                            {data.price_cents !== '' && Number(data.price_cents) > 0 && (
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    ${(Number(data.price_cents) / 100).toLocaleString('en-CA')} CAD
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="svc-price-unit">Unit</Label>
                            <Select value={data.price_unit ?? ''} onValueChange={(v: string) => setData('price_unit', v)}>
                                <SelectTrigger id="svc-price-unit" className="mt-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {PRICE_UNITS.map((u) => (
                                        <SelectItem key={u.value} value={u.value}>{u.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </>
                )}
            </div>

            <div className="flex items-center gap-3">
                <Checkbox
                    id="svc-active"
                    checked={data.is_active}
                    onCheckedChange={(v: boolean) => setData('is_active', v)}
                />
                <Label htmlFor="svc-active">Active (visible to couples)</Label>
            </div>

            <div className="flex items-center gap-2">
                <Button type="submit" size="sm" disabled={processing || !data.name}>
                    {submitLabel}
                </Button>
                <Button type="button" size="sm" variant="ghost" onClick={onCancel}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}

export default function VendorServicesPage({ services }: PageProps) {
    const [adding, setAdding] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    function addService(data: ServicePayload) {
        router.post('/vendor/services', data as unknown as Record<string, string>, {
            preserveScroll: true,
            onSuccess: () => setAdding(false),
        });
    }

    function updateService(id: number, data: ServicePayload) {
        router.put(`/vendor/services/${id}`, data as unknown as Record<string, string>, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    }

    function toggleService(id: number) {
        router.patch(`/vendor/services/${id}/toggle`, {}, { preserveScroll: true });
    }

    function deleteService(id: number) {
        if (!confirm('Delete this service?')) return;
        router.delete(`/vendor/services/${id}`, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Services" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Services & packages"
                        description="List the services you offer. Couples can browse these and request a quote."
                    />
                    {!adding && (
                        <Button size="sm" onClick={() => setAdding(true)}>
                            <Plus className="mr-1.5 size-4" />
                            Add service
                        </Button>
                    )}
                </div>

                {/* Add new service card */}
                {adding && (
                    <Card className="border-primary/40">
                        <CardHeader className="pb-0">
                            <CardTitle className="text-sm text-primary">New service</CardTitle>
                        </CardHeader>
                        <ServiceForm
                            onSave={addService}
                            onCancel={() => setAdding(false)}
                            submitLabel="Add service"
                        />
                    </Card>
                )}

                {/* Services list */}
                {services.length === 0 && !adding ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-3 py-12">
                            <PackageOpen className="size-10 text-muted-foreground/40" />
                            <p className="text-sm text-muted-foreground">No services yet. Add one to get started.</p>
                            <Button size="sm" onClick={() => setAdding(true)}>
                                <Plus className="mr-1.5 size-4" />
                                Add your first service
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-3">
                        {services.map((service) => (
                            <Card key={service.id} className={service.is_active ? '' : 'opacity-60'}>
                                {editingId === service.id ? (
                                    <>
                                        <CardHeader className="pb-0">
                                            <CardTitle className="text-sm">Edit service</CardTitle>
                                        </CardHeader>
                                        <ServiceForm
                                            initial={service}
                                            onSave={(data) => updateService(service.id, data)}
                                            onCancel={() => setEditingId(null)}
                                            submitLabel="Save changes"
                                        />
                                    </>
                                ) : (
                                    <CardContent className="flex flex-wrap items-start justify-between gap-4 py-4">
                                        <div className="flex-1 space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium">{service.name}</span>
                                                <Badge variant={service.is_active ? 'default' : 'secondary'} className="text-xs">
                                                    {service.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </div>
                                            {service.description && (
                                                <p className="text-sm text-muted-foreground">{service.description}</p>
                                            )}
                                            <p className="flex items-center gap-1 text-sm font-medium text-[#1b4638]">
                                                <Tag className="size-3.5" />
                                                {priceLabel(service)}
                                            </p>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                title={service.is_active ? 'Deactivate' : 'Activate'}
                                                onClick={() => toggleService(service.id)}
                                            >
                                                {service.is_active ? (
                                                    <EyeOff className="size-4" />
                                                ) : (
                                                    <Eye className="size-4" />
                                                )}
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8"
                                                onClick={() => setEditingId(service.id)}
                                            >
                                                <Pencil className="size-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                onClick={() => deleteService(service.id)}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </CardContent>
                                )}
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

VendorServicesPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/vendor' },
        { title: 'Services', href: '/vendor/services' },
    ],
};

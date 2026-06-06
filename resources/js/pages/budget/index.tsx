import { Head, router, useForm } from '@inertiajs/react';
import { Download, Layers, Pencil, Plus, Trash2, Wallet } from 'lucide-react';
import { useMemo, useState } from 'react';
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
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';

type Item = {
    id: number;
    name: string;
    estimated: number;
    actual: number | null;
    paid: number;
    due_date: string | null;
    notes: string | null;
    category_id: number | null;
    category_name: string | null;
};

type Category = { id: number; name: string };

type Stats = {
    estimated: number;
    projected: number;
    paid: number;
    outstanding: number;
};

type PageProps = {
    items: Item[];
    categories: Category[];
    stats: Stats;
};

const NO_CATEGORY = 'none';

const money = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 0,
});

type ItemFormData = {
    name: string;
    category_id: string;
    estimated_amount: string;
    actual_amount: string;
    paid_amount: string;
    due_date: string;
    notes: string;
};

const EMPTY: ItemFormData = {
    name: '',
    category_id: NO_CATEGORY,
    estimated_amount: '',
    actual_amount: '',
    paid_amount: '0',
    due_date: '',
    notes: '',
};

export default function BudgetIndex({ items, categories, stats }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('budget');

    const [categoryFilter, setCategoryFilter] = useState('all');
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [categoriesOpen, setCategoriesOpen] = useState(false);

    const form = useForm<ItemFormData>({ ...EMPTY });

    const filtered = useMemo(() => {
        if (categoryFilter === 'all') {
            return items;
        }

        if (categoryFilter === NO_CATEGORY) {
            return items.filter((i) => i.category_id === null);
        }

        return items.filter((i) => String(i.category_id) === categoryFilter);
    }, [items, categoryFilter]);

    function openCreate() {
        form.clearErrors();
        form.setDefaults({ ...EMPTY });
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(item: Item) {
        form.clearErrors();
        form.setData({
            name: item.name,
            category_id: item.category_id
                ? String(item.category_id)
                : NO_CATEGORY,
            estimated_amount: String(item.estimated),
            actual_amount: item.actual !== null ? String(item.actual) : '',
            paid_amount: String(item.paid),
            due_date: item.due_date ?? '',
            notes: item.notes ?? '',
        });
        setEditingId(item.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            category_id:
                data.category_id === NO_CATEGORY
                    ? null
                    : Number(data.category_id),
            actual_amount:
                data.actual_amount === '' ? null : data.actual_amount,
        }));

        const onSuccess = () => {
            toast.success(editingId ? 'Item updated.' : 'Item added.');
            setSheetOpen(false);
        };

        if (editingId) {
            form.put(`/budget/${editingId}`, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post('/budget', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(item: Item) {
        if (!confirm(`Delete "${item.name}" from the budget?`)) {
            return;
        }

        router.delete(`/budget/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Item deleted.'),
        });
    }

    return (
        <>
            <Head title="Budget" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Budget"
                        description="Plan estimated costs, track what you actually spend, and stay on target."
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a href="/exports/budget">
                                <Download className="size-4" />
                                Export CSV
                            </a>
                        </Button>
                        {writable && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => setCategoriesOpen(true)}
                                >
                                    <Layers className="size-4" />
                                    Categories
                                </Button>
                                <Button
                                    onClick={openCreate}
                                    data-test="add-item"
                                >
                                    <Plus className="size-4" />
                                    Add item
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Estimated"
                        value={money.format(stats.estimated)}
                    />
                    <StatCard
                        label="Projected"
                        value={money.format(stats.projected)}
                    />
                    <StatCard
                        label="Paid"
                        value={money.format(stats.paid)}
                        accent="text-emerald-600"
                    />
                    <StatCard
                        label="Outstanding"
                        value={money.format(stats.outstanding)}
                        accent="text-amber-600"
                    />
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Select
                        value={categoryFilter}
                        onValueChange={setCategoryFilter}
                    >
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="All categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            <SelectItem value={NO_CATEGORY}>
                                Uncategorised
                            </SelectItem>
                            {categories.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>
                                    {c.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {filtered.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                                <Wallet className="size-8 opacity-40" />
                                {items.length === 0
                                    ? 'No budget items yet. Add your first cost to get started.'
                                    : 'No items in this category.'}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Item
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Category
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Estimated
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Actual
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
                                        {filtered.map((i) => (
                                            <tr
                                                key={i.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    {i.name}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {i.category_name ?? '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {money.format(i.estimated)}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {i.actual !== null
                                                        ? money.format(i.actual)
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {money.format(i.paid)}
                                                </td>
                                                {writable && (
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    openEdit(i)
                                                                }
                                                                aria-label="Edit item"
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    destroy(i)
                                                                }
                                                                aria-label="Delete item"
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
                            {editingId ? 'Edit item' : 'Add item'}
                        </SheetTitle>
                        <SheetDescription>
                            Enter amounts in dollars. Leave actual blank until
                            you know the real cost.
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={submit}
                        className="flex flex-1 flex-col gap-4 px-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Item</Label>
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

                        <div className="grid gap-2">
                            <Label>Category</Label>
                            <Select
                                value={form.data.category_id}
                                onValueChange={(v) =>
                                    form.setData('category_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_CATEGORY}>
                                        Uncategorised
                                    </SelectItem>
                                    {categories.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={String(c.id)}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.category_id} />
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="estimated_amount">
                                    Estimated
                                </Label>
                                <Input
                                    id="estimated_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.estimated_amount}
                                    onChange={(e) =>
                                        form.setData(
                                            'estimated_amount',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.estimated_amount}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="actual_amount">Actual</Label>
                                <Input
                                    id="actual_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.actual_amount}
                                    onChange={(e) =>
                                        form.setData(
                                            'actual_amount',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.actual_amount}
                                />
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
                            <Label htmlFor="due_date">Due date</Label>
                            <Input
                                id="due_date"
                                type="date"
                                value={form.data.due_date}
                                onChange={(e) =>
                                    form.setData('due_date', e.target.value)
                                }
                            />
                            <InputError message={form.errors.due_date} />
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
                                {editingId ? 'Save changes' : 'Add item'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <ManageCategories
                open={categoriesOpen}
                onOpenChange={setCategoriesOpen}
                categories={categories}
            />
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

function ManageCategories({
    open,
    onOpenChange,
    categories,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    categories: Category[];
}) {
    const form = useForm({ name: '' });

    function add(e: React.FormEvent) {
        e.preventDefault();
        form.post('/budget-categories', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                toast.success('Category added.');
            },
        });
    }

    function remove(category: Category) {
        if (
            !confirm(
                `Delete the "${category.name}" category? Items stay, but become uncategorised.`,
            )
        ) {
            return;
        }

        router.delete(`/budget-categories/${category.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Category deleted.'),
        });
    }

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-y-auto sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Categories</SheetTitle>
                    <SheetDescription>
                        Group budget items for clearer reporting.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={add} className="flex items-end gap-2 px-4">
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="category_name">New category</Label>
                        <Input
                            id="category_name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            placeholder="Catering"
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        <Plus className="size-4" />
                    </Button>
                </form>

                <div className="flex flex-col gap-2 px-4">
                    {categories.length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">
                            No categories yet.
                        </p>
                    ) : (
                        categories.map((c) => (
                            <div
                                key={c.id}
                                className="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                            >
                                <span>{c.name}</span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => remove(c)}
                                    aria-label="Delete category"
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        ))
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}

BudgetIndex.layout = {
    breadcrumbs: [{ title: 'Budget', href: '/budget' }],
};

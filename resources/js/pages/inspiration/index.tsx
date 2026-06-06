import { Head, router, useForm } from '@inertiajs/react';
import {
    ExternalLink,
    ImageOff,
    Pencil,
    Plus,
    Sparkles,
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

type Item = {
    id: number;
    title: string;
    category: string;
    image_url: string | null;
    link_url: string | null;
    notes: string | null;
};

type Stats = { total: number; with_image: number; categories: number };

type PageProps = {
    items: Item[];
    stats: Stats;
    options: { categories: Option[] };
};

type ItemFormData = {
    title: string;
    category: string;
    image_url: string;
    link_url: string;
    notes: string;
};

function emptyForm(options: PageProps['options']): ItemFormData {
    return {
        title: '',
        category: options.categories[0]?.value ?? 'other',
        image_url: '',
        link_url: '',
        notes: '',
    };
}

export default function InspirationIndex({ items, stats, options }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('inspiration');

    const [categoryFilter, setCategoryFilter] = useState('all');
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<ItemFormData>(emptyForm(options));

    const labelFor = (value: string) =>
        options.categories.find((o) => o.value === value)?.label ?? value;

    const filtered = useMemo(
        () =>
            items.filter(
                (i) =>
                    categoryFilter === 'all' || i.category === categoryFilter,
            ),
        [items, categoryFilter],
    );

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(item: Item) {
        form.clearErrors();
        form.setData({
            title: item.title,
            category: item.category,
            image_url: item.image_url ?? '',
            link_url: item.link_url ?? '',
            notes: item.notes ?? '',
        });
        setEditingId(item.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            image_url: data.image_url === '' ? null : data.image_url,
            link_url: data.link_url === '' ? null : data.link_url,
        }));

        const onSuccess = () => {
            toast.success(editingId ? 'Idea updated.' : 'Idea added.');
            setSheetOpen(false);
        };

        if (editingId) {
            form.put(`/inspiration/${editingId}`, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post('/inspiration', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(item: Item) {
        if (!confirm(`Delete “${item.title}”?`)) {
            return;
        }

        router.delete(`/inspiration/${item.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Idea deleted.'),
        });
    }

    return (
        <>
            <Head title="Inspiration" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Inspiration"
                        description="Collect the looks, colours, and ideas that capture your vision."
                    />
                    {writable && (
                        <Button onClick={openCreate} data-test="add-idea">
                            <Plus className="size-4" />
                            Add idea
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Ideas" value={String(stats.total)} />
                    <StatCard
                        label="With image"
                        value={String(stats.with_image)}
                    />
                    <StatCard label="Themes" value={String(stats.categories)} />
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Select
                        value={categoryFilter}
                        onValueChange={setCategoryFilter}
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All themes" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All themes</SelectItem>
                            {options.categories.map((c) => (
                                <SelectItem key={c.value} value={c.value}>
                                    {c.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {filtered.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                            <Sparkles className="size-8 opacity-40" />
                            {items.length === 0
                                ? 'No ideas yet. Pin your first piece of inspiration.'
                                : 'No ideas match this theme.'}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {filtered.map((item) => (
                            <Card
                                key={item.id}
                                className="group overflow-hidden pt-0"
                            >
                                <div className="aspect-square w-full overflow-hidden bg-muted">
                                    {item.image_url ? (
                                        <img
                                            src={item.image_url}
                                            alt={item.title}
                                            className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center text-muted-foreground">
                                            <ImageOff className="size-8 opacity-40" />
                                        </div>
                                    )}
                                </div>
                                <CardContent className="flex flex-col gap-2">
                                    <div className="flex items-start justify-between gap-2">
                                        <span className="leading-tight font-medium">
                                            {item.title}
                                        </span>
                                        <Badge variant="secondary">
                                            {labelFor(item.category)}
                                        </Badge>
                                    </div>
                                    {item.notes && (
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {item.notes}
                                        </p>
                                    )}
                                    <div className="mt-1 flex items-center justify-between">
                                        {item.link_url ? (
                                            <a
                                                href={item.link_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                                            >
                                                <ExternalLink className="size-3.5" />
                                                Source
                                            </a>
                                        ) : (
                                            <span />
                                        )}
                                        {writable && (
                                            <div className="flex gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        openEdit(item)
                                                    }
                                                    aria-label="Edit idea"
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        destroy(item)
                                                    }
                                                    aria-label="Delete idea"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>
                            {editingId ? 'Edit idea' : 'Add idea'}
                        </SheetTitle>
                        <SheetDescription>
                            Paste an image and a source link to build your mood
                            board.
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={submit}
                        className="flex flex-1 flex-col gap-4 px-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="title">Title</Label>
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
                            <Label>Theme</Label>
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
                            <Label htmlFor="image_url">Image URL</Label>
                            <Input
                                id="image_url"
                                value={form.data.image_url}
                                onChange={(e) =>
                                    form.setData('image_url', e.target.value)
                                }
                                placeholder="https://…"
                            />
                            <InputError message={form.errors.image_url} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="link_url">Source link</Label>
                            <Input
                                id="link_url"
                                value={form.data.link_url}
                                onChange={(e) =>
                                    form.setData('link_url', e.target.value)
                                }
                                placeholder="https://…"
                            />
                            <InputError message={form.errors.link_url} />
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
                                {editingId ? 'Save changes' : 'Add idea'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

function StatCard({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className="mt-1 text-2xl font-semibold tabular-nums">
                    {value}
                </div>
            </CardContent>
        </Card>
    );
}

InspirationIndex.layout = {
    breadcrumbs: [{ title: 'Inspiration', href: '/inspiration' }],
};

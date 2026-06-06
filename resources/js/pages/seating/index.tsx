import { Head, router, useForm } from '@inertiajs/react';
import {
    Armchair,
    GripVertical,
    Pencil,
    Plus,
    Trash2,
    UserMinus,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
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

type Table = {
    id: number;
    name: string;
    shape: string;
    capacity: number;
    position_x: number;
    position_y: number;
    notes: string | null;
    seated: number;
};

type SeatGuest = {
    id: number;
    name: string;
    table_id: number | null;
    rsvp_status: string;
};

type Stats = {
    tables: number;
    capacity: number;
    seated: number;
    unseated: number;
};

type PageProps = {
    tables: Table[];
    guests: SeatGuest[];
    stats: Stats;
    options: { shapes: Option[] };
};

const SHAPE_CLASS: Record<string, string> = {
    round: 'rounded-full',
    rectangle: 'rounded-lg',
    square: 'rounded-md',
};

function clamp(value: number, min: number, max: number) {
    return Math.min(max, Math.max(min, value));
}

type TableFormData = {
    name: string;
    shape: string;
    capacity: string;
    notes: string;
};

function emptyForm(options: PageProps['options']): TableFormData {
    return {
        name: '',
        shape: options.shapes[0]?.value ?? 'round',
        capacity: '8',
        notes: '',
    };
}

export default function SeatingIndex({
    tables,
    guests,
    stats,
    options,
}: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('seating');

    const canvasRef = useRef<HTMLDivElement>(null);
    const [positions, setPositions] = useState<
        Record<number, { x: number; y: number }>
    >({});
    const [movingId, setMovingId] = useState<number | null>(null);
    const [dragGuestId, setDragGuestId] = useState<number | null>(null);
    const [dropTarget, setDropTarget] = useState<number | 'unseated' | null>(
        null,
    );

    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<TableFormData>(emptyForm(options));

    const unseated = guests.filter((g) => g.table_id === null);
    const guestsByTable = (tableId: number) =>
        guests.filter((g) => g.table_id === tableId);

    const posFor = (table: Table) =>
        positions[table.id] ?? { x: table.position_x, y: table.position_y };

    const persistMove = useCallback((id: number, x: number, y: number) => {
        router.patch(
            `/seating/${id}/move`,
            { position_x: Math.round(x), position_y: Math.round(y) },
            { preserveScroll: true, preserveState: true },
        );
    }, []);

    useEffect(() => {
        if (movingId === null) {
            return;
        }

        const id = movingId;

        function onMove(e: PointerEvent) {
            const rect = canvasRef.current?.getBoundingClientRect();

            if (!rect) {
                return;
            }

            const x = clamp(
                ((e.clientX - rect.left) / rect.width) * 100,
                3,
                95,
            );
            const y = clamp(
                ((e.clientY - rect.top) / rect.height) * 100,
                3,
                92,
            );
            setPositions((prev) => ({ ...prev, [id]: { x, y } }));
        }

        function onUp() {
            setMovingId((current) => {
                if (current !== null) {
                    const pos = positions[current];

                    if (pos) {
                        persistMove(current, pos.x, pos.y);
                    }
                }

                return null;
            });
        }

        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);

        return () => {
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };
    }, [movingId, positions, persistMove]);

    function assign(guestId: number, tableId: number | null) {
        router.patch(
            '/seating-assign',
            { guest_id: guestId, table_id: tableId },
            {
                preserveScroll: true,
                onError: (errors) =>
                    toast.error(errors.table_id ?? 'Could not seat guest.'),
            },
        );
    }

    function onGuestDrop(tableId: number | null) {
        if (dragGuestId !== null) {
            assign(dragGuestId, tableId);
        }

        setDragGuestId(null);
        setDropTarget(null);
    }

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(table: Table) {
        form.clearErrors();
        form.setData({
            name: table.name,
            shape: table.shape,
            capacity: String(table.capacity),
            notes: table.notes ?? '',
        });
        setEditingId(table.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();

        const onSuccess = () => {
            toast.success(editingId ? 'Table updated.' : 'Table added.');
            setSheetOpen(false);
        };

        if (editingId) {
            form.put(`/seating/${editingId}`, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post('/seating', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(table: Table) {
        if (!confirm(`Delete ${table.name}? Seated guests will be unseated.`)) {
            return;
        }

        router.delete(`/seating/${table.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Table deleted.'),
        });
    }

    return (
        <>
            <Head title="Seating chart" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Seating chart"
                        description="Drag guests onto tables and arrange your floor plan."
                    />
                    {writable && (
                        <Button onClick={openCreate} data-test="add-table">
                            <Plus className="size-4" />
                            Add table
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Tables" value={String(stats.tables)} />
                    <StatCard label="Seats" value={String(stats.capacity)} />
                    <StatCard
                        label="Seated"
                        value={String(stats.seated)}
                        accent="text-emerald-600"
                    />
                    <StatCard
                        label="Unseated"
                        value={String(stats.unseated)}
                        accent={
                            stats.unseated > 0 ? 'text-amber-600' : undefined
                        }
                    />
                </div>

                <div className="flex flex-col gap-4 lg:flex-row">
                    {/* Floor plan */}
                    <div
                        ref={canvasRef}
                        className="relative min-h-[28rem] flex-1 overflow-hidden rounded-xl border bg-muted/30 bg-[radial-gradient(var(--color-border)_1px,transparent_1px)] [background-size:24px_24px]"
                    >
                        {tables.length === 0 && (
                            <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center text-sm text-muted-foreground">
                                <Armchair className="size-8 opacity-40" />
                                Add a table to start building your floor plan.
                            </div>
                        )}

                        {tables.map((table) => {
                            const pos = posFor(table);
                            const seatedGuests = guestsByTable(table.id);
                            const full = seatedGuests.length >= table.capacity;
                            const isTarget = dropTarget === table.id;

                            return (
                                <div
                                    key={table.id}
                                    className="absolute -translate-x-1/2 -translate-y-1/2"
                                    style={{
                                        left: `${pos.x}%`,
                                        top: `${pos.y}%`,
                                    }}
                                    onDragOver={(e) => {
                                        if (dragGuestId !== null) {
                                            e.preventDefault();
                                            setDropTarget(table.id);
                                        }
                                    }}
                                    onDragLeave={() =>
                                        setDropTarget((t) =>
                                            t === table.id ? null : t,
                                        )
                                    }
                                    onDrop={() => onGuestDrop(table.id)}
                                >
                                    <div
                                        className={`flex w-44 flex-col gap-2 border bg-card p-3 shadow-sm transition-colors ${
                                            SHAPE_CLASS[table.shape] ??
                                            'rounded-lg'
                                        } ${isTarget ? 'border-primary ring-2 ring-primary/40' : ''}`}
                                    >
                                        <div className="flex items-center justify-between gap-1">
                                            <div className="flex min-w-0 items-center gap-1">
                                                {writable && (
                                                    <span
                                                        className="cursor-grab touch-none text-muted-foreground hover:text-foreground active:cursor-grabbing"
                                                        onPointerDown={(e) => {
                                                            e.preventDefault();
                                                            setMovingId(
                                                                table.id,
                                                            );
                                                        }}
                                                        aria-label="Move table"
                                                    >
                                                        <GripVertical className="size-4" />
                                                    </span>
                                                )}
                                                <span className="truncate text-sm font-semibold">
                                                    {table.name}
                                                </span>
                                            </div>
                                            <Badge
                                                variant={
                                                    full
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {seatedGuests.length}/
                                                {table.capacity}
                                            </Badge>
                                        </div>

                                        <div className="flex flex-col gap-1">
                                            {seatedGuests.map((g) => (
                                                <div
                                                    key={g.id}
                                                    draggable={writable}
                                                    onDragStart={() =>
                                                        setDragGuestId(g.id)
                                                    }
                                                    onDragEnd={() =>
                                                        setDragGuestId(null)
                                                    }
                                                    className="flex items-center justify-between gap-1 rounded bg-muted px-2 py-1 text-xs"
                                                >
                                                    <span className="truncate">
                                                        {g.name}
                                                    </span>
                                                    {writable && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                assign(
                                                                    g.id,
                                                                    null,
                                                                )
                                                            }
                                                            className="shrink-0 text-muted-foreground hover:text-foreground"
                                                            aria-label={`Unseat ${g.name}`}
                                                        >
                                                            <UserMinus className="size-3" />
                                                        </button>
                                                    )}
                                                </div>
                                            ))}
                                            {seatedGuests.length === 0 && (
                                                <span className="py-1 text-center text-xs text-muted-foreground">
                                                    Drop guests here
                                                </span>
                                            )}
                                        </div>

                                        {writable && (
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-7"
                                                    onClick={() =>
                                                        openEdit(table)
                                                    }
                                                    aria-label="Edit table"
                                                >
                                                    <Pencil className="size-3.5" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-7"
                                                    onClick={() =>
                                                        destroy(table)
                                                    }
                                                    aria-label="Delete table"
                                                >
                                                    <Trash2 className="size-3.5" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Unseated guests */}
                    <Card
                        className={`lg:w-72 ${dropTarget === 'unseated' ? 'border-primary ring-2 ring-primary/40' : ''}`}
                        onDragOver={(e) => {
                            if (dragGuestId !== null) {
                                e.preventDefault();
                                setDropTarget('unseated');
                            }
                        }}
                        onDragLeave={() =>
                            setDropTarget((t) => (t === 'unseated' ? null : t))
                        }
                        onDrop={() => onGuestDrop(null)}
                    >
                        <CardContent className="flex flex-col gap-2 px-4">
                            <div className="text-sm font-semibold">
                                Unseated guests ({unseated.length})
                            </div>
                            {unseated.length === 0 ? (
                                <p className="py-6 text-center text-xs text-muted-foreground">
                                    Everyone has a seat.
                                </p>
                            ) : (
                                unseated.map((g) => (
                                    <div
                                        key={g.id}
                                        draggable={writable}
                                        onDragStart={() => setDragGuestId(g.id)}
                                        onDragEnd={() => setDragGuestId(null)}
                                        className="cursor-grab rounded bg-muted px-3 py-2 text-sm hover:bg-muted/70 active:cursor-grabbing"
                                    >
                                        {g.name}
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>
                            {editingId ? 'Edit table' : 'Add table'}
                        </SheetTitle>
                        <SheetDescription>
                            Name the table, pick a shape, and set its capacity.
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={submit}
                        className="flex flex-1 flex-col gap-4 px-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Table name</Label>
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
                                <Label>Shape</Label>
                                <Select
                                    value={form.data.shape}
                                    onValueChange={(v) =>
                                        form.setData('shape', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.shapes.map((o) => (
                                            <SelectItem
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.shape} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="capacity">Capacity</Label>
                                <Input
                                    id="capacity"
                                    type="number"
                                    min="1"
                                    max="50"
                                    value={form.data.capacity}
                                    onChange={(e) =>
                                        form.setData('capacity', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.capacity} />
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
                                {editingId ? 'Save changes' : 'Add table'}
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

SeatingIndex.layout = {
    breadcrumbs: [{ title: 'Seating chart', href: '/seating' }],
};

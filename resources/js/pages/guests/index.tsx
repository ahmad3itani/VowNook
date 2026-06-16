import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Download, FileText, MailCheck, MoreHorizontal, Pencil, Plus, Search, Trash2, UtensilsCrossed, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { type MealConfig, MealOptionsSheet } from '@/components/meal-options-sheet';
import { PlanUsage } from '@/components/plan-usage';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

type Guest = {
    id: number;
    first_name: string;
    last_name: string | null;
    email: string | null;
    phone: string | null;
    side: string;
    age_group: string;
    is_plus_one: boolean;
    rsvp_status: string;
    meal_choice: string | null;
    appetizer_choice: string | null;
    dessert_choice: string | null;
    dietary_notes: string | null;
    notes: string | null;
    group_id: number | null;
    group_name: string | null;
};

type Group = { id: number; name: string; notes: string | null };

type Stats = {
    total: number;
    attending: number;
    declined: number;
    pending: number;
    maybe: number;
};

type PageProps = {
    guests: Guest[];
    groups: Group[];
    stats: Stats;
    options: {
        sides: Option[];
        ageGroups: Option[];
        statuses: Option[];
    };
    meals: MealConfig;
    plan: { used: number; limit: number | null };
};

const STATUS_VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    attending: 'default',
    pending: 'secondary',
    maybe: 'outline',
    declined: 'destructive',
};

const NO_GROUP = 'none';

type GuestFormData = {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    side: string;
    age_group: string;
    is_plus_one: boolean;
    rsvp_status: string;
    meal_choice: string;
    appetizer_choice: string;
    dessert_choice: string;
    dietary_notes: string;
    notes: string;
    group_id: string;
};

function emptyForm(options: PageProps['options']): GuestFormData {
    return {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        side: options.sides[0]?.value ?? 'both',
        age_group: options.ageGroups[0]?.value ?? 'adult',
        is_plus_one: false,
        rsvp_status: 'pending',
        meal_choice: '',
        appetizer_choice: '',
        dessert_choice: '',
        dietary_notes: '',
        notes: '',
        group_id: NO_GROUP,
    };
}

const NO_CHOICE = '__none';

/** A single course field in the guest form: a dropdown of the couple's options
 *  (or a free-text input for the main course when no options are configured). */
function CourseField({
    label, enabled, options, value, onChange, error, fallbackFreeText = false,
}: {
    label: string;
    enabled: boolean;
    options: string[];
    value: string;
    onChange: (v: string) => void;
    error?: string;
    fallbackFreeText?: boolean;
}) {
    if (!enabled) return null;

    if (options.length === 0) {
        if (!fallbackFreeText) return null;
        return (
            <div className="grid gap-2">
                <Label>{label} choice</Label>
                <Input value={value} onChange={(e) => onChange(e.target.value)} />
                <InputError message={error} />
            </div>
        );
    }

    return (
        <div className="grid gap-2">
            <Label>{label} choice</Label>
            <Select value={value === '' ? NO_CHOICE : value} onValueChange={(v) => onChange(v === NO_CHOICE ? '' : v)}>
                <SelectTrigger><SelectValue placeholder="No choice yet" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value={NO_CHOICE}>No choice yet</SelectItem>
                    {options.map((o) => <SelectItem key={o} value={o}>{o}</SelectItem>)}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}

export default function GuestsIndex({
    guests,
    groups,
    stats,
    options,
    meals,
    plan,
}: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('guests');
    const [mealSheetOpen, setMealSheetOpen] = useState(false);

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [mealFilter, setMealFilter] = useState(false); // show attending with no meal choice
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [groupsOpen, setGroupsOpen] = useState(false);

    const form = useForm<GuestFormData>(emptyForm(options));

    const labelFor = (list: Option[], value: string) =>
        list.find((o) => o.value === value)?.label ?? value;

    const noMealCount = useMemo(
        () => guests.filter((g) => g.rsvp_status === 'attending' && !g.meal_choice).length,
        [guests],
    );

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return guests.filter((g) => {
            const matchesStatus =
                statusFilter === 'all' || g.rsvp_status === statusFilter;
            const name = `${g.first_name} ${g.last_name ?? ''}`.toLowerCase();
            const matchesSearch =
                term === '' ||
                name.includes(term) ||
                (g.email ?? '').toLowerCase().includes(term) ||
                (g.group_name ?? '').toLowerCase().includes(term);
            const matchesMeal =
                !mealFilter || (g.rsvp_status === 'attending' && !g.meal_choice);

            return matchesStatus && matchesSearch && matchesMeal;
        });
    }, [guests, search, statusFilter, mealFilter]);

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(guest: Guest) {
        form.clearErrors();
        form.setData({
            first_name: guest.first_name,
            last_name: guest.last_name ?? '',
            email: guest.email ?? '',
            phone: guest.phone ?? '',
            side: guest.side,
            age_group: guest.age_group,
            is_plus_one: guest.is_plus_one,
            rsvp_status: guest.rsvp_status,
            meal_choice: guest.meal_choice ?? '',
            appetizer_choice: guest.appetizer_choice ?? '',
            dessert_choice: guest.dessert_choice ?? '',
            dietary_notes: guest.dietary_notes ?? '',
            notes: guest.notes ?? '',
            group_id: guest.group_id ? String(guest.group_id) : NO_GROUP,
        });
        setEditingId(guest.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const transform = (data: GuestFormData) => ({
            ...data,
            group_id: data.group_id === NO_GROUP ? null : Number(data.group_id),
        });

        const onSuccess = () => {
            toast.success(editingId ? 'Guest updated.' : 'Guest added.');
            setSheetOpen(false);
        };

        form.transform(transform);

        if (editingId) {
            form.put(`/guests/${editingId}`, {
                preserveScroll: true,
                onSuccess,
            });
        } else {
            form.post('/guests', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(guest: Guest) {
        if (!confirm(`Remove ${guest.first_name} from the guest list?`)) {
            return;
        }

        router.delete(`/guests/${guest.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Guest removed.'),
        });
    }

    function remindRsvps() {
        if (!confirm('Email everyone who hasn’t replied yet a friendly RSVP reminder?')) return;
        router.post('/guests/remind-rsvp', {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('RSVP reminders sent to guests who haven’t replied.'),
        });
    }

    return (
        <>
            <Head title="Guests" />

            {writable && <MealOptionsSheet key={JSON.stringify(meals)} open={mealSheetOpen} onOpenChange={setMealSheetOpen} meals={meals} />}

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Guests"
                        description="Track invitations, RSVPs, and households for your celebration."
                    />
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {/* Secondary actions: inline from sm up, collapsed into a "More" menu on phones */}
                        <div className="hidden flex-wrap items-center gap-2 sm:flex">
                            <Button variant="outline" asChild>
                                <a href="/exports/guests">
                                    <Download className="size-4" />
                                    CSV
                                </a>
                            </Button>
                            <Button variant="outline" asChild>
                                <a href="/exports/guests/pdf">
                                    <FileText className="size-4" />
                                    PDF
                                </a>
                            </Button>
                            {writable && (
                                <>
                                    <Button variant="outline" onClick={() => setMealSheetOpen(true)}>
                                        <UtensilsCrossed className="size-4" />
                                        Meal options
                                    </Button>
                                    <Button variant="outline" onClick={remindRsvps}>
                                        <MailCheck className="size-4" />
                                        Remind RSVPs
                                    </Button>
                                    <Button variant="outline" onClick={() => setGroupsOpen(true)}>
                                        <Users className="size-4" />
                                        Households
                                    </Button>
                                </>
                            )}
                        </div>

                        <div className="sm:hidden">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="icon" aria-label="More guest actions">
                                        <MoreHorizontal className="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem asChild>
                                        <a href="/exports/guests">
                                            <Download className="size-4" />
                                            Export CSV
                                        </a>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <a href="/exports/guests/pdf">
                                            <FileText className="size-4" />
                                            Export PDF
                                        </a>
                                    </DropdownMenuItem>
                                    {writable && (
                                        <>
                                            <DropdownMenuItem onClick={() => setMealSheetOpen(true)}>
                                                <UtensilsCrossed className="size-4" />
                                                Meal options
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={remindRsvps}>
                                                <MailCheck className="size-4" />
                                                Remind RSVPs
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => setGroupsOpen(true)}>
                                                <Users className="size-4" />
                                                Households
                                            </DropdownMenuItem>
                                        </>
                                    )}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        {/* Primary action — always visible, never pushed off-screen */}
                        {writable && (
                            <Button onClick={openCreate} data-test="add-guest">
                                <Plus className="size-4" />
                                Add guest
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Total invited" value={stats.total} />
                    <StatCard
                        label="Attending"
                        value={stats.attending}
                        accent="text-[#775a19]"
                    />
                    <StatCard
                        label="Pending"
                        value={stats.pending}
                        accent="text-[#b08d3e]"
                    />
                    <StatCard
                        label="Declined"
                        value={stats.declined}
                        accent="text-[#775a19]"
                    />
                </div>

                {plan.limit !== null && (
                    <PlanUsage
                        used={plan.used}
                        limit={plan.limit}
                        noun="guests"
                    />
                )}

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative max-w-xs flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search name, email, household…"
                            className="pl-9"
                        />
                    </div>
                    <Select
                        value={statusFilter}
                        onValueChange={setStatusFilter}
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="All RSVPs" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All RSVPs</SelectItem>
                            {options.statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>
                                    {s.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {noMealCount > 0 && (
                        <button
                            type="button"
                            onClick={() => setMealFilter((v) => !v)}
                            className={`flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors ${
                                mealFilter
                                    ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400'
                                    : 'border-border text-muted-foreground hover:bg-muted'
                            }`}
                        >
                            <AlertTriangle className="size-3.5" />
                            No meal choice ({noMealCount})
                        </button>
                    )}
                </div>

                <Card>
                    <CardContent className="p-0">
                        {filtered.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                                <Users className="size-8 opacity-40" />
                                {guests.length === 0
                                    ? 'No guests yet. Add your first guest to get started.'
                                    : 'No guests match your filters.'}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">
                                                Name
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Household
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Side
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                RSVP
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Meal
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Contact
                                            </th>
                                            {writable && (
                                                <th className="px-4 py-3" />
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((g) => (
                                            <tr
                                                key={g.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">
                                                        {g.first_name}{' '}
                                                        {g.last_name}
                                                    </div>
                                                    {g.is_plus_one && (
                                                        <span className="text-xs text-muted-foreground">
                                                            Plus-one
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {g.group_name ?? '—'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {labelFor(
                                                        options.sides,
                                                        g.side,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            STATUS_VARIANT[
                                                                g.rsvp_status
                                                            ] ?? 'secondary'
                                                        }
                                                    >
                                                        {labelFor(
                                                            options.statuses,
                                                            g.rsvp_status,
                                                        )}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {g.meal_choice ? (
                                                        <div>
                                                            <span className="text-sm">{g.meal_choice}</span>
                                                            {g.dietary_notes && (
                                                                <span
                                                                    title={g.dietary_notes}
                                                                    className="ml-1.5 inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/40 dark:text-amber-400"
                                                                >
                                                                    <AlertTriangle className="size-2.5" />
                                                                    Allergy
                                                                </span>
                                                            )}
                                                        </div>
                                                    ) : g.rsvp_status === 'attending' ? (
                                                        <span className="text-xs text-amber-600">Not set</span>
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {g.email ?? g.phone ?? '—'}
                                                </td>
                                                {writable && (
                                                    <td className="px-4 py-3">
                                                        <div className="flex justify-end gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    openEdit(g)
                                                                }
                                                                aria-label="Edit guest"
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    destroy(g)
                                                                }
                                                                aria-label="Remove guest"
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
                            {editingId ? 'Edit guest' : 'Add guest'}
                        </SheetTitle>
                        <SheetDescription>
                            Capture contact details and their RSVP status.
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={submit}
                        className="flex flex-1 flex-col gap-4 px-4"
                    >
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="first_name">First name</Label>
                                <Input
                                    id="first_name"
                                    value={form.data.first_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'first_name',
                                            e.target.value,
                                        )
                                    }
                                    autoFocus
                                />
                                <InputError message={form.errors.first_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="last_name">Last name</Label>
                                <Input
                                    id="last_name"
                                    value={form.data.last_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'last_name',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.last_name} />
                            </div>
                        </div>

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

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Side</Label>
                                <Select
                                    value={form.data.side}
                                    onValueChange={(v) =>
                                        form.setData('side', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.sides.map((o) => (
                                            <SelectItem
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Age group</Label>
                                <Select
                                    value={form.data.age_group}
                                    onValueChange={(v) =>
                                        form.setData('age_group', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.ageGroups.map((o) => (
                                            <SelectItem
                                                key={o.value}
                                                value={o.value}
                                            >
                                                {o.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>RSVP status</Label>
                                <Select
                                    value={form.data.rsvp_status}
                                    onValueChange={(v) =>
                                        form.setData('rsvp_status', v)
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
                            </div>
                            <div className="grid gap-2">
                                <Label>Household</Label>
                                <Select
                                    value={form.data.group_id}
                                    onValueChange={(v) =>
                                        form.setData('group_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NO_GROUP}>
                                            None
                                        </SelectItem>
                                        {groups.map((g) => (
                                            <SelectItem
                                                key={g.id}
                                                value={String(g.id)}
                                            >
                                                {g.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.group_id} />
                            </div>
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                className="size-4 rounded border"
                                checked={form.data.is_plus_one}
                                onChange={(e) =>
                                    form.setData(
                                        'is_plus_one',
                                        e.target.checked,
                                    )
                                }
                            />
                            This guest is a plus-one
                        </label>

                        <CourseField label="Appetizer" enabled={meals.appetizer.enabled} options={meals.appetizer.options} value={form.data.appetizer_choice} onChange={(v) => form.setData('appetizer_choice', v)} error={form.errors.appetizer_choice} />
                        <CourseField label="Main" enabled fallbackFreeText options={meals.main.options} value={form.data.meal_choice} onChange={(v) => form.setData('meal_choice', v)} error={form.errors.meal_choice} />
                        <CourseField label="Dessert" enabled={meals.dessert.enabled} options={meals.dessert.options} value={form.data.dessert_choice} onChange={(v) => form.setData('dessert_choice', v)} error={form.errors.dessert_choice} />

                        <div className="grid gap-2">
                            <Label htmlFor="dietary_notes">Dietary notes</Label>
                            <Textarea
                                id="dietary_notes"
                                value={form.data.dietary_notes}
                                onChange={(e) =>
                                    form.setData(
                                        'dietary_notes',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.dietary_notes} />
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
                                {editingId ? 'Save changes' : 'Add guest'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <ManageGroups
                open={groupsOpen}
                onOpenChange={setGroupsOpen}
                groups={groups}
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
    value: number;
    accent?: string;
}) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className={`mt-1 text-3xl font-semibold ${accent ?? ''}`}>
                    {value}
                </div>
            </CardContent>
        </Card>
    );
}

function ManageGroups({
    open,
    onOpenChange,
    groups,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    groups: Group[];
}) {
    const form = useForm({ name: '', notes: '' });

    function add(e: React.FormEvent) {
        e.preventDefault();
        form.post('/guest-groups', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                toast.success('Household added.');
            },
        });
    }

    function remove(group: Group) {
        if (
            !confirm(
                `Delete the "${group.name}" household? Guests stay, but lose this grouping.`,
            )
        ) {
            return;
        }

        router.delete(`/guest-groups/${group.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Household deleted.'),
        });
    }

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-y-auto sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Households</SheetTitle>
                    <SheetDescription>
                        Group guests who share an invitation and should be
                        seated together.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={add} className="flex items-end gap-2 px-4">
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="group_name">New household</Label>
                        <Input
                            id="group_name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            placeholder="The Cole Family"
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        <Plus className="size-4" />
                    </Button>
                </form>

                <div className="flex flex-col gap-2 px-4">
                    {groups.length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">
                            No households yet.
                        </p>
                    ) : (
                        groups.map((g) => (
                            <div
                                key={g.id}
                                className="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                            >
                                <span>{g.name}</span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => remove(g)}
                                    aria-label="Delete household"
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

GuestsIndex.layout = {
    breadcrumbs: [{ title: 'Guests', href: '/guests' }],
};

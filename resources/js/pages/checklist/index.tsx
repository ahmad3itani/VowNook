import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckSquare, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
type Member = { id: number; name: string };

type Task = {
    id: number;
    title: string;
    category: string;
    priority: string;
    due_date: string | null;
    is_complete: boolean;
    notes: string | null;
    assigned_to: number | null;
    assignee_name: string | null;
};

type Stats = {
    total: number;
    completed: number;
    outstanding: number;
    overdue: number;
};

type PageProps = {
    tasks: Task[];
    stats: Stats;
    options: { categories: Option[]; priorities: Option[] };
    members: Member[];
};

const PRIORITY_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    high: 'destructive',
    medium: 'secondary',
    low: 'outline',
};

const UNASSIGNED = 'unassigned';

const today = new Date().toISOString().slice(0, 10);

function isOverdue(task: Task): boolean {
    return !task.is_complete && !!task.due_date && task.due_date < today;
}

function isDueSoon(task: Task): boolean {
    if (task.is_complete || !task.due_date) return false;
    const diff = (new Date(task.due_date).getTime() - Date.now()) / 86400000;
    return diff >= 0 && diff <= 7;
}

type TaskFormData = {
    title: string;
    category: string;
    priority: string;
    due_date: string;
    assigned_to: string;
    notes: string;
};

function emptyForm(options: PageProps['options']): TaskFormData {
    return {
        title: '',
        category: options.categories[0]?.value ?? 'planning',
        priority: 'medium',
        due_date: '',
        assigned_to: UNASSIGNED,
        notes: '',
    };
}

export default function ChecklistIndex({ tasks, stats, options, members }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('checklist');

    const [search, setSearch] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('all');
    const [priorityFilter, setPriorityFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all'); // all | overdue | outstanding | complete
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    // Inline quick-add
    const [quickTitle, setQuickTitle] = useState('');
    const [quickAdding, setQuickAdding] = useState(false);
    const quickRef = useRef<HTMLInputElement>(null);

    const form = useForm<TaskFormData>(emptyForm(options));

    const labelFor = (list: Option[], value: string) =>
        list.find((o) => o.value === value)?.label ?? value;

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();
        return tasks
            .filter((t) => {
                if (categoryFilter !== 'all' && t.category !== categoryFilter) return false;
                if (priorityFilter !== 'all' && t.priority !== priorityFilter) return false;
                if (statusFilter === 'overdue' && !isOverdue(t)) return false;
                if (statusFilter === 'outstanding' && (t.is_complete || isOverdue(t))) return false;
                if (statusFilter === 'complete' && !t.is_complete) return false;
                if (term && !t.title.toLowerCase().includes(term)) return false;
                return true;
            })
            .sort((a, b) => {
                // Overdue first, then by priority, then incomplete before complete
                if (isOverdue(a) !== isOverdue(b)) return isOverdue(a) ? -1 : 1;
                if (a.is_complete !== b.is_complete) return a.is_complete ? 1 : -1;
                const pOrder = { high: 0, medium: 1, low: 2 };
                return (pOrder[a.priority as keyof typeof pOrder] ?? 1)
                    - (pOrder[b.priority as keyof typeof pOrder] ?? 1);
            });
    }, [tasks, search, categoryFilter, priorityFilter, statusFilter]);

    function quickAdd(e: React.KeyboardEvent<HTMLInputElement>) {
        if (e.key !== 'Enter' || !quickTitle.trim() || quickAdding) return;
        setQuickAdding(true);
        router.post(
            '/checklist',
            { title: quickTitle.trim(), category: 'planning', priority: 'medium' },
            {
                preserveScroll: true,
                onSuccess: () => { setQuickTitle(''); setQuickAdding(false); },
                onError: () => setQuickAdding(false),
            },
        );
    }

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(task: Task) {
        form.clearErrors();
        form.setData({
            title: task.title,
            category: task.category,
            priority: task.priority,
            due_date: task.due_date ?? '',
            assigned_to: task.assigned_to !== null ? String(task.assigned_to) : UNASSIGNED,
            notes: task.notes ?? '',
        });
        setEditingId(task.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            due_date: data.due_date === '' ? null : data.due_date,
            assigned_to: data.assigned_to === UNASSIGNED ? null : data.assigned_to,
        }));
        const onSuccess = () => {
            toast.success(editingId ? 'Task updated.' : 'Task added.');
            setSheetOpen(false);
        };
        if (editingId) {
            form.put(`/checklist/${editingId}`, { preserveScroll: true, onSuccess });
        } else {
            form.post('/checklist', { preserveScroll: true, onSuccess });
        }
    }

    function toggle(task: Task) {
        router.patch(`/checklist/${task.id}/toggle`, {}, {
            preserveScroll: true,
            onError: () => toast.error('Something went wrong. Please try again.'),
        });
    }

    function destroy(task: Task) {
        if (!confirm(`Delete "${task.title}"?`)) return;
        router.delete(`/checklist/${task.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Task deleted.'),
            onError: () => toast.error('Something went wrong. Please try again.'),
        });
    }

    const filterChips = [
        { value: 'all', label: 'All' },
        { value: 'overdue', label: `Overdue${stats.overdue > 0 ? ` (${stats.overdue})` : ''}`, danger: stats.overdue > 0 },
        { value: 'outstanding', label: 'Outstanding' },
        { value: 'complete', label: 'Completed' },
    ];

    return (
        <>
            <Head title="Checklist" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Checklist"
                        description="Stay on top of every to-do on the road to your wedding day."
                    />
                    {writable && (
                        <Button onClick={openCreate} data-test="add-task">
                            <Plus className="size-4" />
                            Add task
                        </Button>
                    )}
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Tasks" value={String(stats.total)} />
                    <StatCard label="Completed" value={String(stats.completed)} accent="text-[#1b4638]" />
                    <StatCard label="Outstanding" value={String(stats.outstanding)} />
                    <StatCard
                        label="Overdue"
                        value={String(stats.overdue)}
                        accent={stats.overdue > 0 ? 'text-destructive' : undefined}
                    />
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-center gap-3">
                    {/* Status chips */}
                    <div className="flex items-center gap-1.5 rounded-lg border border-border bg-card p-1">
                        {filterChips.map((chip) => (
                            <button
                                key={chip.value}
                                type="button"
                                onClick={() => setStatusFilter(chip.value)}
                                className={`rounded px-3 py-1 text-xs font-medium transition-colors ${
                                    statusFilter === chip.value
                                        ? chip.danger
                                            ? 'bg-destructive text-destructive-foreground'
                                            : 'bg-[#1b4638] text-white'
                                        : chip.danger
                                          ? 'text-destructive hover:bg-destructive/10'
                                          : 'text-muted-foreground hover:bg-muted'
                                }`}
                            >
                                {chip.danger && statusFilter !== chip.value && (
                                    <AlertTriangle className="mr-1 inline size-3" />
                                )}
                                {chip.label}
                            </button>
                        ))}
                    </div>

                    <div className="relative max-w-xs flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search tasks…"
                            className="pl-9"
                        />
                    </div>

                    <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            {options.categories.map((c) => (
                                <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select value={priorityFilter} onValueChange={setPriorityFilter}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="All priorities" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All priorities</SelectItem>
                            {options.priorities.map((p) => (
                                <SelectItem key={p.value} value={p.value}>{p.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {/* Inline quick-add */}
                        {writable && (
                            <div className="flex items-center gap-3 border-b border-border px-4 py-3">
                                <Plus className="size-4 shrink-0 text-muted-foreground" />
                                <input
                                    ref={quickRef}
                                    value={quickTitle}
                                    onChange={(e) => setQuickTitle(e.target.value)}
                                    onKeyDown={quickAdd}
                                    placeholder="Quick-add a task… press ↵ to save"
                                    disabled={quickAdding}
                                    className="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:opacity-50"
                                />
                                {quickAdding && <Spinner className="size-4" />}
                            </div>
                        )}

                        {filtered.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                                <CheckSquare className="size-8 opacity-40" />
                                {tasks.length === 0
                                    ? 'No tasks yet. Type above to quick-add, or use Add task for full options.'
                                    : 'No tasks match your filters.'}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th className="w-10 px-4 py-3" />
                                            <th className="px-4 py-3 font-medium">Task</th>
                                            <th className="px-4 py-3 font-medium">Category</th>
                                            <th className="px-4 py-3 font-medium">Priority</th>
                                            <th className="px-4 py-3 font-medium">Due</th>
                                            <th className="px-4 py-3 font-medium">Assignee</th>
                                            {writable && <th className="px-4 py-3" />}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((t) => {
                                            const overdue = isOverdue(t);
                                            const soon = isDueSoon(t);
                                            return (
                                                <tr
                                                    key={t.id}
                                                    className={`border-b last:border-0 ${
                                                        overdue
                                                            ? 'bg-destructive/[0.04] dark:bg-destructive/10'
                                                            : ''
                                                    }`}
                                                >
                                                    <td className={`px-4 py-3 ${overdue ? 'border-l-2 border-l-destructive' : ''}`}>
                                                        <Checkbox
                                                            checked={t.is_complete}
                                                            onCheckedChange={() => toggle(t)}
                                                            disabled={!writable}
                                                            aria-label="Toggle complete"
                                                        />
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex items-center gap-2">
                                                            <span className={t.is_complete ? 'text-muted-foreground line-through' : 'font-medium'}>
                                                                {t.title}
                                                            </span>
                                                            {overdue && (
                                                                <AlertTriangle className="size-3.5 shrink-0 text-destructive" />
                                                            )}
                                                        </div>
                                                        {t.notes && (
                                                            <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                                {t.notes}
                                                            </p>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">
                                                        {labelFor(options.categories, t.category)}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge variant={PRIORITY_VARIANT[t.priority] ?? 'secondary'}>
                                                            {labelFor(options.priorities, t.priority)}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {t.due_date ? (
                                                            <span className={
                                                                overdue
                                                                    ? 'font-semibold text-destructive'
                                                                    : soon
                                                                      ? 'font-medium text-amber-600'
                                                                      : 'text-muted-foreground'
                                                            }>
                                                                {t.due_date}
                                                            </span>
                                                        ) : (
                                                            <span className="text-muted-foreground">—</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-muted-foreground">
                                                        {t.assignee_name ?? '—'}
                                                    </td>
                                                    {writable && (
                                                        <td className="px-4 py-3">
                                                            <div className="flex justify-end gap-1">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => openEdit(t)}
                                                                    aria-label="Edit task"
                                                                >
                                                                    <Pencil className="size-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => destroy(t)}
                                                                    aria-label="Delete task"
                                                                >
                                                                    <Trash2 className="size-4" />
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    )}
                                                </tr>
                                            );
                                        })}
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
                        <SheetTitle>{editingId ? 'Edit task' : 'Add task'}</SheetTitle>
                        <SheetDescription>Set a due date, priority, and who is responsible.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={submit} className="flex flex-1 flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label htmlFor="title">Task</Label>
                            <Input
                                id="title"
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                autoFocus
                            />
                            <InputError message={form.errors.title} />
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
                                <Label>Priority</Label>
                                <Select value={form.data.priority} onValueChange={(v) => form.setData('priority', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {options.priorities.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.priority} />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="due_date">Due date</Label>
                                <Input
                                    id="due_date"
                                    type="date"
                                    value={form.data.due_date}
                                    onChange={(e) => form.setData('due_date', e.target.value)}
                                />
                                <InputError message={form.errors.due_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Assignee</Label>
                                <Select value={form.data.assigned_to} onValueChange={(v) => form.setData('assigned_to', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={UNASSIGNED}>Unassigned</SelectItem>
                                        {members.map((m) => (
                                            <SelectItem key={m.id} value={String(m.id)}>{m.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.assigned_to} />
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
                                {editingId ? 'Save changes' : 'Add task'}
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

ChecklistIndex.layout = {
    breadcrumbs: [{ title: 'Checklist', href: '/checklist' }],
};

import { Head, router, useForm } from '@inertiajs/react';
import {
    HeartHandshake,
    Mail,
    Pencil,
    Phone,
    Plus,
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

type Member = {
    id: number;
    name: string;
    role: string;
    email: string | null;
    phone: string | null;
    notes: string | null;
};

type Stats = { total: number; roles: number; with_contact: number };

type PageProps = {
    members: Member[];
    stats: Stats;
    options: { roles: Option[] };
};

type MemberFormData = {
    name: string;
    role: string;
    email: string;
    phone: string;
    notes: string;
};

function emptyForm(options: PageProps['options']): MemberFormData {
    return {
        name: '',
        role: options.roles[0]?.value ?? 'other',
        email: '',
        phone: '',
        notes: '',
    };
}

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function CrewIndex({ members, stats, options }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('crew');

    const [roleFilter, setRoleFilter] = useState('all');
    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const form = useForm<MemberFormData>(emptyForm(options));

    const labelFor = (value: string) =>
        options.roles.find((o) => o.value === value)?.label ?? value;

    const filtered = useMemo(
        () =>
            members.filter(
                (m) => roleFilter === 'all' || m.role === roleFilter,
            ),
        [members, roleFilter],
    );

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(member: Member) {
        form.clearErrors();
        form.setData({
            name: member.name,
            role: member.role,
            email: member.email ?? '',
            phone: member.phone ?? '',
            notes: member.notes ?? '',
        });
        setEditingId(member.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            email: data.email === '' ? null : data.email,
            phone: data.phone === '' ? null : data.phone,
        }));

        const onSuccess = () => {
            toast.success(
                editingId ? 'Crew member updated.' : 'Crew member added.',
            );
            setSheetOpen(false);
        };

        if (editingId) {
            form.put(`/crew/${editingId}`, { preserveScroll: true, onSuccess });
        } else {
            form.post('/crew', { preserveScroll: true, onSuccess });
        }
    }

    function destroy(member: Member) {
        if (!confirm(`Remove ${member.name} from the crew?`)) {
            return;
        }

        router.delete(`/crew/${member.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Crew member removed.'),
        });
    }

    return (
        <>
            <Head title="Crew" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Crew"
                        description="Your wedding party and the people with a role on the day."
                    />
                    {writable && (
                        <Button onClick={openCreate} data-test="add-crew">
                            <Plus className="size-4" />
                            Add crew
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Crew" value={String(stats.total)} />
                    <StatCard label="Roles" value={String(stats.roles)} />
                    <StatCard
                        label="With contact"
                        value={String(stats.with_contact)}
                    />
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Select value={roleFilter} onValueChange={setRoleFilter}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="All roles" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All roles</SelectItem>
                            {options.roles.map((r) => (
                                <SelectItem key={r.value} value={r.value}>
                                    {r.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {filtered.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                            <HeartHandshake className="size-8 opacity-40" />
                            {members.length === 0
                                ? 'No crew yet. Add your wedding party and helpers.'
                                : 'No crew match this role.'}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {filtered.map((member) => (
                            <Card key={member.id} className="group">
                                <CardContent className="flex flex-col gap-3">
                                    <div className="flex items-start gap-3">
                                        <div className="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#a8d5c2]/40 font-medium text-[#1b4638] dark:bg-[#a8d5c2]/15 dark:text-[#6e9e8a]">
                                            {initials(member.name)}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate font-medium">
                                                {member.name}
                                            </div>
                                            <Badge
                                                variant="secondary"
                                                className="mt-1"
                                            >
                                                {labelFor(member.role)}
                                            </Badge>
                                        </div>
                                        {writable && (
                                            <div className="flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        openEdit(member)
                                                    }
                                                    aria-label="Edit crew member"
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        destroy(member)
                                                    }
                                                    aria-label="Remove crew member"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>

                                    {(member.email || member.phone) && (
                                        <div className="flex flex-col gap-1 text-sm">
                                            {member.email && (
                                                <a
                                                    href={`mailto:${member.email}`}
                                                    className="inline-flex items-center gap-2 text-muted-foreground hover:text-primary"
                                                >
                                                    <Mail className="size-3.5" />
                                                    <span className="truncate">
                                                        {member.email}
                                                    </span>
                                                </a>
                                            )}
                                            {member.phone && (
                                                <a
                                                    href={`tel:${member.phone}`}
                                                    className="inline-flex items-center gap-2 text-muted-foreground hover:text-primary"
                                                >
                                                    <Phone className="size-3.5" />
                                                    {member.phone}
                                                </a>
                                            )}
                                        </div>
                                    )}

                                    {member.notes && (
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {member.notes}
                                        </p>
                                    )}
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
                            {editingId ? 'Edit crew member' : 'Add crew member'}
                        </SheetTitle>
                        <SheetDescription>
                            Track who is in your wedding party and how to reach
                            them.
                        </SheetDescription>
                    </SheetHeader>

                    <form
                        onSubmit={submit}
                        className="flex flex-1 flex-col gap-4 px-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
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
                            <Label>Role</Label>
                            <Select
                                value={form.data.role}
                                onValueChange={(v) => form.setData('role', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.roles.map((o) => (
                                        <SelectItem
                                            key={o.value}
                                            value={o.value}
                                        >
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.role} />
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
                                {editingId ? 'Save changes' : 'Add crew'}
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

CrewIndex.layout = {
    breadcrumbs: [{ title: 'Crew', href: '/crew' }],
};

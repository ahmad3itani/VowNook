import { Head, router, useForm } from '@inertiajs/react';
import { Crown, Trash2, UserPlus } from 'lucide-react';
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
import { usePermissions } from '@/hooks/use-permissions';

type RoleOption = { value: string; label: string; description: string };

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_owner: boolean;
    is_self: boolean;
};

type PageProps = {
    members: Member[];
    options: { roles: RoleOption[] };
};

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function CollaboratorsIndex({ members, options }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('collaborators');

    const invite = useForm<{ email: string; role: string }>({
        email: '',
        role: options.roles[0]?.value ?? 'collaborator',
    });

    const labelFor = (value: string) =>
        value === 'owner' ? 'Owner' : (options.roles.find((o) => o.value === value)?.label ?? value);

    function submitInvite(e: React.FormEvent) {
        e.preventDefault();
        invite.post('/collaborators', {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Collaborator added.');
                invite.reset('email');
            },
        });
    }

    function changeRole(member: Member, role: string) {
        router.put(
            `/collaborators/${member.id}`,
            { role },
            {
                preserveScroll: true,
                onSuccess: () => toast.success(`${member.name} is now a ${labelFor(role)}.`),
            },
        );
    }

    function remove(member: Member) {
        if (!confirm(`Remove ${member.name}'s access to this wedding?`)) {
            return;
        }

        router.delete(`/collaborators/${member.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Access removed.'),
        });
    }

    return (
        <>
            <Head title="Collaborators" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Collaborators"
                    description="Decide who can see and help plan this wedding, and what they can do."
                />

                {writable && (
                    <Card>
                        <CardContent>
                            <form
                                onSubmit={submitInvite}
                                className="flex flex-col gap-3 sm:flex-row sm:items-end"
                            >
                                <div className="grid flex-1 gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={invite.data.email}
                                        onChange={(e) => invite.setData('email', e.target.value)}
                                        placeholder="name@example.com"
                                    />
                                    <InputError message={invite.errors.email} />
                                </div>
                                <div className="grid gap-2 sm:w-48">
                                    <Label>Role</Label>
                                    <Select
                                        value={invite.data.role}
                                        onValueChange={(v) => invite.setData('role', v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.roles.map((o) => (
                                                <SelectItem key={o.value} value={o.value}>
                                                    {o.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button type="submit" disabled={invite.processing}>
                                    <UserPlus className="size-4" />
                                    Add
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent className="flex flex-col divide-y p-0">
                        {members.map((member) => (
                            <div
                                key={member.id}
                                className="flex flex-wrap items-center gap-4 p-4"
                            >
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-sm font-medium text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                                    {initials(member.name)}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="truncate font-medium">{member.name}</span>
                                        {member.is_self && (
                                            <span className="text-muted-foreground text-xs">(you)</span>
                                        )}
                                    </div>
                                    <div className="text-muted-foreground truncate text-sm">
                                        {member.email}
                                    </div>
                                </div>

                                {member.is_owner ? (
                                    <Badge className="gap-1 bg-amber-100 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300">
                                        <Crown className="size-3.5" />
                                        Owner
                                    </Badge>
                                ) : writable ? (
                                    <div className="flex items-center gap-2">
                                        <Select
                                            value={member.role}
                                            onValueChange={(v) => changeRole(member, v)}
                                        >
                                            <SelectTrigger className="w-40">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.roles.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => remove(member)}
                                            aria-label="Remove access"
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ) : (
                                    <Badge variant="secondary">{labelFor(member.role)}</Badge>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {writable && (
                    <div className="text-muted-foreground grid gap-2 text-sm">
                        {options.roles.map((o) => (
                            <div key={o.value}>
                                <span className="text-foreground font-medium">{o.label}</span> —{' '}
                                {o.description}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

CollaboratorsIndex.layout = {
    breadcrumbs: [{ title: 'Collaborators', href: '/collaborators' }],
};

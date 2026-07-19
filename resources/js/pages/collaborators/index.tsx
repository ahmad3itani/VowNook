import { Head, router, useForm } from '@inertiajs/react';
import { ChevronDown, Clock, Crown, Mail, RotateCw, Trash2, UserPlus, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { type AccessMap, PermissionMatrix, accessSummary, type Section } from '@/components/permission-matrix';
import { PlanUsage } from '@/components/plan-usage';
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
import { usePermissions } from '@/hooks/use-permissions';

type RoleOption = { value: string; label: string; description: string };
type Member = { id: number; name: string; email: string; role: string; is_owner: boolean; is_self: boolean; access: AccessMap };
type Invitation = { id: number; email: string; role: string; access: AccessMap; invited_at: string | null; expired: boolean };
type Options = {
    roles: RoleOption[];
    sections: Section[];
    levels: string[];
    role_defaults: Record<string, AccessMap>;
};
type PageProps = {
    members: Member[];
    invitations: Invitation[];
    options: Options;
    plan: { used: number; limit: number | null };
};

function initials(name: string): string {
    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('');
}

export default function CollaboratorsIndex({ members, invitations, options, plan }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('collaborators');
    const labelFor = (value: string) =>
        value === 'owner' ? 'Owner' : (options.roles.find((o) => o.value === value)?.label ?? value);

    // Invite form
    const firstRole = options.roles[0]?.value ?? 'collaborator';
    const invite = useForm<{ email: string; role: string; permissions: AccessMap }>({
        email: '',
        role: firstRole,
        permissions: { ...options.role_defaults[firstRole] },
    });
    const [customising, setCustomising] = useState(false);

    function setInviteRole(role: string) {
        invite.setData((d) => ({ ...d, role, permissions: { ...options.role_defaults[role] } }));
    }

    function submitInvite(e: React.FormEvent) {
        e.preventDefault();
        invite.post('/collaborators', {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Invitation sent.');
                invite.reset('email');
                setCustomising(false);
            },
        });
    }

    // Per-member access editor (Sheet)
    const [editing, setEditing] = useState<Member | null>(null);
    const [draftRole, setDraftRole] = useState('');
    const [draftAccess, setDraftAccess] = useState<AccessMap>({});
    const [savingMember, setSavingMember] = useState(false);

    function openEditor(member: Member) {
        setEditing(member);
        setDraftRole(member.role);
        setDraftAccess({ ...member.access });
    }

    function editorRole(role: string) {
        setDraftRole(role);
        setDraftAccess({ ...options.role_defaults[role] });
    }

    function saveMember() {
        if (!editing) return;
        setSavingMember(true);
        router.put(
            `/collaborators/${editing.id}`,
            { role: draftRole, permissions: draftAccess as unknown as Record<string, string> },
            {
                preserveScroll: true,
                onSuccess: () => { toast.success('Access updated.'); setEditing(null); },
                onError: () => toast.error('Something went wrong. Please try again.'),
                onFinish: () => setSavingMember(false),
            },
        );
    }

    function removeMember(member: Member) {
        if (!confirm(`Remove ${member.name}'s access to this wedding?`)) return;
        router.delete(`/collaborators/${member.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Access removed.'),
            onError: () => toast.error('Something went wrong. Please try again.'),
        });
    }

    function resend(inv: Invitation) {
        router.post(`/collaborators/invitations/${inv.id}/resend`, {}, {
            preserveScroll: true,
            onSuccess: () => toast.success(`Invitation re-sent to ${inv.email}.`),
            onError: () => toast.error('Could not resend.'),
        });
    }

    function revoke(inv: Invitation) {
        if (!confirm(`Cancel the invitation to ${inv.email}?`)) return;
        router.delete(`/collaborators/invitations/${inv.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Invitation cancelled.'),
            onError: () => toast.error('Could not cancel.'),
        });
    }

    return (
        <>
            <Head title="Collaborators" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Your team"
                    description="Invite people to help plan this wedding, and choose exactly what each person can access."
                />

                {plan.limit !== null && <PlanUsage used={plan.used} limit={plan.limit} noun="collaborators" />}

                {/* Invite */}
                {writable && (
                    <Card>
                        <CardContent className="flex flex-col gap-4">
                            <form onSubmit={submitInvite} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div className="grid flex-1 gap-2">
                                    <Label htmlFor="email">Invite by email</Label>
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
                                    <Select value={invite.data.role} onValueChange={setInviteRole}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {options.roles.map((o) => (
                                                <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button type="submit" disabled={invite.processing}>
                                    {invite.processing ? <Spinner /> : <UserPlus className="size-4" />}
                                    Send invite
                                </Button>
                            </form>

                            <div>
                                <button
                                    type="button"
                                    onClick={() => setCustomising((v) => !v)}
                                    className="flex items-center gap-1 text-xs font-medium text-[#1b4638] hover:underline"
                                >
                                    <ChevronDown className={`size-3.5 transition-transform ${customising ? 'rotate-180' : ''}`} />
                                    Customize access
                                    <span className="text-muted-foreground">· {accessSummary(invite.data.permissions)}</span>
                                </button>
                                {customising && (
                                    <div className="mt-3">
                                        <PermissionMatrix
                                            sections={options.sections}
                                            value={invite.data.permissions}
                                            onChange={(next) => invite.setData('permissions', next)}
                                        />
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            Starting from the <strong>{labelFor(invite.data.role)}</strong> role — adjust any section above.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Members */}
                <Card>
                    <CardContent className="flex flex-col divide-y p-0">
                        {members.map((member) => (
                            <div key={member.id} className="flex flex-wrap items-center gap-4 p-4">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#a8d5c2]/40 text-sm font-medium text-[#1b4638] dark:bg-[#a8d5c2]/15 dark:text-[#6e9e8a]">
                                    {initials(member.name)}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="truncate font-medium">{member.name}</span>
                                        {member.is_self && <span className="text-xs text-muted-foreground">(you)</span>}
                                    </div>
                                    <div className="truncate text-sm text-muted-foreground">{member.email}</div>
                                </div>

                                {member.is_owner ? (
                                    <Badge className="gap-1 bg-[#a8d5c2]/40 text-[#1b4638] hover:bg-[#a8d5c2]/40 dark:bg-[#a8d5c2]/15 dark:text-[#6e9e8a]">
                                        <Crown className="size-3.5" /> Owner
                                    </Badge>
                                ) : (
                                    <div className="flex items-center gap-2">
                                        <span className="hidden text-xs text-muted-foreground sm:inline">{accessSummary(member.access)}</span>
                                        <Badge variant="secondary">{labelFor(member.role)}</Badge>
                                        {writable && (
                                            <>
                                                <Button variant="outline" size="sm" onClick={() => openEditor(member)}>
                                                    Manage
                                                </Button>
                                                <Button variant="ghost" size="icon" onClick={() => removeMember(member)} aria-label="Remove access">
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {/* Pending invitations */}
                {invitations.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <h2 className="text-sm font-medium text-muted-foreground">Pending invitations</h2>
                        <Card>
                            <CardContent className="flex flex-col divide-y p-0">
                                {invitations.map((inv) => (
                                    <div key={inv.id} className="flex flex-wrap items-center gap-4 p-4">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                            <Mail className="size-4" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate font-medium">{inv.email}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {accessSummary(inv.access)} · invited {inv.invited_at}
                                            </div>
                                        </div>
                                        <Badge variant={inv.expired ? 'destructive' : 'outline'} className="gap-1">
                                            <Clock className="size-3" /> {inv.expired ? 'Expired' : 'Pending'} · {labelFor(inv.role)}
                                        </Badge>
                                        {writable && (
                                            <div className="flex items-center gap-1">
                                                <Button variant="ghost" size="sm" onClick={() => resend(inv)}>
                                                    <RotateCw className="size-3.5" /> Resend
                                                </Button>
                                                <Button variant="ghost" size="icon" onClick={() => revoke(inv)} aria-label="Cancel invitation">
                                                    <X className="size-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Role legend */}
                {writable && (
                    <div className="grid gap-2 text-sm text-muted-foreground">
                        {options.roles.map((o) => (
                            <div key={o.value}>
                                <span className="font-medium text-foreground">{o.label}</span> — {o.description}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Per-member access editor */}
            <Sheet open={editing !== null} onOpenChange={(o) => !o && setEditing(null)}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>{editing?.name}</SheetTitle>
                        <SheetDescription>{editing?.email}</SheetDescription>
                    </SheetHeader>

                    <div className="flex flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label>Role</Label>
                            <Select value={draftRole} onValueChange={editorRole}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {options.roles.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label>Access by section</Label>
                            <PermissionMatrix
                                sections={options.sections}
                                value={draftAccess}
                                onChange={setDraftAccess}
                            />
                            <button
                                type="button"
                                onClick={() => setDraftAccess({ ...options.role_defaults[draftRole] })}
                                className="self-start text-xs font-medium text-[#1b4638] hover:underline"
                            >
                                Reset to {labelFor(draftRole)} defaults
                            </button>
                        </div>
                    </div>

                    <SheetFooter className="px-4">
                        <Button onClick={saveMember} disabled={savingMember}>
                            {savingMember && <Spinner />}
                            Save changes
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        </>
    );
}

CollaboratorsIndex.layout = {
    breadcrumbs: [{ title: 'Your team', href: '/collaborators' }],
};

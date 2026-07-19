import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, UserCheck } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Reply = { id: number; body: string; is_staff: boolean; author: string; created_at: string | null };
type Ticket = {
    id: number;
    subject: string;
    name: string;
    email: string;
    category: string;
    status: string;
    status_label: string;
    source: string;
    message: string;
    assignee: string | null;
    user: { id: number; name: string; account_type: string } | null;
    created_at: string | null;
    replies: Reply[];
};
type Option = { value: string; label: string };

type PageProps = { ticket: Ticket; statuses: Option[] };

function fmt(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function AdminSupportShow({ ticket, statuses }: PageProps) {
    const [body, setBody] = useState('');
    const [busy, setBusy] = useState(false);

    function sendReply() {
        if (!body.trim()) return;
        router.post(`/admin/support/${ticket.id}/reply`, { body }, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onSuccess: () => { setBody(''); toast.success('Reply sent to the requester.'); },
            onError: () => toast.error('Could not send reply.'),
        });
    }

    function setStatus(status: string) {
        router.put(`/admin/support/${ticket.id}/status`, { status }, {
            preserveScroll: true,
            onSuccess: () => toast.success('Status updated.'),
        });
    }

    function assignToMe() {
        router.post(`/admin/support/${ticket.id}/assign`, {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Assigned to you.'),
        });
    }

    return (
        <>
            <Head title={ticket.subject} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Link href="/admin/support" className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="size-4" /> Back to inbox
                </Link>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title={ticket.subject} description={`${ticket.name} <${ticket.email}>`} />
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className="capitalize">{ticket.category}</Badge>
                        {ticket.user && (
                            <Link href={`/admin/users/${ticket.user.id}`} className="text-xs font-medium text-[#1b4638] hover:underline">
                                View account
                            </Link>
                        )}
                        <Select value={ticket.status} onValueChange={setStatus}>
                            <SelectTrigger className="h-8 w-36"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {statuses.map((s) => <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <Button size="sm" variant="outline" onClick={assignToMe}>
                            <UserCheck className="size-4" /> {ticket.assignee ? ticket.assignee : 'Assign me'}
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        {/* Original message */}
                        <Bubble author={ticket.name} when={ticket.created_at} body={ticket.message} staff={false} />
                        {ticket.replies.map((r) => (
                            <Bubble key={r.id} author={r.author} when={r.created_at} body={r.body} staff={r.is_staff} />
                        ))}

                        <Card>
                            <CardHeader><CardTitle className="text-base">Reply</CardTitle></CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                <Textarea
                                    rows={5}
                                    value={body}
                                    onChange={(e) => setBody(e.target.value)}
                                    placeholder="Write a reply — this emails the requester."
                                />
                                <div className="flex justify-end">
                                    <Button disabled={busy || !body.trim()} onClick={sendReply}>Send reply</Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card className="h-fit">
                        <CardHeader><CardTitle className="text-base">Details</CardTitle></CardHeader>
                        <CardContent className="grid gap-3 text-sm">
                            <Detail label="Status" value={ticket.status_label} />
                            <Detail label="Source" value={ticket.source === 'in_app' ? 'In-app form' : 'Contact form'} />
                            <Detail label="Account" value={ticket.user ? `${ticket.user.name} (${ticket.user.account_type})` : 'Guest (no account)'} />
                            <Detail label="Assigned to" value={ticket.assignee ?? 'Unassigned'} />
                            <Detail label="Opened" value={fmt(ticket.created_at)} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function Bubble({ author, when, body, staff }: { author: string; when: string | null; body: string; staff: boolean }) {
    return (
        <div className={`rounded-lg border p-4 ${staff ? 'border-[#1b4638]/30 bg-[#1b4638]/5' : 'bg-card'}`}>
            <div className="mb-1 flex items-center justify-between text-xs">
                <span className="font-medium">{author}{staff && ' · Support'}</span>
                <span className="text-muted-foreground">{fmt(when)}</span>
            </div>
            <p className="whitespace-pre-wrap text-sm">{body}</p>
        </div>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-3">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

AdminSupportShow.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Support', href: '/admin/support' },
    ],
};

import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { FormEvent } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';

type Reply = { id: number; body: string; is_staff: boolean; author: string; created_at: string | null };
type Ticket = {
    id: number;
    subject: string;
    category: string;
    status: string;
    status_label: string;
    message: string;
    created_at: string | null;
    replies: Reply[];
};

type PageProps = { ticket: Ticket };

function fmt(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function SupportShow({ ticket }: PageProps) {
    const form = useForm({ body: '' });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!form.data.body.trim()) return;
        form.post(`/support/${ticket.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <>
            <Head title={ticket.subject} />

            <div className="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <Link href="/support" className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft className="size-4" /> Back to support
                </Link>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading title={ticket.subject} description={`Opened ${fmt(ticket.created_at)}`} />
                    <Badge variant={ticket.status === 'closed' ? 'outline' : 'default'}>{ticket.status_label}</Badge>
                </div>

                <div className="flex flex-col gap-4">
                    <Bubble author="You" when={ticket.created_at} body={ticket.message} staff={false} />
                    {ticket.replies.map((r) => (
                        <Bubble key={r.id} author={r.author} when={r.created_at} body={r.body} staff={r.is_staff} />
                    ))}
                </div>

                <Card>
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="flex flex-col gap-3">
                            <Textarea rows={4} value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} placeholder="Add a reply…" />
                            <div className="flex justify-end">
                                <Button type="submit" disabled={form.processing || !form.data.body.trim()}>Reply</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Bubble({ author, when, body, staff }: { author: string; when: string | null; body: string; staff: boolean }) {
    return (
        <div className={`rounded-lg border p-4 ${staff ? 'border-[#1b4638]/30 bg-[#1b4638]/5' : 'bg-card'}`}>
            <div className="mb-1 flex items-center justify-between text-xs">
                <span className="font-medium">{author}</span>
                <span className="text-muted-foreground">{fmt(when)}</span>
            </div>
            <p className="whitespace-pre-wrap text-sm">{body}</p>
        </div>
    );
}

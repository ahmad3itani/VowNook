import { Head, useForm } from '@inertiajs/react';
import { Megaphone, Send, Users } from 'lucide-react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';

type Broadcast = {
    id: number;
    subject: string;
    body: string;
    audience: string;
    recipient_count: number;
    sent_at: string | null;
};

const AUDIENCE_LABELS: Record<string, string> = {
    all: 'Everyone with an email',
    attending: 'Guests attending',
    pending: "Haven't replied yet",
    maybe: 'Maybe / undecided',
};

const dateFmt = new Intl.DateTimeFormat('en-CA', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });

export default function CommunicationsIndex({
    counts,
    history,
    audiences,
}: {
    counts: Record<string, number>;
    history: Broadcast[];
    audiences: string[];
}) {
    const { canWrite } = usePermissions();
    const writable = canWrite('guests');

    const form = useForm({ subject: '', body: '', audience: 'all' });
    const recipients = counts[form.data.audience] ?? 0;

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (recipients === 0) {
            toast.error('No guests with an email in that audience.');
            return;
        }
        if (!confirm(`Send this message to ${recipients} guest${recipients === 1 ? '' : 's'}?`)) return;
        form.post('/messages', {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Message sent to your guests.');
                form.reset('subject', 'body');
            },
        });
    }

    return (
        <>
            <Head title="Message guests" />

            <div className="flex h-full flex-1 flex-col gap-8 p-4">
                <Heading
                    title="Message your guests"
                    description="Send a quick announcement — a venue change, a reminder, a thank-you — to everyone or just part of your list. Guests who've added an email receive it."
                />

                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={submit} className="flex flex-col gap-4">
                            <div className="grid gap-2">
                                <Label>Send to</Label>
                                <Select value={form.data.audience} onValueChange={(v) => form.setData('audience', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {audiences.map((a) => (
                                            <SelectItem key={a} value={a}>
                                                {AUDIENCE_LABELS[a] ?? a} ({counts[a] ?? 0})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Users className="size-4" />
                                    {recipients} guest{recipients === 1 ? '' : 's'} will receive this
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label>Subject</Label>
                                <Input value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} placeholder="A quick update about our big day" />
                                <InputError message={form.errors.subject} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Message</Label>
                                <Textarea
                                    value={form.data.body}
                                    onChange={(e) => form.setData('body', e.target.value)}
                                    rows={7}
                                    placeholder={"Hi everyone,\n\nJust a reminder that the ceremony now begins at 4:30 PM. We can't wait to celebrate with you!"}
                                />
                                <p className="text-xs text-muted-foreground">Sent from your wedding, with a link to your website. Guests can always reply to your email.</p>
                                <InputError message={form.errors.body} />
                            </div>

                            {writable && (
                                <div>
                                    <Button type="submit" disabled={form.processing}>
                                        {form.processing ? <Spinner /> : <Send className="size-4" />} Send message
                                    </Button>
                                </div>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Sent history */}
                <section className="flex flex-col gap-3">
                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                        <Megaphone className="size-5 text-[#1f5142]" /> Sent messages
                    </h2>
                    {history.length === 0 ? (
                        <Card><CardContent className="py-10 text-center text-sm text-muted-foreground">Nothing sent yet.</CardContent></Card>
                    ) : (
                        <div className="flex flex-col gap-3">
                            {history.map((b) => (
                                <Card key={b.id}>
                                    <CardContent className="space-y-1 py-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <p className="font-medium">{b.subject}</p>
                                            <span className="text-xs text-muted-foreground">
                                                {b.sent_at ? dateFmt.format(new Date(b.sent_at)) : ''}
                                            </span>
                                        </div>
                                        <p className="line-clamp-2 text-sm text-muted-foreground">{b.body}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {AUDIENCE_LABELS[b.audience] ?? b.audience} · {b.recipient_count} recipient{b.recipient_count === 1 ? '' : 's'}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

CommunicationsIndex.layout = {
    breadcrumbs: [{ title: 'Message guests', href: '/messages' }],
};

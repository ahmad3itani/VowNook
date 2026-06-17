import { Head, router } from '@inertiajs/react';
import { CalendarHeart, Eye, MailCheck, Send, Users } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { usePermissions } from '@/hooks/use-permissions';

type KindStats = { sent: number; opened: number; responded: number };

const KIND_META: Record<string, { title: string; blurb: string; cta: string }> = {
    save_the_date: {
        title: 'Save-the-dates',
        blurb: 'Give guests an early heads-up so they can mark the calendar and plan travel.',
        cta: 'Send save-the-dates',
    },
    invitation: {
        title: 'Invitations',
        blurb: 'Send the formal invitation with a direct link to RSVP on your website.',
        cta: 'Send invitations',
    },
};

function Stat({ icon: Icon, label, value }: { icon: typeof Eye; label: string; value: number }) {
    return (
        <div className="flex flex-col items-center rounded-lg bg-[#f6efe1] px-3 py-3 text-center">
            <Icon className="size-4 text-[#8a651c]" />
            <span className="mt-1 text-xl font-semibold text-[#1e1b17]">{value}</span>
            <span className="text-[11px] tracking-wide text-muted-foreground uppercase">{label}</span>
        </div>
    );
}

export default function SaveTheDatesIndex({
    stats,
    guests_with_email,
    kinds,
}: {
    stats: Record<string, KindStats>;
    guests_with_email: number;
    kinds: string[];
}) {
    const { canWrite } = usePermissions();
    const writable = canWrite('guests');
    const [sending, setSending] = useState<string | null>(null);

    function send(kind: string) {
        const meta = KIND_META[kind];
        if (guests_with_email === 0) {
            toast.error('No guests have an email address yet.');
            return;
        }
        if (!confirm(`Send ${meta.title.toLowerCase()} to ${guests_with_email} guest${guests_with_email === 1 ? '' : 's'}?`)) return;
        setSending(kind);
        router.post('/save-the-dates/send', { kind }, {
            preserveScroll: true,
            onSuccess: () => toast.success(`${meta.title} sent.`),
            onFinish: () => setSending(null),
        });
    }

    return (
        <>
            <Head title="Save-the-dates" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Save-the-dates & invitations"
                    description="Email a beautiful save-the-date or invitation to your guests — and see who's opened it."
                />

                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Users className="size-4" /> {guests_with_email} guest{guests_with_email === 1 ? '' : 's'} have an email address
                </p>

                <div className="grid gap-5 lg:grid-cols-2">
                    {kinds.map((kind) => {
                        const meta = KIND_META[kind];
                        const s = stats[kind] ?? { sent: 0, opened: 0, responded: 0 };
                        const Icon = kind === 'invitation' ? MailCheck : CalendarHeart;
                        return (
                            <Card key={kind}>
                                <CardContent className="flex flex-col gap-4 pt-6">
                                    <div className="flex items-start gap-3">
                                        <div className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-[#f6efe1] text-[#775a19]">
                                            <Icon className="size-5" />
                                        </div>
                                        <div>
                                            <h2 className="font-semibold">{meta.title}</h2>
                                            <p className="text-sm text-muted-foreground">{meta.blurb}</p>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-3 gap-2">
                                        <Stat icon={Send} label="Sent" value={s.sent} />
                                        <Stat icon={Eye} label="Opened" value={s.opened} />
                                        <Stat icon={MailCheck} label="Replied" value={s.responded} />
                                    </div>

                                    {writable && (
                                        <Button onClick={() => send(kind)} disabled={sending !== null} className="w-full">
                                            <Send className="size-4" /> {s.sent > 0 ? 'Resend' : meta.cta}
                                        </Button>
                                    )}
                                    {s.sent > 0 && (
                                        <p className="text-center text-xs text-muted-foreground">
                                            Resending refreshes the open count.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <p className="text-xs text-muted-foreground">
                    Open tracking relies on the guest's email app loading images, so opens are a guide, not a guarantee — some apps block images by default.
                </p>
            </div>
        </>
    );
}

SaveTheDatesIndex.layout = {
    breadcrumbs: [{ title: 'Save-the-dates', href: '/save-the-dates' }],
};

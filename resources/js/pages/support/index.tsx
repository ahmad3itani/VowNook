import { Head, Link, useForm } from '@inertiajs/react';
import { LifeBuoy, Plus, Send, Sparkles } from 'lucide-react';
import { FormEvent, ReactNode, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Ticket = {
    id: number;
    subject: string;
    category: string;
    status: string;
    status_label: string;
    last_reply_at: string | null;
    created_at: string | null;
};

type PageProps = {
    tickets: Ticket[];
    categories: string[];
    ai_enabled: boolean;
};

function readXsrf(): string {
    const c = document.cookie
        .split('; ')
        .find((x) => x.startsWith('XSRF-TOKEN='));
    return c ? decodeURIComponent(c.split('=')[1]) : '';
}

/** Render **bold** segments as real nodes (no HTML injection — just text + <strong>). */
function renderInline(text: string): ReactNode[] {
    return text.split(/(\*\*[^*]+\*\*)/g).map((part, i) =>
        part.startsWith('**') && part.endsWith('**') ? (
            <strong key={i} className="font-semibold text-[#1e1b17]">
                {part.slice(2, -2)}
            </strong>
        ) : (
            <span key={i}>{part}</span>
        ),
    );
}

/** Turn the assistant's lightweight markdown (paragraphs, bold, "-" bullets) into clean, styled output. */
function FormattedAnswer({ text }: { text: string }) {
    const blocks = text.trim().split(/\n{2,}/);

    return (
        <div className="flex flex-col gap-2 leading-relaxed text-foreground">
            {blocks.map((block, bi) => {
                const lines = block.split('\n');
                const isList =
                    lines.length > 0 &&
                    lines.every((l) => /^\s*[-*•]\s+/.test(l));

                if (isList) {
                    return (
                        <ul key={bi} className="flex flex-col gap-1.5">
                            {lines.map((l, li) => (
                                <li key={li} className="flex gap-2">
                                    <span
                                        className="mt-[7px] size-1.5 shrink-0 rounded-full bg-[#c79a3f]"
                                        aria-hidden
                                    />
                                    <span>
                                        {renderInline(
                                            l.replace(/^\s*[-*•]\s+/, ''),
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    );
                }

                return (
                    <p key={bi} className="whitespace-pre-line">
                        {renderInline(block)}
                    </p>
                );
            })}
        </div>
    );
}

function AskAi() {
    const [question, setQuestion] = useState('');
    const [answer, setAnswer] = useState<{
        text: string;
        confident: boolean;
    } | null>(null);
    const [loading, setLoading] = useState(false);

    async function ask(e: FormEvent) {
        e.preventDefault();
        if (!question.trim() || loading) return;
        setLoading(true);
        setAnswer(null);
        try {
            const res = await fetch('/support/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': readXsrf(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ question }),
            });
            const data = await res.json();
            setAnswer({
                text:
                    data.answer ??
                    'Sorry, I couldn’t answer that right now — please send a request below.',
                confident: !!data.confident,
            });
        } catch {
            setAnswer({
                text: 'Sorry, something went wrong. Please send a request below and our team will help.',
                confident: false,
            });
        } finally {
            setLoading(false);
        }
    }

    return (
        <Card className="border-[#e9c176]/50 bg-[#fdf8ee]">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <Sparkles className="size-4 text-[#8a651c]" /> Ask VowNook
                    AI
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    Instant answers to common questions — like how to add
                    guests, publish your site, or upgrade.
                </p>
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
                <form onSubmit={ask} className="flex gap-2">
                    <Input
                        value={question}
                        onChange={(e) => setQuestion(e.target.value)}
                        placeholder="e.g. How do I collect RSVPs?"
                        aria-label="Ask the VowNook assistant"
                    />
                    <Button
                        type="submit"
                        disabled={loading || !question.trim()}
                        className="bg-[#8a651c] hover:bg-[#6f5016]"
                    >
                        {loading ? 'Thinking…' : <Send className="size-4" />}
                    </Button>
                </form>
                {answer && (
                    <div className="flex gap-2.5 rounded-xl border border-[#e9c176]/60 bg-white/80 p-4 text-sm shadow-sm">
                        <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#8a651c] text-white">
                            <Sparkles className="size-3.5" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <FormattedAnswer text={answer.text} />
                            {!answer.confident && (
                                <p className="mt-3 border-t border-[#e9c176]/30 pt-2 text-xs text-muted-foreground">
                                    Still need a hand? Send a request below and
                                    our team will follow up.
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function statusVariant(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'open') return 'default';
    if (status === 'pending') return 'secondary';
    return 'outline';
}

function fmt(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export default function SupportIndex({
    tickets,
    categories,
    ai_enabled,
}: PageProps) {
    const form = useForm({ subject: '', category: 'general', message: '' });

    function submit(e: FormEvent) {
        e.preventDefault();
        form.post('/support', { onSuccess: () => form.reset() });
    }

    return (
        <>
            <Head title="Help & support" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Help & support"
                    description="Get instant answers from our AI helper, or send our team a request — we usually reply within a day."
                />

                {ai_enabled && <AskAi />}

                <div className="grid gap-6 lg:grid-cols-5">
                    {/* New request */}
                    <Card className="lg:order-last lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Plus className="size-4" /> New request
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={submit}
                                className="flex flex-col gap-4"
                            >
                                <div className="grid gap-1.5">
                                    <Label htmlFor="subject">Subject</Label>
                                    <Input
                                        id="subject"
                                        value={form.data.subject}
                                        onChange={(e) =>
                                            form.setData(
                                                'subject',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {form.errors.subject && (
                                        <p className="text-xs text-destructive">
                                            {form.errors.subject}
                                        </p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label>Category</Label>
                                    <Select
                                        value={form.data.category}
                                        onValueChange={(v) =>
                                            form.setData('category', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((c) => (
                                                <SelectItem
                                                    key={c}
                                                    value={c}
                                                    className="capitalize"
                                                >
                                                    {c}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="message">
                                        How can we help?
                                    </Label>
                                    <Textarea
                                        id="message"
                                        rows={5}
                                        value={form.data.message}
                                        onChange={(e) =>
                                            form.setData(
                                                'message',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {form.errors.message && (
                                        <p className="text-xs text-destructive">
                                            {form.errors.message}
                                        </p>
                                    )}
                                </div>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    Send request
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Past tickets */}
                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Your requests
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {tickets.length === 0 ? (
                                <div className="flex flex-col items-center gap-2 py-12 text-muted-foreground">
                                    <LifeBuoy className="size-7" />
                                    <p className="text-sm">No requests yet.</p>
                                </div>
                            ) : (
                                <ul className="divide-y">
                                    {tickets.map((t) => (
                                        <li key={t.id}>
                                            <Link
                                                href={`/support/${t.id}`}
                                                className="flex items-center justify-between gap-3 px-6 py-3 hover:bg-muted/40"
                                            >
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium">
                                                            {t.subject}
                                                        </span>
                                                        <Badge
                                                            variant={statusVariant(
                                                                t.status,
                                                            )}
                                                            className="text-[10px]"
                                                        >
                                                            {t.status_label}
                                                        </Badge>
                                                    </div>
                                                    <div className="text-xs text-muted-foreground capitalize">
                                                        {t.category} ·{' '}
                                                        {fmt(t.created_at)}
                                                    </div>
                                                </div>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

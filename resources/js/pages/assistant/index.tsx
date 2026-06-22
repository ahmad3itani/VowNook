import { Head, Link, router } from '@inertiajs/react';
import {
    CalendarClock,
    ListChecks,
    Plus,
    RotateCcw,
    Send,
    Sparkles,
    Trash2,
    Wallet,
    WandSparkles,
} from 'lucide-react';
import { FormEvent, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { FormattedMessage } from '@/components/ai/formatted-message';
import Heading from '@/components/heading';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Option = { value: string; label: string };
type Kind = 'checklist' | 'budget' | 'timeline';
type ChatMessage = { id?: number; role: 'user' | 'assistant'; content: string };

type PageProps = {
    configured: boolean;
    entitled: boolean;
    wedding: { name: string; event_date: string | null; guest_count: number };
    can: Record<Kind, boolean>;
    options: {
        task_categories: Option[];
        task_priorities: Option[];
        event_types: Option[];
    };
    history: ChatMessage[];
};

type ChecklistItem = {
    title: string;
    category: string;
    priority: string;
    months_before: number;
};
type BudgetItem = { category: string; name: string; estimated_cents: number };
type TimelineItem = {
    title: string;
    type: string;
    time: string;
    location: string | null;
};
type Item = ChecklistItem | BudgetItem | TimelineItem;

const GENERATORS: {
    kind: Kind;
    label: string;
    icon: typeof ListChecks;
    blurb: string;
    target: string;
}[] = [
    {
        kind: 'checklist',
        label: 'Checklist',
        icon: ListChecks,
        blurb: 'A complete, prioritised to-do list tailored to your date and guest count.',
        target: '/checklist',
    },
    {
        kind: 'budget',
        label: 'Budget',
        icon: Wallet,
        blurb: 'A realistic Ontario starter budget, broken into categories and line items.',
        target: '/budget',
    },
    {
        kind: 'timeline',
        label: 'Day-of timeline',
        icon: CalendarClock,
        blurb: 'A wedding-day run-of-show from getting ready through the send-off.',
        target: '/timeline',
    },
];

// Inertia sets an XSRF-TOKEN cookie; forward it on our JSON fetch.
function xsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

const CHAT_SUGGESTIONS = [
    'Help me plan a $30,000 Ontario wedding',
    'What should we be doing 6 months out?',
    'Draft a day-of timeline for a 4pm ceremony',
    'How do we politely ask for no kids?',
];

/** Three pulsing dots shown in the assistant bubble before the first token arrives. */
function TypingDots() {
    return (
        <span
            className="flex items-center gap-1 py-0.5"
            aria-label="Planner is typing"
        >
            {[0, 1, 2].map((i) => (
                <span
                    key={i}
                    className="size-1.5 animate-bounce rounded-full bg-[#775a19]/50"
                    style={{ animationDelay: `${i * 0.15}s` }}
                />
            ))}
        </span>
    );
}

/** A persisted, wedding-aware conversation with the AI planner. */
function ChatPlanner({
    weddingName,
    history,
}: {
    weddingName: string;
    history: ChatMessage[];
}) {
    const [messages, setMessages] = useState<ChatMessage[]>(history);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const threadRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const el = threadRef.current;
        if (el) el.scrollTop = el.scrollHeight;
    }, [messages, sending]);

    function patchLastAssistant(patch: Partial<ChatMessage>) {
        setMessages((prev) => {
            const next = prev.slice();
            const last = next[next.length - 1];
            if (last && last.role === 'assistant') {
                next[next.length - 1] = { ...last, ...patch };
            }
            return next;
        });
    }

    function dropEmptyAssistant() {
        setMessages((prev) => {
            const last = prev[prev.length - 1];
            return last && last.role === 'assistant' && last.content === ''
                ? prev.slice(0, -1)
                : prev;
        });
    }

    async function send(text: string) {
        const q = text.trim();
        if (!q || sending) return;
        setInput('');
        // Append the user turn + an empty assistant bubble we fill as deltas stream in.
        setMessages((prev) => [
            ...prev,
            { role: 'user', content: q },
            { role: 'assistant', content: '' },
        ]);
        setSending(true);

        try {
            const res = await fetch('/assistant/chat/stream', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-XSRF-TOKEN': xsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ message: q }),
            });

            const contentType = res.headers.get('content-type') ?? '';
            if (
                !res.ok ||
                !res.body ||
                !contentType.includes('text/event-stream')
            ) {
                const data = await res.json().catch(() => ({}));
                dropEmptyAssistant();
                toast.error(
                    data.available === false
                        ? 'AI isn’t configured on this server yet.'
                        : (data.message ??
                              'Something went wrong. Please try again.'),
                );
                return;
            }

            const reader = res.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let acc = '';
            let errorMsg = '';

            for (;;) {
                const { done, value } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });

                let sep: number;
                while ((sep = buffer.indexOf('\n\n')) !== -1) {
                    const frame = buffer.slice(0, sep).trim();
                    buffer = buffer.slice(sep + 2);
                    if (!frame.startsWith('data:')) continue;
                    const json = frame.slice(5).trim();
                    if (!json) continue;

                    let p: {
                        delta?: string;
                        error?: string;
                        done?: boolean;
                        id?: number;
                    };
                    try {
                        p = JSON.parse(json);
                    } catch {
                        continue;
                    }

                    if (typeof p.delta === 'string') {
                        acc += p.delta;
                        patchLastAssistant({ content: acc });
                    } else if (p.error) {
                        errorMsg = p.error;
                    } else if (p.done && typeof p.id === 'number') {
                        patchLastAssistant({ id: p.id });
                    }
                }
            }

            if (errorMsg) {
                toast.error(errorMsg);
                if (acc === '') dropEmptyAssistant();
            }
        } catch (e) {
            dropEmptyAssistant();
            toast.error(
                e instanceof Error ? e.message : 'Something went wrong.',
            );
        } finally {
            setSending(false);
        }
    }

    function onSubmit(e: FormEvent) {
        e.preventDefault();
        void send(input);
    }

    function clearChat() {
        if (sending) return;
        router.delete('/assistant/chat', {
            preserveScroll: true,
            onSuccess: () => setMessages([]),
            onError: () =>
                toast.error('Could not clear the chat. Please try again.'),
        });
    }

    return (
        <Card className="border-[#775a19]/25">
            <CardContent className="flex flex-col gap-4 py-5">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2.5">
                        <span className="flex size-8 items-center justify-center rounded-full bg-[#775a19] text-white">
                            <Sparkles className="size-4" />
                        </span>
                        <div>
                            <p className="leading-none font-medium">
                                Chat with your planner
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Ask anything about planning {weddingName}.
                            </p>
                        </div>
                    </div>
                    {messages.length > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={clearChat}
                            className="text-muted-foreground"
                        >
                            <RotateCcw className="size-3.5" /> Clear
                        </Button>
                    )}
                </div>

                <div
                    ref={threadRef}
                    className="flex max-h-[55vh] min-h-40 flex-col gap-3 overflow-y-auto pr-1"
                >
                    {messages.length === 0 && !sending && (
                        <div className="flex flex-col items-center gap-3 py-6 text-center">
                            <p className="max-w-md text-sm text-muted-foreground">
                                Your planner already knows your date, guest
                                count and venue. Ask away — or start with one of
                                these:
                            </p>
                            <div className="flex flex-wrap justify-center gap-2">
                                {CHAT_SUGGESTIONS.map((s) => (
                                    <button
                                        key={s}
                                        type="button"
                                        onClick={() => send(s)}
                                        className="rounded-full border border-[#775a19]/30 bg-[#775a19]/[0.04] px-3 py-1.5 text-xs text-[#775a19] transition-colors hover:bg-[#775a19]/[0.09]"
                                    >
                                        {s}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {messages.map((m, i) =>
                        m.role === 'user' ? (
                            <div key={i} className="flex justify-end">
                                <div className="max-w-[85%] rounded-2xl rounded-br-sm bg-[#775a19] px-4 py-2.5 text-sm whitespace-pre-line text-white">
                                    {m.content}
                                </div>
                            </div>
                        ) : (
                            <div key={i} className="flex gap-2.5">
                                <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#775a19]/10 text-[#775a19]">
                                    <Sparkles className="size-3.5" />
                                </span>
                                <div className="max-w-[85%] rounded-2xl rounded-bl-sm border border-border bg-card px-4 py-2.5 text-sm text-foreground">
                                    {m.content === '' ? (
                                        <TypingDots />
                                    ) : (
                                        <FormattedMessage text={m.content} />
                                    )}
                                </div>
                            </div>
                        ),
                    )}
                </div>

                <form onSubmit={onSubmit} className="flex items-end gap-2">
                    <Textarea
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                void send(input);
                            }
                        }}
                        placeholder="Message your planner…  (Enter to send, Shift+Enter for a new line)"
                        rows={1}
                        className="max-h-32 min-h-[42px] resize-none"
                    />
                    <Button
                        type="submit"
                        disabled={sending || !input.trim()}
                        className="shrink-0"
                    >
                        {sending ? <Spinner /> : <Send className="size-4" />}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

async function generate(
    kind: Kind,
    notes: string,
    totalBudget: string,
): Promise<Item[]> {
    const res = await fetch('/assistant/generate', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            kind,
            notes: notes.trim() || null,
            total_budget:
                kind === 'budget' && totalBudget ? Number(totalBudget) : null,
        }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok)
        throw new Error(data.message ?? 'Generation failed. Please try again.');
    return (data.items ?? []) as Item[];
}

export default function AssistantIndex({
    configured,
    entitled,
    wedding,
    can,
    options,
    history,
}: PageProps) {
    const [kind, setKind] = useState<Kind>('checklist');
    const [notes, setNotes] = useState('');
    const [totalBudget, setTotalBudget] = useState('');
    const [items, setItems] = useState<Item[]>([]);
    const [loading, setLoading] = useState(false);
    const [applying, setApplying] = useState(false);

    const active = GENERATORS.find((g) => g.kind === kind)!;
    const canApply = can[kind];

    function switchKind(next: Kind) {
        setKind(next);
        setItems([]);
    }

    async function onGenerate() {
        if (loading) return;
        setLoading(true);
        try {
            const result = await generate(kind, notes, totalBudget);
            setItems(result);
            if (result.length === 0) {
                toast.message(
                    'The assistant returned nothing to add. Try adding a little more context.',
                );
            }
        } catch (e) {
            toast.error(
                e instanceof Error ? e.message : 'Something went wrong.',
            );
        } finally {
            setLoading(false);
        }
    }

    function updateItem(index: number, patch: Partial<Item>) {
        setItems((prev) =>
            prev.map((it, i) => (i === index ? { ...it, ...patch } : it)),
        );
    }

    function removeItem(index: number) {
        setItems((prev) => prev.filter((_, i) => i !== index));
    }

    function addBlankRow() {
        const blank: Record<Kind, Item> = {
            checklist: {
                title: '',
                category: options.task_categories[0]?.value ?? 'planning',
                priority: 'medium',
                months_before: 0,
            },
            budget: { category: 'Other', name: '', estimated_cents: 0 },
            timeline: {
                title: '',
                type: options.event_types[0]?.value ?? 'other',
                time: '12:00',
                location: null,
            },
        };
        setItems((prev) => [...prev, blank[kind]]);
    }

    function onApply() {
        if (applying || items.length === 0) return;
        setApplying(true);
        router.post(
            '/assistant/apply',
            {
                kind,
                items: items as unknown as Record<
                    string,
                    string | number | null
                >[],
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(
                        `Added ${items.length} ${active.label.toLowerCase()} item${items.length === 1 ? '' : 's'}.`,
                    );
                    router.visit(active.target);
                },
                onError: () =>
                    toast.error(
                        'Some items could not be saved. Please review and try again.',
                    ),
                onFinish: () => setApplying(false),
            },
        );
    }

    return (
        <>
            <Head title="AI assistant" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="AI assistant"
                    description={`Let AI draft a starting point for ${wedding.name}, then edit anything before it's saved.`}
                />

                {!entitled && <UpgradeCard />}

                {entitled && (
                    <>
                        {!configured && (
                            <Card>
                                <CardContent className="flex items-start gap-3 py-5 text-sm text-muted-foreground">
                                    <WandSparkles className="mt-0.5 size-5 shrink-0 text-[#775a19]" />
                                    <p>
                                        AI assistance isn’t configured on this
                                        server yet. Once an API key is added it
                                        will appear here — in the meantime you
                                        can still build your checklist, budget
                                        and timeline by hand.
                                    </p>
                                </CardContent>
                            </Card>
                        )}

                        {/* Conversational planner — the main event. */}
                        {configured && (
                            <ChatPlanner
                                weddingName={wedding.name}
                                history={history}
                            />
                        )}

                        {/* Quick starters — one-click drafts the couple edits before saving. */}
                        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1 pt-2">
                            <h2 className="font-serif text-lg">
                                Quick starters
                            </h2>
                            <span className="text-sm text-muted-foreground">
                                Draft a full checklist, budget or day-of
                                timeline in one click — then edit before saving.
                            </span>
                        </div>

                        {/* Generator picker */}
                        <div className="grid gap-4 sm:grid-cols-3">
                            {GENERATORS.map((g) => {
                                const Icon = g.icon;
                                const activeCard = g.kind === kind;
                                return (
                                    <button
                                        key={g.kind}
                                        type="button"
                                        onClick={() => switchKind(g.kind)}
                                        className={`flex flex-col items-start gap-2 rounded-xl border p-4 text-left transition-colors ${
                                            activeCard
                                                ? 'shadow-atelier border-[#775a19] bg-[#775a19]/[0.06]'
                                                : 'border-border bg-card hover:border-[#775a19]/40'
                                        }`}
                                    >
                                        <Icon
                                            className={`size-5 ${activeCard ? 'text-[#775a19]' : 'text-muted-foreground'}`}
                                        />
                                        <span className="font-medium">
                                            {g.label}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {g.blurb}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>

                        {/* Context + generate */}
                        <Card>
                            <CardContent className="flex flex-col gap-4 py-5">
                                {kind === 'budget' && (
                                    <div className="grid max-w-xs gap-2">
                                        <Label htmlFor="total_budget">
                                            Total budget (optional, CAD)
                                        </Label>
                                        <Input
                                            id="total_budget"
                                            type="number"
                                            min={0}
                                            inputMode="numeric"
                                            placeholder="e.g. 35000"
                                            value={totalBudget}
                                            onChange={(e) =>
                                                setTotalBudget(e.target.value)
                                            }
                                        />
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="notes">
                                        Anything the assistant should know?
                                        (optional)
                                    </Label>
                                    <Textarea
                                        id="notes"
                                        value={notes}
                                        onChange={(e) =>
                                            setNotes(e.target.value)
                                        }
                                        placeholder="Style, season, must-haves, things to skip…"
                                        rows={2}
                                    />
                                </div>

                                <div className="flex items-center gap-3">
                                    <Button
                                        onClick={onGenerate}
                                        disabled={
                                            !configured || !canApply || loading
                                        }
                                    >
                                        {loading ? (
                                            <Spinner />
                                        ) : (
                                            <Sparkles className="size-4" />
                                        )}
                                        {items.length > 0
                                            ? 'Regenerate'
                                            : `Generate ${active.label.toLowerCase()}`}
                                    </Button>
                                    {!canApply && configured && (
                                        <span className="text-xs text-muted-foreground">
                                            You don’t have permission to add to
                                            this section.
                                        </span>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Editable preview */}
                        {items.length > 0 && (
                            <Card>
                                <CardContent className="flex flex-col gap-4 py-5">
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm text-muted-foreground">
                                            Review and edit — nothing is saved
                                            until you add it.
                                        </p>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={addBlankRow}
                                        >
                                            <Plus className="size-4" /> Add row
                                        </Button>
                                    </div>

                                    <div className="overflow-x-auto">
                                        {kind === 'checklist' && (
                                            <ChecklistTable
                                                items={items as ChecklistItem[]}
                                                options={options}
                                                onChange={updateItem}
                                                onRemove={removeItem}
                                            />
                                        )}
                                        {kind === 'budget' && (
                                            <BudgetTable
                                                items={items as BudgetItem[]}
                                                onChange={updateItem}
                                                onRemove={removeItem}
                                            />
                                        )}
                                        {kind === 'timeline' && (
                                            <TimelineTable
                                                items={items as TimelineItem[]}
                                                options={options}
                                                onChange={updateItem}
                                                onRemove={removeItem}
                                            />
                                        )}
                                    </div>

                                    <div className="flex items-center justify-end gap-3">
                                        <span className="text-sm text-muted-foreground">
                                            {items.length} item
                                            {items.length === 1 ? '' : 's'}
                                        </span>
                                        <Button
                                            onClick={onApply}
                                            disabled={applying || !canApply}
                                        >
                                            {applying && <Spinner />}
                                            Add to my{' '}
                                            {active.label.toLowerCase()}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function UpgradeCard() {
    return (
        <Card>
            <CardContent className="flex flex-col items-start gap-4 py-8">
                <div className="flex items-center gap-3">
                    <span className="flex size-10 items-center justify-center rounded-full bg-[#775a19]/10">
                        <WandSparkles className="size-5 text-[#775a19]" />
                    </span>
                    <div>
                        <h2 className="font-serif text-lg">
                            AI planning is a premium perk
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Generate a tailored checklist, budget, and day-of
                            timeline in seconds.
                        </p>
                    </div>
                </div>
                <p className="max-w-prose text-sm text-muted-foreground">
                    Upgrade to Premium to let AI draft everything from your
                    wedding details — then edit anything before it’s saved.
                    You’ll also unlock higher guest, collaborator and photo
                    limits.
                </p>
                <Button asChild>
                    <Link href="/#pricing">View plans</Link>
                </Button>
            </CardContent>
        </Card>
    );
}

type RowProps<T> = {
    items: T[];
    options: PageProps['options'];
    onChange: (index: number, patch: Partial<Item>) => void;
    onRemove: (index: number) => void;
};

function ChecklistTable({
    items,
    options,
    onChange,
    onRemove,
}: RowProps<ChecklistItem>) {
    return (
        <table className="w-full text-sm">
            <thead className="border-b text-left text-muted-foreground">
                <tr>
                    <th className="px-2 py-2 font-medium">Task</th>
                    <th className="w-40 px-2 py-2 font-medium">Category</th>
                    <th className="w-32 px-2 py-2 font-medium">Priority</th>
                    <th className="w-32 px-2 py-2 font-medium">
                        Months before
                    </th>
                    <th className="w-10 px-2 py-2" />
                </tr>
            </thead>
            <tbody>
                {items.map((it, i) => (
                    <tr key={i} className="border-b last:border-0">
                        <td className="px-2 py-1.5">
                            <Input
                                value={it.title}
                                onChange={(e) =>
                                    onChange(i, { title: e.target.value })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <Select
                                value={it.category}
                                onValueChange={(v) =>
                                    onChange(i, { category: v })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.task_categories.map((o) => (
                                        <SelectItem
                                            key={o.value}
                                            value={o.value}
                                        >
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </td>
                        <td className="px-2 py-1.5">
                            <Select
                                value={it.priority}
                                onValueChange={(v) =>
                                    onChange(i, { priority: v })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.task_priorities.map((o) => (
                                        <SelectItem
                                            key={o.value}
                                            value={o.value}
                                        >
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </td>
                        <td className="px-2 py-1.5">
                            <Input
                                type="number"
                                min={0}
                                max={36}
                                value={it.months_before}
                                onChange={(e) =>
                                    onChange(i, {
                                        months_before: Number(e.target.value),
                                    })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <RemoveButton onClick={() => onRemove(i)} />
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function BudgetTable({
    items,
    onChange,
    onRemove,
}: Omit<RowProps<BudgetItem>, 'options'>) {
    return (
        <table className="w-full text-sm">
            <thead className="border-b text-left text-muted-foreground">
                <tr>
                    <th className="w-44 px-2 py-2 font-medium">Category</th>
                    <th className="px-2 py-2 font-medium">Line item</th>
                    <th className="w-36 px-2 py-2 font-medium">
                        Estimated (CAD)
                    </th>
                    <th className="w-10 px-2 py-2" />
                </tr>
            </thead>
            <tbody>
                {items.map((it, i) => (
                    <tr key={i} className="border-b last:border-0">
                        <td className="px-2 py-1.5">
                            <Input
                                value={it.category}
                                onChange={(e) =>
                                    onChange(i, { category: e.target.value })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <Input
                                value={it.name}
                                onChange={(e) =>
                                    onChange(i, { name: e.target.value })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <Input
                                type="number"
                                min={0}
                                value={Math.round(it.estimated_cents / 100)}
                                onChange={(e) =>
                                    onChange(i, {
                                        estimated_cents: Math.round(
                                            Number(e.target.value) * 100,
                                        ),
                                    })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <RemoveButton onClick={() => onRemove(i)} />
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function TimelineTable({
    items,
    options,
    onChange,
    onRemove,
}: RowProps<TimelineItem>) {
    return (
        <table className="w-full text-sm">
            <thead className="border-b text-left text-muted-foreground">
                <tr>
                    <th className="w-24 px-2 py-2 font-medium">Time</th>
                    <th className="px-2 py-2 font-medium">Event</th>
                    <th className="w-40 px-2 py-2 font-medium">Type</th>
                    <th className="px-2 py-2 font-medium">Location</th>
                    <th className="w-10 px-2 py-2" />
                </tr>
            </thead>
            <tbody>
                {items.map((it, i) => (
                    <tr key={i} className="border-b last:border-0">
                        <td className="px-2 py-1.5">
                            <Input
                                type="time"
                                value={it.time}
                                onChange={(e) =>
                                    onChange(i, { time: e.target.value })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <Input
                                value={it.title}
                                onChange={(e) =>
                                    onChange(i, { title: e.target.value })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <Select
                                value={it.type}
                                onValueChange={(v) => onChange(i, { type: v })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.event_types.map((o) => (
                                        <SelectItem
                                            key={o.value}
                                            value={o.value}
                                        >
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </td>
                        <td className="px-2 py-1.5">
                            <Input
                                value={it.location ?? ''}
                                onChange={(e) =>
                                    onChange(i, {
                                        location: e.target.value || null,
                                    })
                                }
                            />
                        </td>
                        <td className="px-2 py-1.5">
                            <RemoveButton onClick={() => onRemove(i)} />
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function RemoveButton({ onClick }: { onClick: () => void }) {
    return (
        <Button
            variant="ghost"
            size="icon"
            onClick={onClick}
            aria-label="Remove row"
        >
            <Trash2 className="size-4" />
        </Button>
    );
}

AssistantIndex.layout = {
    breadcrumbs: [{ title: 'AI assistant', href: '/assistant' }],
};

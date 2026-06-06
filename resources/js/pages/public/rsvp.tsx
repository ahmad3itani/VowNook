import { Head, useForm } from '@inertiajs/react';
import { Check, Heart, Search } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Match = {
    id: number;
    name: string;
    rsvp_status: string;
    meal_choice: string | null;
    dietary_notes: string | null;
};

type PageProps = {
    wedding: { name: string; slug: string; event_date: string | null };
    matches: Match[];
    searched: boolean;
};

const REPLIES: { value: string; label: string }[] = [
    { value: 'attending', label: 'Joyfully accepts' },
    { value: 'maybe', label: 'Maybe' },
    { value: 'declined', label: 'Regretfully declines' },
];

const dateFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

export default function PublicRsvp({ wedding, matches, searched }: PageProps) {
    const [selected, setSelected] = useState<Match | null>(null);
    const [done, setDone] = useState(false);

    const lookup = useForm({ name: '' });

    const respond = useForm({
        guest_id: 0,
        rsvp_status: 'attending',
        meal_choice: '',
        dietary_notes: '',
    });

    function submitLookup(e: React.FormEvent) {
        e.preventDefault();
        setSelected(null);
        setDone(false);
        lookup.post(`/w/${wedding.slug}/lookup`, {
            preserveScroll: true,
            preserveState: true,
            only: ['matches', 'searched'],
        });
    }

    function choose(match: Match) {
        setSelected(match);
        setDone(false);
        respond.setData({
            guest_id: match.id,
            rsvp_status: match.rsvp_status === 'pending' ? 'attending' : match.rsvp_status,
            meal_choice: match.meal_choice ?? '',
            dietary_notes: match.dietary_notes ?? '',
        });
    }

    function submitRespond(e: React.FormEvent) {
        e.preventDefault();
        respond.post(`/w/${wedding.slug}/respond`, {
            preserveScroll: true,
            onSuccess: () => {
                setDone(true);
                toast.success('Thank you! Your reply has been recorded.');
            },
        });
    }

    return (
        <div className="min-h-screen bg-gradient-to-b from-rose-50 via-white to-rose-50 text-stone-800 dark:from-stone-950 dark:via-stone-900 dark:to-stone-950 dark:text-stone-100">
            <Head title={`RSVP — ${wedding.name}`} />

            <div className="mx-auto flex max-w-xl flex-col items-center px-6 py-16">
                <Heart className="size-8 text-rose-400" />
                <p className="mt-6 text-sm tracking-[0.3em] text-rose-400 uppercase">
                    Together with their families
                </p>
                <h1 className="mt-3 text-center font-serif text-4xl leading-tight sm:text-5xl">
                    {wedding.name}
                </h1>
                {wedding.event_date && (
                    <p className="mt-4 text-center text-stone-500 dark:text-stone-400">
                        {dateFormat.format(new Date(wedding.event_date))}
                    </p>
                )}

                <div className="mt-12 w-full rounded-2xl border border-rose-100 bg-white/80 p-8 shadow-sm backdrop-blur dark:border-stone-800 dark:bg-stone-900/70">
                    {done ? (
                        <div className="flex flex-col items-center gap-3 py-6 text-center">
                            <div className="flex size-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <Check className="size-6" />
                            </div>
                            <h2 className="text-xl font-medium">Reply received</h2>
                            <p className="text-sm text-stone-500 dark:text-stone-400">
                                Thank you, {selected?.name}. You can update your response any time.
                            </p>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setDone(false);
                                    setSelected(null);
                                    lookup.reset();
                                }}
                            >
                                Respond for someone else
                            </Button>
                        </div>
                    ) : selected ? (
                        <form onSubmit={submitRespond} className="flex flex-col gap-5">
                            <div>
                                <h2 className="text-lg font-medium">Hello, {selected.name}</h2>
                                <p className="text-sm text-stone-500 dark:text-stone-400">
                                    Will you be joining us?
                                </p>
                            </div>

                            <div className="grid gap-2">
                                {REPLIES.map((r) => {
                                    const active = respond.data.rsvp_status === r.value;

                                    return (
                                        <button
                                            type="button"
                                            key={r.value}
                                            onClick={() => respond.setData('rsvp_status', r.value)}
                                            className={`rounded-lg border px-4 py-3 text-left text-sm transition-colors ${
                                                active
                                                    ? 'border-rose-400 bg-rose-50 font-medium text-rose-700 dark:bg-rose-950/40 dark:text-rose-200'
                                                    : 'border-stone-200 hover:border-rose-200 dark:border-stone-700'
                                            }`}
                                        >
                                            {r.label}
                                        </button>
                                    );
                                })}
                            </div>

                            {respond.data.rsvp_status === 'attending' && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="meal_choice">Meal preference</Label>
                                        <Input
                                            id="meal_choice"
                                            value={respond.data.meal_choice}
                                            onChange={(e) => respond.setData('meal_choice', e.target.value)}
                                            placeholder="Beef, fish, vegetarian…"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="dietary_notes">Dietary notes</Label>
                                        <Textarea
                                            id="dietary_notes"
                                            value={respond.data.dietary_notes}
                                            onChange={(e) => respond.setData('dietary_notes', e.target.value)}
                                            placeholder="Allergies or restrictions"
                                        />
                                    </div>
                                </>
                            )}

                            <div className="flex gap-2">
                                <Button type="submit" disabled={respond.processing} className="flex-1">
                                    {respond.processing && <Spinner />}
                                    Send reply
                                </Button>
                                <Button type="button" variant="ghost" onClick={() => setSelected(null)}>
                                    Back
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <div className="flex flex-col gap-5">
                            <div className="text-center">
                                <h2 className="text-lg font-medium">RSVP</h2>
                                <p className="text-sm text-stone-500 dark:text-stone-400">
                                    Find your name to reply.
                                </p>
                            </div>

                            <form onSubmit={submitLookup} className="flex gap-2">
                                <div className="relative flex-1">
                                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                                    <Input
                                        value={lookup.data.name}
                                        onChange={(e) => lookup.setData('name', e.target.value)}
                                        placeholder="Your name"
                                        className="pl-9"
                                        autoFocus
                                    />
                                </div>
                                <Button type="submit" disabled={lookup.processing}>
                                    {lookup.processing ? <Spinner /> : 'Find'}
                                </Button>
                            </form>

                            {searched && (
                                <div className="flex flex-col gap-2">
                                    {matches.length === 0 ? (
                                        <p className="py-4 text-center text-sm text-stone-500 dark:text-stone-400">
                                            No matches found. Try another spelling, or contact the couple.
                                        </p>
                                    ) : (
                                        matches.map((m) => (
                                            <button
                                                type="button"
                                                key={m.id}
                                                onClick={() => choose(m)}
                                                className="rounded-lg border border-stone-200 px-4 py-3 text-left text-sm transition-colors hover:border-rose-300 hover:bg-rose-50 dark:border-stone-700 dark:hover:bg-stone-800"
                                            >
                                                {m.name}
                                            </button>
                                        ))
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </div>

                <p className="mt-10 text-xs text-stone-400">Powered by WedFlow Atelier</p>
            </div>
        </div>
    );
}

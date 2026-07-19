import { Head, useForm } from '@inertiajs/react';
import { Check, Search } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

type Match = {
    id: number;
    name: string;
    rsvp_status: string;
    meal_choice: string | null;
    appetizer_choice: string | null;
    dessert_choice: string | null;
    dietary_notes: string | null;
    event_rsvps: Record<string, string>;
};

type MealCourse = { course: 'appetizer' | 'main' | 'dessert'; label: string; options: string[] };

type RsvpEvent = { id: number; name: string; type: string; date: string | null; time: string | null; venue_name: string | null };

type PageProps = {
    wedding: { name: string; slug: string; event_date: string | null };
    matches: Match[];
    searched: boolean;
    query: string;
    meals: MealCourse[];
    events: RsvpEvent[];
};

const COURSE_FIELD: Record<string, 'meal_choice' | 'appetizer_choice' | 'dessert_choice'> = {
    appetizer: 'appetizer_choice',
    main: 'meal_choice',
    dessert: 'dessert_choice',
};

const REPLIES: { value: string; label: string }[] = [
    { value: 'attending', label: 'Joyfully accepts' },
    { value: 'maybe', label: 'Maybe' },
    { value: 'declined', label: 'Regretfully declines' },
];

const serif = "font-['Newsreader']";

const dateFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

export default function PublicRsvp({ wedding, matches, searched, query, meals, events }: PageProps) {
    const [selected, setSelected] = useState<Match | null>(null);
    const [done, setDone] = useState(false);

    const lookup = useForm({ name: query ?? '' });
    const respond = useForm<{
        guest_id: number;
        rsvp_status: string;
        meal_choice: string;
        appetizer_choice: string;
        dessert_choice: string;
        dietary_notes: string;
        events: Record<string, string>;
    }>({
        guest_id: 0,
        rsvp_status: 'attending',
        meal_choice: '',
        appetizer_choice: '',
        dessert_choice: '',
        dietary_notes: '',
        events: {},
    });

    function submitLookup(e: React.FormEvent) {
        e.preventDefault();
        setSelected(null);
        setDone(false);
        lookup.get(`/w/${wedding.slug}/rsvp`, {
            preserveScroll: true,
            preserveState: true,
            only: ['matches', 'searched', 'query'],
        });
    }

    function choose(match: Match) {
        setSelected(match);
        setDone(false);
        const eventReplies: Record<string, string> = {};
        events.forEach((ev) => {
            eventReplies[String(ev.id)] = match.event_rsvps?.[String(ev.id)] ?? 'attending';
        });
        respond.setData({
            guest_id: match.id,
            rsvp_status: match.rsvp_status === 'pending' ? 'attending' : match.rsvp_status,
            meal_choice: match.meal_choice ?? '',
            appetizer_choice: match.appetizer_choice ?? '',
            dessert_choice: match.dessert_choice ?? '',
            dietary_notes: match.dietary_notes ?? '',
            events: eventReplies,
        });
    }

    function submitRespond(e: React.FormEvent) {
        e.preventDefault();
        respond.post(`/w/${wedding.slug}/rsvp/respond`, {
            preserveScroll: true,
            onSuccess: () => {
                setDone(true);
                toast.success('Thank you! Your reply has been recorded.');
            },
        });
    }

    const inputClass =
        'w-full border-0 border-b border-[#d5d8d1] bg-transparent px-0 py-3 text-[#12211b] transition-colors placeholder:text-[#47534d]/50 focus:border-[#1b4638] focus:ring-0';

    return (
        <div className="min-h-screen bg-[#f5f4ee] font-['Instrument_Sans'] text-[#12211b] antialiased">
            <Head title={`RSVP — ${wedding.name}`} />

            <div className="mx-auto flex max-w-xl flex-col items-center px-6 py-20">
                <p className="text-xs tracking-[0.3em] text-[#1b4638] uppercase">Together with their families</p>
                <h1 className={`${serif} mt-4 text-center text-4xl leading-tight sm:text-5xl`}>{wedding.name}</h1>
                {wedding.event_date && (
                    <p className="mt-4 text-sm tracking-[0.1em] text-[#47534d] uppercase">
                        {dateFormat.format(new Date(wedding.event_date))}
                    </p>
                )}

                <div className="mt-12 w-full border border-[#d5d8d1]/40 bg-[#fafaf6] p-8 shadow-sm md:p-10">
                    {done ? (
                        <div className="flex flex-col items-center gap-3 py-6 text-center">
                            <div className="flex size-12 items-center justify-center rounded-full bg-[#1b4638] text-white">
                                <Check className="size-6" />
                            </div>
                            <h2 className={`${serif} text-2xl`}>Reply received</h2>
                            <p className="text-sm text-[#47534d]">
                                Thank you, {selected?.name}. You can update your response any time.
                            </p>
                            <button
                                onClick={() => {
                                    setDone(false);
                                    setSelected(null);
                                    lookup.reset();
                                }}
                                className="mt-2 border border-[#12211b] px-6 py-2.5 text-xs tracking-widest text-[#12211b] uppercase transition-colors hover:bg-[#12211b] hover:text-white"
                            >
                                Respond for someone else
                            </button>
                        </div>
                    ) : selected ? (
                        <form onSubmit={submitRespond} className="flex flex-col gap-6">
                            <div>
                                <h2 className={`${serif} text-2xl`}>Hello, {selected.name}</h2>
                                <p className="text-sm text-[#47534d]">Will you be joining us?</p>
                            </div>

                            <div className="grid gap-2">
                                {REPLIES.map((r) => {
                                    const active = respond.data.rsvp_status === r.value;

                                    return (
                                        <button
                                            type="button"
                                            key={r.value}
                                            onClick={() => respond.setData('rsvp_status', r.value)}
                                            className={`border px-4 py-3 text-left text-sm tracking-wide transition-colors ${
                                                active
                                                    ? 'border-[#1b4638] bg-[#a8d5c2]/20 font-medium text-[#12211b]'
                                                    : 'border-[#d5d8d1]/60 hover:border-[#1b4638]/50'
                                            }`}
                                        >
                                            {r.label}
                                        </button>
                                    );
                                })}
                            </div>

                            {respond.data.rsvp_status === 'attending' && (
                                <>
                                    {meals.map((m) => {
                                        const field = COURSE_FIELD[m.course];
                                        return (
                                            <div key={m.course} className="grid gap-2">
                                                <label className="text-xs tracking-widest text-[#47534d] uppercase">
                                                    {m.label}
                                                </label>
                                                <select
                                                    value={respond.data[field]}
                                                    onChange={(e) => respond.setData(field, e.target.value)}
                                                    className={inputClass}
                                                >
                                                    <option value="">Please choose…</option>
                                                    {m.options.map((o) => <option key={o} value={o}>{o}</option>)}
                                                </select>
                                            </div>
                                        );
                                    })}
                                    <div className="grid gap-2">
                                        <label className="text-xs tracking-widest text-[#47534d] uppercase">
                                            Dietary notes
                                        </label>
                                        <textarea
                                            value={respond.data.dietary_notes}
                                            onChange={(e) => respond.setData('dietary_notes', e.target.value)}
                                            placeholder="Allergies or restrictions"
                                            rows={3}
                                            className={`${inputClass} resize-none`}
                                        />
                                    </div>
                                </>
                            )}

                            {events.length > 0 && (
                                <div className="grid gap-3 border-t border-[#d5d8d1]/40 pt-5">
                                    <p className="text-xs tracking-widest text-[#47534d] uppercase">
                                        Which celebrations will you join?
                                    </p>
                                    {events.map((ev) => {
                                        const status = respond.data.events[String(ev.id)] ?? 'attending';
                                        const detail = [ev.date, ev.time, ev.venue_name].filter(Boolean).join(' · ');
                                        return (
                                            <div key={ev.id} className="border border-[#d5d8d1]/60 p-3">
                                                <p className="text-sm font-medium text-[#12211b]">{ev.name}</p>
                                                {detail && <p className="text-xs text-[#47534d]">{detail}</p>}
                                                <div className="mt-2 flex gap-2">
                                                    {[{ v: 'attending', l: 'Attending' }, { v: 'declined', l: "Can't make it" }].map((o) => (
                                                        <button
                                                            type="button"
                                                            key={o.v}
                                                            onClick={() => respond.setData('events', { ...respond.data.events, [String(ev.id)]: o.v })}
                                                            className={`flex-1 border px-3 py-2 text-xs tracking-wide uppercase transition-colors ${
                                                                status === o.v
                                                                    ? 'border-[#1b4638] bg-[#a8d5c2]/20 font-medium text-[#12211b]'
                                                                    : 'border-[#d5d8d1]/60 hover:border-[#1b4638]/50'
                                                            }`}
                                                        >
                                                            {o.l}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            <div className="flex gap-3">
                                <button
                                    type="submit"
                                    disabled={respond.processing}
                                    className="flex-1 bg-[#12211b] px-6 py-3.5 text-xs tracking-[0.2em] text-white uppercase transition-opacity hover:opacity-85 disabled:opacity-50"
                                >
                                    Send reply
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setSelected(null)}
                                    className="px-4 text-xs tracking-widest text-[#47534d] uppercase hover:text-[#12211b]"
                                >
                                    Back
                                </button>
                            </div>
                        </form>
                    ) : (
                        <div className="flex flex-col gap-6">
                            <div className="text-center">
                                <h2 className={`${serif} text-2xl`}>Kindly Respond</h2>
                                <p className="text-sm text-[#47534d]">Find your name to reply.</p>
                            </div>

                            <form onSubmit={submitLookup} className="flex gap-3">
                                <div className="relative flex-1">
                                    <Search className="absolute top-1/2 left-0 size-4 -translate-y-1/2 text-[#1b4638]" />
                                    <input
                                        value={lookup.data.name}
                                        onChange={(e) => lookup.setData('name', e.target.value)}
                                        placeholder="Your name"
                                        className={`${inputClass} pl-6`}
                                        autoFocus
                                    />
                                </div>
                                <button
                                    type="submit"
                                    disabled={lookup.processing}
                                    className="bg-[#12211b] px-6 text-xs tracking-widest text-white uppercase transition-opacity hover:opacity-85 disabled:opacity-50"
                                >
                                    Find
                                </button>
                            </form>

                            {searched && (
                                <div className="flex flex-col gap-2">
                                    {matches.length === 0 ? (
                                        <p className="py-4 text-center text-sm text-[#47534d]">
                                            No matches found. Try another spelling, or contact the couple.
                                        </p>
                                    ) : (
                                        matches.map((m) => (
                                            <button
                                                type="button"
                                                key={m.id}
                                                onClick={() => choose(m)}
                                                className="border border-[#d5d8d1]/60 px-4 py-3 text-left text-sm transition-colors hover:border-[#1b4638] hover:bg-[#a8d5c2]/10"
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

                <p className="mt-10 text-xs tracking-[0.15em] text-[#47534d]/60 uppercase">Made with VowNook</p>
            </div>
        </div>
    );
}

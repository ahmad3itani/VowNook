import { Head, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Armchair, Search, Users } from 'lucide-react';

type Match = {
    id: number;
    name: string;
    table: string | null;
    tablemates: string[];
};

type PageProps = {
    wedding: { name: string; slug: string; event_date: string | null };
    matches: Match[];
    searched: boolean;
    query: string;
};

const serif = "font-['Playfair_Display']";

const dateFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

export default function PublicSeats({ wedding, matches, searched, query }: PageProps) {
    const lookup = useForm({ name: query ?? '' });

    function submitLookup(e: React.FormEvent) {
        e.preventDefault();
        lookup.get(`/w/${wedding.slug}/seats`, {
            preserveScroll: true,
            preserveState: true,
            only: ['matches', 'searched', 'query'],
        });
    }

    return (
        <div className="relative min-h-screen overflow-hidden bg-[#fff8f3] font-['DM_Sans'] text-[#1e1b17] antialiased">
            <Head title={`Find your seat — ${wedding.name}`} />

            <div
                className="pointer-events-none absolute inset-0 bg-cover bg-center opacity-[0.08] grayscale"
                style={{ backgroundImage: "url('/images/wedding/reception.jpg')" }}
            />

            <div className="relative mx-auto flex max-w-xl flex-col items-center px-6 py-20">
                <p className="text-xs tracking-[0.3em] text-[#775a19] uppercase">Welcome to the celebration</p>
                <h1 className={`${serif} mt-4 text-center text-4xl leading-tight sm:text-5xl`}>{wedding.name}</h1>
                {wedding.event_date && (
                    <p className="mt-4 text-sm tracking-[0.1em] text-[#4c4640] uppercase">
                        {dateFormat.format(new Date(wedding.event_date))}
                    </p>
                )}

                <motion.div
                    className="mt-12 w-full border border-[#cec5bd]/40 bg-[#fffdf9]/90 p-8 shadow-sm backdrop-blur md:p-10"
                    initial={{ opacity: 0, y: 24 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
                >
                    <div className="text-center">
                        <h2 className={`${serif} text-2xl`}>Find your seat</h2>
                        <p className="text-sm text-[#4c4640]">Enter your name to see your table.</p>
                    </div>

                    <form onSubmit={submitLookup} className="mt-6 flex gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-0 size-4 -translate-y-1/2 text-[#775a19]" />
                            <input
                                value={lookup.data.name}
                                onChange={(e) => lookup.setData('name', e.target.value)}
                                placeholder="Your name"
                                autoFocus
                                className="w-full border-0 border-b border-[#cec5bd] bg-transparent px-0 py-3 pl-6 text-[#1e1b17] transition-colors placeholder:text-[#4c4640]/50 focus:border-[#775a19] focus:ring-0"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={lookup.processing}
                            className="bg-[#1e1b18] px-6 text-xs tracking-widest text-white uppercase transition-opacity hover:opacity-85 disabled:opacity-50"
                        >
                            Find
                        </button>
                    </form>

                    {searched && (
                        <div className="mt-6 flex flex-col gap-3">
                            {matches.length === 0 ? (
                                <p className="py-4 text-center text-sm text-[#4c4640]">
                                    No seat found under that name yet. Try another spelling, or ask a member of the
                                    wedding party.
                                </p>
                            ) : (
                                matches.map((m) => (
                                    <div key={m.id} className="border border-[#cec5bd]/50 bg-[#faf2ec] p-5">
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-10 items-center justify-center rounded-full bg-[#fed488]/40 text-[#775a19]">
                                                <Armchair className="size-5" />
                                            </div>
                                            <div>
                                                <p className="text-sm text-[#4c4640]">{m.name}, you are seated at</p>
                                                <p className={`${serif} text-xl`}>{m.table}</p>
                                            </div>
                                        </div>

                                        {m.tablemates.length > 0 && (
                                            <div className="mt-4 border-t border-[#cec5bd]/50 pt-4">
                                                <p className="flex items-center gap-1.5 text-xs tracking-wide text-[#4c4640] uppercase">
                                                    <Users className="size-3.5" />
                                                    Sharing your table
                                                </p>
                                                <div className="mt-2 flex flex-wrap gap-2">
                                                    {m.tablemates.map((name) => (
                                                        <span
                                                            key={name}
                                                            className="border border-[#cec5bd]/50 bg-[#fffdf9] px-3 py-1 text-sm text-[#4c4640]"
                                                        >
                                                            {name}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>
                    )}
                </motion.div>

                <p className="mt-10 text-xs tracking-[0.15em] text-[#4c4640]/60 uppercase">Made with VowNook</p>
            </div>
        </div>
    );
}

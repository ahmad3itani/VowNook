import { Head, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Armchair, Heart, Search, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

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
        <div className="relative min-h-screen overflow-hidden bg-gradient-to-b from-rose-50 via-white to-rose-50 text-stone-800 dark:from-stone-950 dark:via-stone-900 dark:to-stone-950 dark:text-stone-100">
            <Head title={`Find your seat — ${wedding.name}`} />

            <div
                className="pointer-events-none absolute inset-0 bg-cover bg-center opacity-10"
                style={{ backgroundImage: "url('/images/wedding/reception.jpg')" }}
            />

            <div className="relative mx-auto flex max-w-xl flex-col items-center px-6 py-16">
                <Heart className="size-8 text-rose-400" />
                <p className="mt-6 text-sm tracking-[0.3em] text-rose-400 uppercase">
                    Welcome to the celebration
                </p>
                <h1 className="mt-3 text-center font-serif text-4xl leading-tight sm:text-5xl">
                    {wedding.name}
                </h1>
                {wedding.event_date && (
                    <p className="mt-4 text-center text-stone-500 dark:text-stone-400">
                        {dateFormat.format(new Date(wedding.event_date))}
                    </p>
                )}

                <motion.div
                    className="mt-12 w-full rounded-2xl border border-rose-100 bg-white/80 p-8 shadow-sm backdrop-blur dark:border-stone-800 dark:bg-stone-900/70"
                    initial={{ opacity: 0, y: 24 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
                >
                    <div className="text-center">
                        <h2 className="text-lg font-medium">Find your seat</h2>
                        <p className="text-sm text-stone-500 dark:text-stone-400">
                            Enter your name to see your table.
                        </p>
                    </div>

                    <form onSubmit={submitLookup} className="mt-5 flex gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                            <Input
                                value={lookup.data.name}
                                onChange={(e) =>
                                    lookup.setData('name', e.target.value)
                                }
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
                        <div className="mt-5 flex flex-col gap-3">
                            {matches.length === 0 ? (
                                <p className="py-4 text-center text-sm text-stone-500 dark:text-stone-400">
                                    No seat found under that name yet. Try
                                    another spelling, or ask a member of the
                                    wedding party.
                                </p>
                            ) : (
                                matches.map((m) => (
                                    <div
                                        key={m.id}
                                        className="rounded-xl border border-rose-100 bg-rose-50/60 p-5 dark:border-stone-700 dark:bg-stone-800/60"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-10 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                                                <Armchair className="size-5" />
                                            </div>
                                            <div>
                                                <p className="text-sm text-stone-500 dark:text-stone-400">
                                                    {m.name}, you are seated at
                                                </p>
                                                <p className="font-serif text-xl">
                                                    {m.table}
                                                </p>
                                            </div>
                                        </div>

                                        {m.tablemates.length > 0 && (
                                            <div className="mt-4 border-t border-rose-100 pt-4 dark:border-stone-700">
                                                <p className="flex items-center gap-1.5 text-xs tracking-wide text-stone-500 uppercase dark:text-stone-400">
                                                    <Users className="size-3.5" />
                                                    Sharing your table
                                                </p>
                                                <div className="mt-2 flex flex-wrap gap-2">
                                                    {m.tablemates.map(
                                                        (name) => (
                                                            <span
                                                                key={name}
                                                                className="rounded-full bg-white px-3 py-1 text-sm text-stone-600 shadow-sm dark:bg-stone-900 dark:text-stone-300"
                                                            >
                                                                {name}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>
                    )}
                </motion.div>

                <p className="mt-10 text-xs text-stone-400">
                    Powered by WedFlow Atelier
                </p>
            </div>
        </div>
    );
}

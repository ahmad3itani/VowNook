import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Armchair,
    Briefcase,
    CalendarClock,
    Heart,
    ListChecks,
    QrCode,
    Users,
    Wallet,
} from 'lucide-react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';

const features = [
    {
        icon: Users,
        title: 'Guests & RSVP',
        body: 'Track invitations, households, meal choices, and dietary needs — with a public RSVP site your guests will love.',
    },
    {
        icon: Wallet,
        title: 'Budget',
        body: 'Set estimates, log actuals, and watch what is paid versus owed across every category.',
    },
    {
        icon: Briefcase,
        title: 'Vendors',
        body: 'Keep contracts, contacts, and payments for every vendor in one organised place.',
    },
    {
        icon: ListChecks,
        title: 'Checklist',
        body: 'A living to-do list with categories, priorities, due dates, and assignments for your crew.',
    },
    {
        icon: CalendarClock,
        title: 'Timeline',
        body: 'Build the day-of run-of-show and export it straight to everyone’s calendar.',
    },
    {
        icon: Armchair,
        title: 'Seating chart',
        body: 'Drag-and-drop tables and guests, then let attendees find their seat by name.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Plan your wedding, beautifully" />

            <div className="min-h-screen bg-gradient-to-b from-rose-50 via-white to-white text-stone-800 dark:from-stone-950 dark:via-stone-900 dark:to-stone-950 dark:text-stone-100">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                    <span className="flex items-center gap-2 font-serif text-xl">
                        <Heart className="size-5 text-rose-400" />
                        WedFlow Atelier
                    </span>
                    <nav className="flex items-center gap-2 text-sm">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="rounded-full bg-rose-500 px-5 py-2 font-medium text-white transition-colors hover:bg-rose-600"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="rounded-full px-5 py-2 font-medium text-stone-600 transition-colors hover:text-stone-900 dark:text-stone-300 dark:hover:text-white"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="rounded-full bg-rose-500 px-5 py-2 font-medium text-white transition-colors hover:bg-rose-600"
                                >
                                    Get started
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                <main>
                    {/* Hero */}
                    <section className="mx-auto max-w-3xl px-6 pt-16 pb-20 text-center sm:pt-24">
                        <p className="text-sm tracking-[0.3em] text-rose-400 uppercase">
                            The all-in-one wedding planner
                        </p>
                        <h1 className="mt-6 font-serif text-5xl leading-[1.1] sm:text-6xl">
                            Plan your wedding,
                            <span className="block text-rose-500">
                                beautifully.
                            </span>
                        </h1>
                        <p className="mx-auto mt-6 max-w-xl text-lg text-stone-500 dark:text-stone-400">
                            Guests, budget, vendors, timeline, and seating —
                            every detail of your celebration in one calm,
                            elegant workspace you can share with the people who
                            matter.
                        </p>
                        <div className="mt-10 flex flex-wrap items-center justify-center gap-3">
                            <Link
                                href={auth.user ? dashboard() : login()}
                                className="inline-flex items-center gap-2 rounded-full bg-rose-500 px-7 py-3 font-medium text-white transition-colors hover:bg-rose-600"
                            >
                                {auth.user
                                    ? 'Open your dashboard'
                                    : 'Start planning'}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </section>

                    {/* Showcase image */}
                    <section className="mx-auto max-w-5xl px-6">
                        <Reveal className="overflow-hidden rounded-3xl shadow-xl">
                            <img
                                src="/images/wedding/venue.jpg"
                                alt="An elegant wedding reception"
                                className="aspect-[16/9] w-full object-cover"
                                loading="lazy"
                            />
                        </Reveal>
                    </section>

                    {/* Features */}
                    <section className="mx-auto max-w-6xl px-6 py-20">
                        <Stagger className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((f) => (
                                <StaggerItem
                                    key={f.title}
                                    className="rounded-2xl border border-rose-100/70 bg-white/70 p-6 backdrop-blur transition-shadow hover:shadow-md dark:border-stone-800 dark:bg-stone-900/60"
                                >
                                    <div className="flex size-11 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                                        <f.icon className="size-5" />
                                    </div>
                                    <h3 className="mt-4 font-serif text-xl">
                                        {f.title}
                                    </h3>
                                    <p className="mt-2 text-sm text-stone-500 dark:text-stone-400">
                                        {f.body}
                                    </p>
                                </StaggerItem>
                            ))}
                        </Stagger>
                    </section>

                    {/* Guest-facing highlight */}
                    <section className="mx-auto max-w-6xl px-6 py-16">
                        <div className="grid items-center gap-10 rounded-3xl bg-rose-500 p-10 text-white sm:p-14 lg:grid-cols-2">
                            <div>
                                <h2 className="font-serif text-3xl sm:text-4xl">
                                    Delight your guests, too
                                </h2>
                                <p className="mt-4 text-rose-50">
                                    Share a beautiful RSVP page and a QR seat
                                    finder so guests can reply and find their
                                    table in seconds — no apps, no logins.
                                </p>
                            </div>
                            <div className="flex justify-center gap-4">
                                <div className="flex flex-col items-center gap-3 rounded-2xl bg-white/10 p-6 text-center backdrop-blur">
                                    <Heart className="size-8" />
                                    <span className="text-sm font-medium">
                                        Online RSVP
                                    </span>
                                </div>
                                <div className="flex flex-col items-center gap-3 rounded-2xl bg-white/10 p-6 text-center backdrop-blur">
                                    <QrCode className="size-8" />
                                    <span className="text-sm font-medium">
                                        QR seat finder
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Closing CTA */}
                    <section className="mx-auto max-w-3xl px-6 py-20 text-center">
                        <h2 className="font-serif text-4xl">
                            Your big day, beautifully organised.
                        </h2>
                        <p className="mx-auto mt-4 max-w-lg text-stone-500 dark:text-stone-400">
                            Bring every detail together and enjoy the journey to
                            your wedding day.
                        </p>
                        <div className="mt-8">
                            <Link
                                href={auth.user ? dashboard() : login()}
                                className="inline-flex items-center gap-2 rounded-full bg-rose-500 px-7 py-3 font-medium text-white transition-colors hover:bg-rose-600"
                            >
                                {auth.user
                                    ? 'Open your dashboard'
                                    : 'Start planning'}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-rose-100/70 py-8 text-center text-sm text-stone-400 dark:border-stone-800">
                    Powered by WedFlow Atelier
                </footer>
            </div>
        </>
    );
}

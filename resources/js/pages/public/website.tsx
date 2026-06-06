import { Head, Link } from '@inertiajs/react';
import { Armchair, CalendarHeart, Clock, Heart, MapPin, Shirt } from 'lucide-react';

type Content = {
    headline: string | null;
    welcome_message: string | null;
    our_story: string | null;
    venue_name: string | null;
    venue_address: string | null;
    ceremony_time: string | null;
    dress_code: string | null;
    hero_image_url: string | null;
};

type PageProps = {
    wedding: { name: string; slug: string; event_date: string | null };
    published: boolean;
    content: Content | null;
};

const dateFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

export default function PublicWebsite({ wedding, published, content }: PageProps) {
    const eventDate = wedding.event_date ? dateFormat.format(new Date(wedding.event_date)) : null;

    return (
        <div className="min-h-screen bg-gradient-to-b from-rose-50 via-white to-rose-50 text-stone-800 dark:from-stone-950 dark:via-stone-900 dark:to-stone-950 dark:text-stone-100">
            <Head title={wedding.name} />

            {/* Hero */}
            <section className="relative flex min-h-[60vh] flex-col items-center justify-center overflow-hidden px-6 py-20 text-center">
                {content?.hero_image_url && (
                    <>
                        <img
                            src={content.hero_image_url}
                            alt=""
                            className="absolute inset-0 size-full object-cover"
                        />
                        <div className="absolute inset-0 bg-black/40" />
                    </>
                )}
                <div className={content?.hero_image_url ? 'relative text-white' : 'relative'}>
                    <Heart
                        className={`mx-auto size-8 ${content?.hero_image_url ? 'text-white' : 'text-rose-400'}`}
                    />
                    {content?.headline && (
                        <p className="mt-6 text-sm tracking-[0.3em] uppercase opacity-90">
                            {content.headline}
                        </p>
                    )}
                    <h1 className="mt-3 font-serif text-5xl leading-tight sm:text-6xl">
                        {wedding.name}
                    </h1>
                    {eventDate && <p className="mt-4 text-lg opacity-90">{eventDate}</p>}

                    <div className="mt-10 flex flex-wrap items-center justify-center gap-3">
                        <Link
                            href={`/w/${wedding.slug}/rsvp`}
                            className="rounded-full bg-rose-500 px-7 py-3 font-medium text-white transition-colors hover:bg-rose-600"
                        >
                            RSVP
                        </Link>
                        <Link
                            href={`/w/${wedding.slug}/seats`}
                            className={`inline-flex items-center gap-2 rounded-full border px-7 py-3 font-medium transition-colors ${
                                content?.hero_image_url
                                    ? 'border-white/60 text-white hover:bg-white/10'
                                    : 'border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-stone-700 dark:text-rose-300 dark:hover:bg-stone-900'
                            }`}
                        >
                            <Armchair className="size-4" />
                            Find your seat
                        </Link>
                    </div>
                </div>
            </section>

            <div className="mx-auto max-w-2xl px-6 pb-20">
                {published && content ? (
                    <div className="flex flex-col gap-12">
                        {content.welcome_message && (
                            <p className="text-center text-xl leading-relaxed text-stone-600 dark:text-stone-300">
                                {content.welcome_message}
                            </p>
                        )}

                        {content.our_story && (
                            <section className="text-center">
                                <h2 className="font-serif text-3xl">Our Story</h2>
                                <p className="mt-4 leading-relaxed whitespace-pre-line text-stone-600 dark:text-stone-300">
                                    {content.our_story}
                                </p>
                            </section>
                        )}

                        {(content.venue_name || content.ceremony_time || content.dress_code) && (
                            <section className="grid gap-4 sm:grid-cols-3">
                                {content.venue_name && (
                                    <Detail icon={MapPin} label="Venue">
                                        {content.venue_name}
                                        {content.venue_address && (
                                            <span className="mt-1 block text-sm opacity-80">
                                                {content.venue_address}
                                            </span>
                                        )}
                                    </Detail>
                                )}
                                {content.ceremony_time && (
                                    <Detail icon={Clock} label="Ceremony">
                                        {content.ceremony_time}
                                    </Detail>
                                )}
                                {content.dress_code && (
                                    <Detail icon={Shirt} label="Dress code">
                                        {content.dress_code}
                                    </Detail>
                                )}
                            </section>
                        )}
                    </div>
                ) : (
                    <div className="flex flex-col items-center gap-3 text-center text-stone-500 dark:text-stone-400">
                        <CalendarHeart className="size-8 text-rose-300" />
                        <p>More details are on the way — in the meantime, you can RSVP above.</p>
                    </div>
                )}
            </div>

            <footer className="border-t border-rose-100/70 py-8 text-center text-sm text-stone-400 dark:border-stone-800">
                Powered by WedFlow Atelier
            </footer>
        </div>
    );
}

function Detail({
    icon: Icon,
    label,
    children,
}: {
    icon: typeof MapPin;
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="rounded-2xl border border-rose-100/70 bg-white/70 p-6 text-center backdrop-blur dark:border-stone-800 dark:bg-stone-900/60">
            <Icon className="mx-auto size-5 text-rose-400" />
            <div className="mt-3 text-xs tracking-wide text-stone-400 uppercase">{label}</div>
            <div className="mt-1 font-medium">{children}</div>
        </div>
    );
}

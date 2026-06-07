import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Armchair, ChevronDown, Clock, Heart, MapPin, Shirt } from 'lucide-react';
import { Countdown } from '@/components/countdown';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';

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

const IMG = {
    hero: '/images/wedding/hero.jpg',
    story: '/images/wedding/story.jpg',
    celebration: '/images/wedding/celebration.jpg',
};

const GALLERY = [
    { src: '/images/wedding/venue.jpg', label: 'The venue' },
    { src: '/images/wedding/reception.jpg', label: 'Reception' },
    { src: '/images/wedding/table-setting.jpg', label: 'The details' },
    { src: '/images/wedding/florals.jpg', label: 'Florals' },
    { src: '/images/wedding/dinner.jpg', label: 'Dinner' },
    { src: '/images/wedding/ceremony.jpg', label: 'Ceremony' },
];

const dateFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

const heroText = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.18, delayChildren: 0.2 } },
};
const heroItem = {
    hidden: { opacity: 0, y: 30 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.9, ease: [0.22, 1, 0.36, 1] as const } },
};

export default function PublicWebsite({ wedding, published, content }: PageProps) {
    const eventDate = wedding.event_date ? dateFormat.format(new Date(wedding.event_date)) : null;
    const heroImage = content?.hero_image_url || IMG.hero;

    return (
        <div className="bg-white text-stone-800 dark:bg-stone-950 dark:text-stone-100">
            <Head title={wedding.name} />

            {/* Hero */}
            <section className="relative flex h-screen min-h-[600px] items-center justify-center overflow-hidden">
                <motion.div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{ backgroundImage: `url(${heroImage})` }}
                    initial={{ scale: 1.18 }}
                    animate={{ scale: 1 }}
                    transition={{ duration: 12, ease: 'easeOut' }}
                />
                <div className="absolute inset-0 bg-gradient-to-b from-black/40 via-black/30 to-black/60" />

                <motion.div
                    className="relative z-10 px-6 text-center text-white"
                    variants={heroText}
                    initial="hidden"
                    animate="visible"
                >
                    <motion.div variants={heroItem}>
                        <Heart className="mx-auto size-9" />
                    </motion.div>
                    <motion.p
                        variants={heroItem}
                        className="mt-6 text-sm tracking-[0.4em] uppercase opacity-90"
                    >
                        {content?.headline || 'Together with their families'}
                    </motion.p>
                    <motion.h1
                        variants={heroItem}
                        className="mt-4 font-serif text-6xl leading-[1.05] sm:text-7xl md:text-8xl"
                    >
                        {wedding.name}
                    </motion.h1>
                    {eventDate && (
                        <motion.p variants={heroItem} className="mt-6 text-lg tracking-wide opacity-90">
                            {eventDate}
                        </motion.p>
                    )}
                    {wedding.event_date && (
                        <motion.div variants={heroItem} className="mt-10">
                            <Countdown date={wedding.event_date} light />
                        </motion.div>
                    )}
                    <motion.div variants={heroItem} className="mt-12 flex flex-wrap justify-center gap-3">
                        <Link
                            href={`/w/${wedding.slug}/rsvp`}
                            className="rounded-full bg-rose-500 px-8 py-3 font-medium text-white shadow-lg transition-colors hover:bg-rose-600"
                        >
                            RSVP
                        </Link>
                        <Link
                            href={`/w/${wedding.slug}/seats`}
                            className="inline-flex items-center gap-2 rounded-full border border-white/60 px-8 py-3 font-medium text-white backdrop-blur transition-colors hover:bg-white/10"
                        >
                            <Armchair className="size-4" />
                            Find your seat
                        </Link>
                    </motion.div>
                </motion.div>

                <motion.div
                    className="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 text-white/80"
                    animate={{ y: [0, 10, 0] }}
                    transition={{ duration: 1.8, repeat: Infinity, ease: 'easeInOut' }}
                >
                    <ChevronDown className="size-7" />
                </motion.div>
            </section>

            {/* Welcome */}
            {published && content?.welcome_message && (
                <section className="mx-auto max-w-2xl px-6 py-24 text-center">
                    <Reveal>
                        <Heart className="mx-auto size-6 text-rose-400" />
                        <p className="mt-6 font-serif text-2xl leading-relaxed text-stone-600 sm:text-3xl dark:text-stone-300">
                            {content.welcome_message}
                        </p>
                    </Reveal>
                </section>
            )}

            {/* Our story */}
            {published && content?.our_story && (
                <section className="mx-auto grid max-w-5xl items-center gap-10 px-6 py-16 md:grid-cols-2">
                    <Reveal className="overflow-hidden rounded-3xl">
                        <img
                            src={IMG.story}
                            alt="Our story"
                            className="aspect-[4/5] w-full object-cover"
                            loading="lazy"
                        />
                    </Reveal>
                    <Reveal delay={0.15}>
                        <p className="text-sm tracking-[0.3em] text-rose-400 uppercase">Our story</p>
                        <h2 className="mt-3 font-serif text-4xl">How it began</h2>
                        <p className="mt-5 leading-relaxed whitespace-pre-line text-stone-600 dark:text-stone-300">
                            {content.our_story}
                        </p>
                    </Reveal>
                </section>
            )}

            {/* Details */}
            {published && (content?.venue_name || content?.ceremony_time || content?.dress_code) && (
                <section className="mx-auto max-w-5xl px-6 py-20">
                    <Reveal className="mb-12 text-center">
                        <h2 className="font-serif text-4xl">The Details</h2>
                    </Reveal>
                    <Stagger className="grid gap-6 sm:grid-cols-3">
                        {content.venue_name && (
                            <StaggerItem className="rounded-2xl border border-rose-100/70 bg-rose-50/40 p-8 text-center dark:border-stone-800 dark:bg-stone-900/50">
                                <MapPin className="mx-auto size-6 text-rose-400" />
                                <h3 className="mt-4 font-serif text-xl">Venue</h3>
                                <p className="mt-2 text-stone-600 dark:text-stone-300">{content.venue_name}</p>
                                {content.venue_address && (
                                    <p className="mt-1 text-sm text-stone-400">{content.venue_address}</p>
                                )}
                            </StaggerItem>
                        )}
                        {content.ceremony_time && (
                            <StaggerItem className="rounded-2xl border border-rose-100/70 bg-rose-50/40 p-8 text-center dark:border-stone-800 dark:bg-stone-900/50">
                                <Clock className="mx-auto size-6 text-rose-400" />
                                <h3 className="mt-4 font-serif text-xl">Ceremony</h3>
                                <p className="mt-2 text-stone-600 dark:text-stone-300">{content.ceremony_time}</p>
                            </StaggerItem>
                        )}
                        {content.dress_code && (
                            <StaggerItem className="rounded-2xl border border-rose-100/70 bg-rose-50/40 p-8 text-center dark:border-stone-800 dark:bg-stone-900/50">
                                <Shirt className="mx-auto size-6 text-rose-400" />
                                <h3 className="mt-4 font-serif text-xl">Dress code</h3>
                                <p className="mt-2 text-stone-600 dark:text-stone-300">{content.dress_code}</p>
                            </StaggerItem>
                        )}
                    </Stagger>
                </section>
            )}

            {/* Gallery */}
            <section className="mx-auto max-w-6xl px-6 py-20">
                <Reveal className="mb-12 text-center">
                    <p className="text-sm tracking-[0.3em] text-rose-400 uppercase">A glimpse</p>
                    <h2 className="mt-3 font-serif text-4xl">Moments to come</h2>
                </Reveal>
                <Stagger className="grid grid-cols-2 gap-4 md:grid-cols-3">
                    {GALLERY.map((g) => (
                        <StaggerItem
                            key={g.src}
                            className="group relative overflow-hidden rounded-2xl"
                        >
                            <img
                                src={g.src}
                                alt={g.label}
                                className="aspect-square w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                loading="lazy"
                            />
                            <div className="absolute inset-0 flex items-end bg-gradient-to-t from-black/50 to-transparent p-4 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                <span className="font-serif text-lg text-white">{g.label}</span>
                            </div>
                        </StaggerItem>
                    ))}
                </Stagger>
            </section>

            {/* CTA band */}
            <section className="relative overflow-hidden">
                <div
                    className="absolute inset-0 bg-cover bg-fixed bg-center"
                    style={{ backgroundImage: `url(${IMG.celebration})` }}
                />
                <div className="absolute inset-0 bg-rose-950/70" />
                <div className="relative mx-auto max-w-2xl px-6 py-28 text-center text-white">
                    <Reveal>
                        <h2 className="font-serif text-4xl sm:text-5xl">We can't wait to celebrate</h2>
                        <p className="mx-auto mt-4 max-w-md text-rose-50">
                            {published
                                ? 'Let us know if you can make it — we would love to have you there.'
                                : 'More details are on the way. In the meantime, you can let us know if you’ll be there.'}
                        </p>
                        <Link
                            href={`/w/${wedding.slug}/rsvp`}
                            className="mt-8 inline-block rounded-full bg-white px-8 py-3 font-medium text-rose-600 transition-transform hover:scale-105"
                        >
                            RSVP now
                        </Link>
                    </Reveal>
                </div>
            </section>

            <footer className="bg-white py-8 text-center text-sm text-stone-400 dark:bg-stone-950">
                Powered by WedFlow Atelier
            </footer>
        </div>
    );
}

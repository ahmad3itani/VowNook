import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
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
};

const GALLERY = [
    { src: '/images/wedding/venue.jpg', label: 'The venue' },
    { src: '/images/wedding/reception.jpg', label: 'Reception' },
    { src: '/images/wedding/table-setting.jpg', label: 'The details' },
    { src: '/images/wedding/florals.jpg', label: 'Florals' },
    { src: '/images/wedding/dinner.jpg', label: 'Dinner' },
    { src: '/images/wedding/ceremony.jpg', label: 'Ceremony' },
];

const serif = "font-['Playfair_Display']";

const longDate = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});
const shortDate = new Intl.DateTimeFormat('en-CA', { month: 'long', day: 'numeric', year: 'numeric' });

/** Render "Amelia & Julian" with the ampersand set in italic Playfair. */
function CoupleName({ name, className }: { name: string; className?: string }) {
    const parts = name.split(/\s*&\s*/);

    if (parts.length < 2) {
        return <span className={className}>{name}</span>;
    }

    return (
        <span className={className}>
            {parts[0]} <span className="italic font-normal text-[#775a19]">&amp;</span> {parts.slice(1).join(' & ')}
        </span>
    );
}

function useCountdown(date: string | null) {
    const [parts, setParts] = useState({ days: 0, hours: 0, minutes: 0 });
    useEffect(() => {
        if (!date) {
return;
}

        const tick = () => {
            const ms = new Date(date).getTime() - Date.now();

            if (ms <= 0) {
                setParts({ days: 0, hours: 0, minutes: 0 });

                return;
            }

            const s = Math.floor(ms / 1000);
            setParts({
                days: Math.floor(s / 86400),
                hours: Math.floor((s % 86400) / 3600),
                minutes: Math.floor((s % 3600) / 60),
            });
        };
        tick();
        const id = setInterval(tick, 1000 * 30);

        return () => clearInterval(id);
    }, [date]);

    return parts;
}

export default function PublicWebsite({ wedding, published, content }: PageProps) {
    const heroImage = content?.hero_image_url || IMG.hero;
    const eventLong = wedding.event_date ? longDate.format(new Date(wedding.event_date)) : null;
    const eventShort = wedding.event_date ? shortDate.format(new Date(wedding.event_date)) : null;
    const cd = useCountdown(wedding.event_date);

    return (
        <div className="min-h-screen bg-[#fff8f3] font-['DM_Sans'] text-[#1e1b17] antialiased">
            <Head title={wedding.name} />

            {/* Header */}
            <header className="fixed inset-x-0 top-0 z-50 border-b border-[#cec5bd]/30 bg-[#fff8f3]/80 backdrop-blur-md">
                <nav className="mx-auto flex max-w-[1440px] items-center justify-between px-5 py-5 md:px-16">
                    <span className={`${serif} text-2xl tracking-tight`}>
                        <CoupleName name={wedding.name} />
                    </span>
                    <div className="hidden items-center gap-10 md:flex">
                        <a href="#story" className="text-sm tracking-wide text-[#4c4640] transition-colors hover:text-[#775a19]">
                            The Story
                        </a>
                        <a href="#details" className="text-sm tracking-wide text-[#4c4640] transition-colors hover:text-[#775a19]">
                            Details
                        </a>
                        <a href="#gallery" className="text-sm tracking-wide text-[#4c4640] transition-colors hover:text-[#775a19]">
                            Gallery
                        </a>
                    </div>
                    <Link
                        href={`/w/${wedding.slug}/rsvp`}
                        className="bg-[#1e1b18] px-7 py-2.5 text-xs font-medium tracking-[0.2em] text-white uppercase transition-opacity hover:opacity-80"
                    >
                        RSVP
                    </Link>
                </nav>
            </header>

            {/* Hero */}
            <section className="relative flex min-h-screen flex-col justify-end overflow-hidden pt-24">
                <motion.div
                    className="absolute inset-0 z-0 bg-cover bg-center"
                    style={{ backgroundImage: `url(${heroImage})` }}
                    initial={{ scale: 1.15 }}
                    animate={{ scale: 1 }}
                    transition={{ duration: 14, ease: 'easeOut' }}
                />
                <div className="absolute inset-0 z-0 bg-gradient-to-t from-[#fff8f3] via-[#1e1b18]/20 to-[#1e1b18]/30" />

                <motion.div
                    className="relative z-10 mx-auto w-full max-w-[1440px] px-5 pb-24 text-center md:px-16 md:text-left"
                    initial={{ opacity: 0, y: 30 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                >
                    <p className="mb-6 text-xs tracking-[0.3em] text-white/90 uppercase drop-shadow">
                        {content?.headline || 'Save the Date'}
                        {eventShort ? ` • ${eventShort}` : ''}
                    </p>
                    <h1 className={`${serif} mb-10 text-6xl leading-[1.05] text-white drop-shadow-lg sm:text-7xl md:text-8xl`}>
                        <CoupleName name={wedding.name} />
                    </h1>

                    <div className="flex flex-col items-center gap-8 md:flex-row md:items-end">
                        {wedding.event_date && (
                            <div className="flex gap-8 border border-white/30 bg-[#1e1b18]/30 px-8 py-6 backdrop-blur-sm">
                                {[
                                    { label: 'Days', value: cd.days },
                                    { label: 'Hours', value: cd.hours },
                                    { label: 'Mins', value: cd.minutes },
                                ].map((u) => (
                                    <div key={u.label} className="text-center text-white">
                                        <span className={`${serif} block text-3xl tabular-nums sm:text-4xl`}>
                                            {String(u.value).padStart(2, '0')}
                                        </span>
                                        <span className="text-[10px] tracking-widest uppercase opacity-70">{u.label}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                        {content?.welcome_message && (
                            <p className="max-w-xs text-sm text-white/90 italic drop-shadow md:text-[#4c4640] md:not-italic md:opacity-100">
                                {content.welcome_message}
                            </p>
                        )}
                    </div>
                </motion.div>
            </section>

            {published && content?.our_story && (
                <section id="story" className="bg-[#faf2ec] py-24 md:py-40">
                    <div className="mx-auto grid max-w-[1440px] items-center gap-10 px-5 md:grid-cols-12 md:px-16">
                        <Reveal className="md:col-span-5">
                            <div className="relative aspect-[3/4] overflow-hidden">
                                <img
                                    src={IMG.story}
                                    alt="Our story"
                                    className="size-full object-cover grayscale transition-all duration-700 hover:grayscale-0"
                                    loading="lazy"
                                />
                            </div>
                        </Reveal>
                        <Reveal delay={0.15} className="md:col-span-6 md:col-start-7">
                            <h2 className="mb-8 text-xs tracking-[0.25em] text-[#775a19] uppercase">The Beginning</h2>
                            <h3 className={`${serif} mb-10 text-4xl leading-tight sm:text-5xl`}>How it began</h3>
                            <p className="text-lg leading-relaxed whitespace-pre-line text-[#4c4640]">{content.our_story}</p>
                            <div className="mt-8 h-px w-16 bg-[#775a19]/40" />
                        </Reveal>
                    </div>
                </section>
            )}

            {/* Details */}
            {published && (content?.venue_name || content?.ceremony_time || content?.dress_code) && (
                <section id="details" className="bg-[#fff8f3] py-24 md:py-40">
                    <div className="mx-auto max-w-[1440px] px-5 md:px-16">
                        <Reveal className="mb-16 text-center">
                            <h2 className={`${serif} text-4xl sm:text-5xl`}>
                                The <span className="italic text-[#775a19]">Celebration</span>
                            </h2>
                            {eventLong && (
                                <p className="mt-4 text-sm tracking-[0.15em] text-[#4c4640] uppercase">{eventLong}</p>
                            )}
                        </Reveal>
                        <Stagger className="grid gap-px overflow-hidden border border-[#cec5bd]/40 bg-[#cec5bd]/40 md:grid-cols-3">
                            {[
                                { label: 'The Venue', value: content.venue_name, sub: content.venue_address },
                                { label: 'Ceremony', value: content.ceremony_time, sub: eventLong },
                                { label: 'Dress Code', value: content.dress_code, sub: null },
                            ]
                                .filter((d) => d.value)
                                .map((d) => (
                                    <StaggerItem key={d.label} className="bg-[#fff8f3] p-10 text-center">
                                        <p className="text-xs tracking-[0.2em] text-[#775a19] uppercase">{d.label}</p>
                                        <p className={`${serif} mt-4 text-2xl`}>{d.value}</p>
                                        {d.sub && <p className="mt-2 text-sm text-[#4c4640]">{d.sub}</p>}
                                    </StaggerItem>
                                ))}
                        </Stagger>
                    </div>
                </section>
            )}

            {/* Gallery */}
            <section id="gallery" className="bg-[#1e1b18] py-24 md:py-40">
                <div className="mx-auto max-w-[1440px] px-5 md:px-16">
                    <Reveal className="mb-16 text-center">
                        <p className="text-xs tracking-[0.25em] text-[#e9c176] uppercase">A glimpse</p>
                        <h2 className={`${serif} mt-4 text-4xl text-[#fff8f3] sm:text-5xl`}>The Atmosphere</h2>
                    </Reveal>
                    <Stagger className="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
                        {GALLERY.map((g) => (
                            <StaggerItem key={g.src} className="group relative aspect-[4/5] overflow-hidden">
                                <img
                                    src={g.src}
                                    alt={g.label}
                                    loading="lazy"
                                    className="size-full object-cover grayscale transition-all duration-700 group-hover:scale-105 group-hover:grayscale-0"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-[#1e1b18]/70 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100" />
                                <span className="absolute bottom-4 left-4 text-xs tracking-[0.2em] text-white/90 uppercase opacity-0 transition-opacity duration-500 group-hover:opacity-100">
                                    {g.label}
                                </span>
                            </StaggerItem>
                        ))}
                    </Stagger>
                </div>
            </section>

            {/* RSVP CTA */}
            <section className="bg-[#fff8f3] py-28 text-center md:py-40">
                <Reveal className="mx-auto max-w-2xl px-5">
                    <h2 className={`${serif} mb-6 text-4xl sm:text-5xl`}>
                        Kindly <span className="italic text-[#775a19]">Respond</span>
                    </h2>
                    <p className="mb-12 text-[#4c4640]">
                        We would be honoured to have you celebrate with us. Find your name to send your reply.
                    </p>
                    <div className="flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href={`/w/${wedding.slug}/rsvp`}
                            className="bg-[#1e1b18] px-12 py-4 text-xs font-medium tracking-[0.3em] text-white uppercase transition-opacity hover:opacity-80"
                        >
                            RSVP
                        </Link>
                        <Link
                            href={`/w/${wedding.slug}/seats`}
                            className="border border-[#1e1b18] px-12 py-4 text-xs font-medium tracking-[0.3em] text-[#1e1b18] uppercase transition-colors hover:bg-[#1e1b18] hover:text-white"
                        >
                            Find your seat
                        </Link>
                    </div>
                </Reveal>
            </section>

            {/* Footer */}
            <footer className="border-t border-[#cec5bd]/30 bg-[#fff8f3] py-16">
                <div className="mx-auto flex max-w-[1440px] flex-col items-center gap-6 px-5 text-center md:flex-row md:justify-between md:px-16 md:text-left">
                    <div>
                        <p className={`${serif} text-3xl`}>
                            <CoupleName name={wedding.name} />
                        </p>
                        {eventShort && (
                            <p className="mt-2 text-xs tracking-[0.2em] text-[#4c4640] uppercase">{eventShort}</p>
                        )}
                    </div>
                    <p className="text-xs tracking-[0.15em] text-[#4c4640]/70 uppercase">Made with WedFlow Atelier</p>
                </div>
            </footer>
        </div>
    );
}

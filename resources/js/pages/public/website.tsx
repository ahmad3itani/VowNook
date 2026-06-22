import { Head, Link } from '@inertiajs/react';
import {
    animate,
    AnimatePresence,
    motion,
    useInView,
    useScroll,
    useTransform,
} from 'framer-motion';
import {
    Cake,
    Camera,
    Car,
    Church,
    GlassWater,
    Heart,
    MapPin,
    Music,
    PartyPopper,
    Sparkles,
    Utensils,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { WebsiteRegistry } from '@/components/public/website-registry';
import type { RegistryData } from '@/components/public/website-registry';
import { WebsiteSchedule } from '@/components/public/website-schedule';
import type { EventData } from '@/components/public/website-schedule';
import {
    WebsiteFaq,
    WebsiteGuestbook,
    WebsiteLocalGuide,
    WebsiteParty,
} from '@/components/public/website-sections';
import type {
    FaqItem,
    GuestbookItem,
    LocalItem,
    PartyMember,
} from '@/components/public/website-sections';
import { WebsiteTravel } from '@/components/public/website-travel';
import type { TravelData } from '@/components/public/website-travel';
import { useTranslations } from '@/hooks/use-translations';

type TimelineItem = { year: string; title: string; body: string };
type Photo = {
    id: number;
    url: string;
    caption: string | null;
    sort_order: number;
};
type ScheduleItem = {
    title: string;
    type: string | null;
    time: string | null;
    location: string | null;
};

type Content = {
    template:
        | 'classic'
        | 'modern'
        | 'botanical'
        | 'blush'
        | 'dark'
        | 'royal'
        | 'dolce'
        | 'destination'
        | 'vibrant';
    headline: string | null;
    welcome_message: string | null;
    our_story: string | null;
    venue_name: string | null;
    venue_address: string | null;
    ceremony_time: string | null;
    dress_code: string | null;
    hero_image_url: string | null;
    hero_image_preview: string | null;
    hero_video_url: string | null;
    story_image_preview: string | null;
    timeline_items: TimelineItem[];
    video_url: string | null;
    music_url: string | null;
    music_title: string | null;
    photos: Photo[];
};

type PageProps = {
    wedding: { name: string; slug: string; event_date: string | null };
    published: boolean;
    content: Content | null;
    schedule: ScheduleItem[];
    events?: EventData[];
    travel?: TravelData;
    registry?: RegistryData;
    party?: PartyMember[];
    faq?: FaqItem[];
    local_guide?: LocalItem[];
    guestbook?: GuestbookItem[];
};

// ── Themes (all light — dark is "bad luck") ─────────────────────────────────────

const THEMES = {
    classic: {
        bg: '#fff8f3',
        primary: '#775a19',
        text: '#1e1b17',
        surface: '#faf2ec',
        dark: '#1e1b18',
        muted: '#4c4640',
        border: '#cec5bd',
    },
    modern: {
        bg: '#f9f9f9',
        primary: '#1a1a1a',
        text: '#111111',
        surface: '#ffffff',
        dark: '#111111',
        muted: '#555555',
        border: '#e0e0e0',
    },
    botanical: {
        bg: '#f4f7f0',
        primary: '#4a7c59',
        text: '#2c3e28',
        surface: '#e8f0e0',
        dark: '#2c3e28',
        muted: '#5a7a6a',
        border: '#b5c9a8',
    },
    blush: {
        bg: '#fdf6f4',
        primary: '#b06a78',
        text: '#3a2a2e',
        surface: '#f9ebe9',
        dark: '#3a2a2e',
        muted: '#8a6a70',
        border: '#ecd6d4',
    },
    royal: {
        bg: '#fbf8f0',
        primary: '#b8902f',
        text: '#26211a',
        surface: '#f4ecd8',
        dark: '#1c1813',
        muted: '#6b5d40',
        border: '#e0d2a8',
    },
    dolce: {
        bg: '#fdf6ee',
        primary: '#c2603d',
        text: '#3a2c22',
        surface: '#f7e9da',
        dark: '#2a1f17',
        muted: '#7a6452',
        border: '#ecd9c4',
    },
    destination: {
        bg: '#f3f7f8',
        primary: '#3d7a8c',
        text: '#22343a',
        surface: '#e4eef0',
        dark: '#1c2a30',
        muted: '#5a727a',
        border: '#bcd4d8',
    },
    vibrant: {
        bg: '#fff5f2',
        primary: '#d2436a',
        text: '#36202a',
        surface: '#ffe7e0',
        dark: '#2a1820',
        muted: '#8a5a68',
        border: '#f3cdc7',
    },
} as const;

type ThemeKey = keyof typeof THEMES;

const FALLBACK_HERO = '/images/wedding/hero.jpg';
const FALLBACK_STORY = '/images/wedding/story.jpg';

const serif = "font-['Playfair_Display']";

const longDate = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});
const shortDate = new Intl.DateTimeFormat('en-CA', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
});

// ── Small building blocks ───────────────────────────────────────────────────────

function CoupleName({ name, className }: { name: string; className?: string }) {
    const parts = name.split(/\s*&\s*/);

    if (parts.length < 2) {
        return <span className={className}>{name}</span>;
    }

    return (
        <span className={className}>
            {parts[0]}{' '}
            <span
                className="font-normal italic"
                style={{ color: 'var(--c-primary)' }}
            >
                &amp;
            </span>{' '}
            {parts.slice(1).join(' & ')}
        </span>
    );
}

/** Count up to a number when scrolled into view. */
function CountUp({ to, className }: { to: number; className?: string }) {
    const ref = useRef<HTMLSpanElement>(null);
    const inView = useInView(ref, { once: true, margin: '-60px' });
    const [val, setVal] = useState(0);
    useEffect(() => {
        if (!inView) {
            return;
        }

        const controls = animate(0, to, {
            duration: 1.7,
            ease: [0.22, 1, 0.36, 1],
            onUpdate: (v) => setVal(Math.floor(v)),
        });

        return () => controls.stop();
    }, [inView, to]);

    return (
        <span ref={ref} className={className}>
            {val.toLocaleString()}
        </span>
    );
}

/** Decorative centered flourish between sections. */
function Divider() {
    return (
        <div
            className="flex items-center justify-center gap-3 py-2"
            aria-hidden
        >
            <span
                className="h-px w-16"
                style={{ background: 'var(--c-primary)', opacity: 0.35 }}
            />
            <Sparkles
                className="size-4"
                style={{ color: 'var(--c-primary)' }}
            />
            <span
                className="h-px w-16"
                style={{ background: 'var(--c-primary)', opacity: 0.35 }}
            />
        </div>
    );
}

/** Gentle falling petals over the hero. */
function Petals() {
    const petals = Array.from({ length: 14 });

    return (
        <div
            className="pointer-events-none absolute inset-0 z-[5] overflow-hidden"
            aria-hidden
        >
            {petals.map((_, i) => {
                const left = (i * 7.3) % 100;
                const delay = (i % 7) * 1.1;
                const duration = 9 + (i % 5) * 2;
                const size = 10 + (i % 4) * 5;

                return (
                    <motion.div
                        key={i}
                        className="absolute -top-10"
                        style={{ left: `${left}%` }}
                        initial={{ y: '-10vh', x: 0, rotate: 0, opacity: 0 }}
                        animate={{
                            y: '110vh',
                            x: [0, 24, -16, 12, 0],
                            rotate: [0, 90, 180, 280, 360],
                            opacity: [0, 0.9, 0.9, 0.7, 0],
                        }}
                        transition={{
                            duration,
                            delay,
                            repeat: Infinity,
                            ease: 'easeInOut',
                        }}
                    >
                        <svg
                            width={size}
                            height={size}
                            viewBox="0 0 20 20"
                            fill="none"
                        >
                            <path
                                d="M10 0C13 6 20 7 20 12C20 17 14 20 10 20C6 20 0 17 0 12C0 7 7 6 10 0Z"
                                fill="#fff"
                                fillOpacity="0.85"
                            />
                        </svg>
                    </motion.div>
                );
            })}
        </div>
    );
}

const TYPE_ICON: Record<string, typeof Heart> = {
    preparation: Sparkles,
    ceremony: Church,
    photos: Camera,
    cocktails: GlassWater,
    reception: Utensils,
    party: Music,
    travel: Car,
    // graceful extras
    dinner: Utensils,
    cake: Cake,
    dancing: PartyPopper,
};

function eventIcon(type: string | null) {
    return (type && TYPE_ICON[type.toLowerCase()]) || Heart;
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

function extractVideoEmbed(url: string): string | null {
    try {
        const u = new URL(url);

        if (
            u.hostname.includes('youtube.com') ||
            u.hostname.includes('youtu.be')
        ) {
            const vid = u.searchParams.get('v') ?? u.pathname.split('/').pop();

            if (vid) {
                return `https://www.youtube-nocookie.com/embed/${vid}?autoplay=0&rel=0`;
            }
        }

        if (u.hostname.includes('vimeo.com')) {
            const vid = u.pathname.split('/').filter(Boolean).pop();

            if (vid) {
                return `https://player.vimeo.com/video/${vid}`;
            }
        }
    } catch {
        /* invalid */
    }

    return null;
}

function extractHeroEmbed(url: string): string | null {
    try {
        const u = new URL(url);

        if (
            u.hostname.includes('youtube.com') ||
            u.hostname.includes('youtu.be')
        ) {
            const vid = u.searchParams.get('v') ?? u.pathname.split('/').pop();

            if (vid) {
                return `https://www.youtube-nocookie.com/embed/${vid}?autoplay=1&mute=1&loop=1&playlist=${vid}&controls=0&showinfo=0&rel=0`;
            }
        }
    } catch {
        /* noop */
    }

    return null;
}

// ── Page ────────────────────────────────────────────────────────────────────────

export default function PublicWebsite({
    wedding,
    published,
    content,
    schedule = [],
    events = [],
    travel = { notes: null, stays: [] },
    registry = { funds: [], items: [] },
    party = [],
    faq = [],
    local_guide = [],
    guestbook = [],
}: PageProps) {
    const { t } = useTranslations();

    // "dark" template is remapped to classic — no dark wedding sites.
    const rawTemplate = content?.template ?? 'classic';
    const templateKey: ThemeKey =
        rawTemplate === 'dark' || !(rawTemplate in THEMES)
            ? 'classic'
            : (rawTemplate as ThemeKey);
    const theme = THEMES[templateKey];

    const heroImage =
        content?.hero_image_preview ?? content?.hero_image_url ?? FALLBACK_HERO;
    const heroVideoEmbed = content?.hero_video_url
        ? extractHeroEmbed(content.hero_video_url)
        : null;
    const storyImage = content?.story_image_preview ?? FALLBACK_STORY;
    const videoEmbed = content?.video_url
        ? extractVideoEmbed(content.video_url)
        : null;
    const photos = content?.photos ?? [];
    const timeline = content?.timeline_items ?? [];

    const eventLong = wedding.event_date
        ? longDate.format(new Date(wedding.event_date))
        : null;
    const eventShort = wedding.event_date
        ? shortDate.format(new Date(wedding.event_date))
        : null;
    const cd = useCountdown(wedding.event_date);

    const { scrollY, scrollYProgress } = useScroll();
    const heroY = useTransform(scrollY, [0, 900], [0, 240]);
    const heroOpacity = useTransform(scrollY, [0, 600], [1, 0]);

    const [lightbox, setLightbox] = useState<Photo | null>(null);

    // Opening "tap to open" intro + background music.
    const musicUrl = content?.music_url ?? null;
    const musicTitle = content?.music_title ?? null;
    const audioRef = useRef<HTMLAudioElement>(null);
    const [opened, setOpened] = useState(false);
    const [playing, setPlaying] = useState(false);

    // Lock page scroll while the cover is showing.
    useEffect(() => {
        if (opened) {
            return;
        }

        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = prev;
        };
    }, [opened]);

    function openInvitation() {
        setOpened(true);
        // The click is the user gesture browsers require to start audio.
        const a = audioRef.current;

        if (a) {
            a.play()
                .then(() => setPlaying(true))
                .catch(() => setPlaying(false));
        }
    }

    function toggleMusic() {
        const a = audioRef.current;

        if (!a) {
            return;
        }

        if (a.paused) {
            a.play()
                .then(() => setPlaying(true))
                .catch(() => undefined);
        } else {
            a.pause();
            setPlaying(false);
        }
    }

    const cssVars = {
        '--c-bg': theme.bg,
        '--c-primary': theme.primary,
        '--c-text': theme.text,
        '--c-surface': theme.surface,
        '--c-dark': theme.dark,
        '--c-muted': theme.muted,
        '--c-border': theme.border,
    } as React.CSSProperties;

    return (
        <div
            className="min-h-screen font-['DM_Sans'] antialiased"
            style={{
                ...cssVars,
                background: 'var(--c-bg)',
                color: 'var(--c-text)',
            }}
        >
            <Head title={wedding.name} />

            {/* Background music */}
            {musicUrl && (
                <>
                    <audio
                        ref={audioRef}
                        src={musicUrl}
                        loop
                        onPlay={() => setPlaying(true)}
                        onPause={() => setPlaying(false)}
                    />
                    <button
                        type="button"
                        onClick={toggleMusic}
                        aria-label={playing ? 'Pause music' : 'Play music'}
                        title={
                            musicTitle ??
                            (playing ? 'Pause music' : 'Play music')
                        }
                        className="fixed right-5 bottom-5 z-50 flex size-12 items-center justify-center rounded-full text-white shadow-lg transition-transform hover:scale-105"
                        style={{ background: 'var(--c-primary)' }}
                    >
                        {playing && (
                            <>
                                <span
                                    className="absolute inset-0 animate-ping rounded-full opacity-30"
                                    style={{ background: 'var(--c-primary)' }}
                                />
                                <span className="absolute -inset-1 animate-pulse rounded-full border border-white/40" />
                            </>
                        )}
                        <Music className="relative size-5" />
                    </button>
                </>
            )}

            {/* Opening "tap to open" cover */}
            <AnimatePresence>
                {!opened && (
                    <motion.div
                        className="fixed inset-0 z-[70] flex flex-col items-center justify-center px-6 text-center"
                        style={{
                            background: 'var(--c-bg)',
                            color: 'var(--c-text)',
                        }}
                        initial={{ opacity: 1 }}
                        exit={{ opacity: 0, y: '-100%' }}
                        transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                    >
                        <Petals />
                        <motion.div
                            initial={{ opacity: 0, y: 24 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 1,
                                ease: [0.22, 1, 0.36, 1],
                            }}
                            className="relative z-10 flex flex-col items-center"
                        >
                            <p
                                className="mb-6 text-xs tracking-[0.3em] uppercase"
                                style={{ color: 'var(--c-primary)' }}
                            >
                                Together with their families
                            </p>
                            <h1
                                className={`${serif} text-5xl leading-tight sm:text-6xl md:text-7xl`}
                            >
                                <CoupleName name={wedding.name} />
                            </h1>
                            {eventLong && (
                                <p
                                    className="mt-6 text-sm tracking-[0.2em] uppercase"
                                    style={{ color: 'var(--c-muted)' }}
                                >
                                    {eventLong}
                                </p>
                            )}
                            <button
                                type="button"
                                onClick={openInvitation}
                                className="mt-12 px-10 py-4 text-xs font-medium tracking-[0.25em] text-white uppercase transition-opacity hover:opacity-85"
                                style={{ background: 'var(--c-dark)' }}
                            >
                                Open Invitation
                            </button>
                            {musicUrl && (
                                <p
                                    className="mt-5 flex items-center gap-1.5 text-[11px] tracking-wide"
                                    style={{ color: 'var(--c-muted)' }}
                                >
                                    <Music className="size-3" /> Best with sound
                                    on
                                </p>
                            )}
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Scroll progress */}
            <motion.div
                className="fixed inset-x-0 top-0 z-[60] h-[3px] origin-left"
                style={{
                    scaleX: scrollYProgress,
                    background: 'var(--c-primary)',
                }}
            />

            {/* Header */}
            <header
                className="fixed inset-x-0 top-0 z-50 border-b backdrop-blur-md"
                style={{
                    borderColor: 'var(--c-border)',
                    background: `color-mix(in srgb, var(--c-bg) 80%, transparent)`,
                }}
            >
                <nav className="mx-auto flex max-w-[1440px] items-center justify-between px-5 py-5 md:px-16">
                    <span className={`${serif} text-2xl tracking-tight`}>
                        <CoupleName name={wedding.name} />
                    </span>
                    <div className="hidden items-center gap-10 md:flex">
                        {[
                            content?.our_story && {
                                href: '#story',
                                label: 'The Story',
                            },
                            (content?.venue_name || content?.ceremony_time) && {
                                href: '#details',
                                label: 'Details',
                            },
                            party.length > 0 && {
                                href: '#party',
                                label: 'Party',
                            },
                            events.length > 0 && {
                                href: '#events',
                                label: 'Events',
                            },
                            schedule.length > 0 && {
                                href: '#schedule',
                                label: 'The Day',
                            },
                            (travel.stays.length > 0 || travel.notes) && {
                                href: '#travel',
                                label: 'Travel',
                            },
                            timeline.length > 0 && {
                                href: '#timeline',
                                label: 'Journey',
                            },
                            photos.length > 0 && {
                                href: '#gallery',
                                label: 'Gallery',
                            },
                            faq.length > 0 && { href: '#faq', label: 'FAQ' },
                            guestbook.length > 0 && {
                                href: '#guestbook',
                                label: 'Guestbook',
                            },
                        ]
                            .filter(Boolean)
                            .map(
                                (link) =>
                                    link && (
                                        <a
                                            key={link.href}
                                            href={link.href}
                                            className="text-sm tracking-wide transition-colors hover:opacity-80"
                                            style={{ color: 'var(--c-muted)' }}
                                        >
                                            {link.label}
                                        </a>
                                    ),
                            )}
                    </div>
                    <Link
                        href={`/w/${wedding.slug}/rsvp`}
                        className="px-7 py-2.5 text-xs font-medium tracking-[0.2em] text-white uppercase transition-opacity hover:opacity-80"
                        style={{ background: 'var(--c-dark)' }}
                    >
                        RSVP
                    </Link>
                </nav>
            </header>

            {/* Hero */}
            <section className="relative flex min-h-screen flex-col justify-end overflow-hidden pt-24">
                {heroVideoEmbed ? (
                    <div className="absolute inset-0 z-0 overflow-hidden">
                        <iframe
                            src={heroVideoEmbed}
                            allow="autoplay; encrypted-media"
                            className="absolute top-1/2 left-1/2 aspect-video w-[177.78vh] min-w-full -translate-x-1/2 -translate-y-1/2 border-0"
                            title="Hero video"
                        />
                    </div>
                ) : (
                    <motion.div
                        className="absolute inset-0 z-0 bg-cover bg-center"
                        style={{
                            backgroundImage: `url(${heroImage})`,
                            y: heroY,
                            scale: 1.1,
                        }}
                    />
                )}
                <div className="absolute inset-0 z-0 bg-gradient-to-t from-[var(--c-bg)] via-[var(--c-dark)]/20 to-[var(--c-dark)]/40" />
                <Petals />

                <motion.div
                    className="relative z-10 mx-auto w-full max-w-[1440px] px-5 pb-24 text-center md:px-16 md:text-left"
                    style={{ opacity: heroOpacity }}
                >
                    <motion.p
                        className="mb-6 text-xs tracking-[0.3em] text-white/90 uppercase drop-shadow"
                        initial={{ opacity: 0, y: 16 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.8, delay: 0.3 }}
                    >
                        {content?.headline || 'Save the Date'}
                        {eventShort ? ` • ${eventShort}` : ''}
                    </motion.p>
                    <motion.h1
                        className={`${serif} mb-10 text-6xl leading-[1.05] text-white drop-shadow-lg sm:text-7xl md:text-8xl`}
                        initial={{ opacity: 0, y: 30, letterSpacing: '0.1em' }}
                        animate={{ opacity: 1, y: 0, letterSpacing: '0em' }}
                        transition={{
                            duration: 1.2,
                            delay: 0.45,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                    >
                        <CoupleName name={wedding.name} />
                    </motion.h1>

                    <motion.div
                        className="flex flex-col items-center gap-8 md:flex-row md:items-end"
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.9, delay: 0.8 }}
                    >
                        {wedding.event_date && (
                            <div className="flex gap-8 border border-white/30 bg-[var(--c-dark)]/30 px-8 py-6 backdrop-blur-sm">
                                {[
                                    { label: 'Days', value: cd.days },
                                    { label: 'Hours', value: cd.hours },
                                    { label: 'Mins', value: cd.minutes },
                                ].map((u) => (
                                    <div
                                        key={u.label}
                                        className="text-center text-white"
                                    >
                                        <span
                                            className={`${serif} block text-3xl tabular-nums sm:text-4xl`}
                                        >
                                            {String(u.value).padStart(2, '0')}
                                        </span>
                                        <span className="text-[10px] tracking-widest uppercase opacity-70">
                                            {u.label}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                        {content?.welcome_message && (
                            <p className="max-w-xs text-sm text-white/90 italic drop-shadow">
                                {content.welcome_message}
                            </p>
                        )}
                    </motion.div>
                </motion.div>

                {/* Scroll indicator */}
                <motion.div
                    className="absolute bottom-8 left-1/2 z-10 -translate-x-1/2"
                    animate={{ y: [0, 10, 0] }}
                    transition={{
                        duration: 1.8,
                        repeat: Infinity,
                        ease: 'easeInOut',
                    }}
                >
                    <div className="flex h-10 w-6 items-start justify-center rounded-full border-2 border-white/60 p-1.5">
                        <div className="h-2 w-1 rounded-full bg-white/80" />
                    </div>
                </motion.div>
            </section>

            {/* By the numbers */}
            {wedding.event_date && (
                <section
                    className="py-20 md:py-28"
                    style={{ background: 'var(--c-bg)' }}
                >
                    <div className="mx-auto max-w-[1440px] px-5 md:px-16">
                        <Reveal className="mb-12">
                            <Divider />
                        </Reveal>
                        <div className="grid grid-cols-2 gap-8 text-center md:grid-cols-4">
                            {[
                                {
                                    value: cd.days,
                                    label: 'Days to go',
                                    suffix: '',
                                },
                                {
                                    value: photos.length,
                                    label: 'Moments captured',
                                    suffix: '',
                                },
                                {
                                    value: timeline.length || 1,
                                    label: 'Chapters of us',
                                    suffix: '',
                                },
                                {
                                    value: 1,
                                    label: 'Unforgettable day',
                                    suffix: '',
                                },
                            ].map((s, i) => (
                                <Reveal key={s.label} delay={i * 0.1}>
                                    <p
                                        className={`${serif} text-5xl sm:text-6xl`}
                                        style={{ color: 'var(--c-primary)' }}
                                    >
                                        <CountUp to={s.value} />
                                        {s.suffix}
                                    </p>
                                    <p
                                        className="mt-2 text-xs tracking-[0.2em] uppercase"
                                        style={{ color: 'var(--c-muted)' }}
                                    >
                                        {s.label}
                                    </p>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Our Story */}
            {published && content?.our_story && (
                <section
                    id="story"
                    className="py-24 md:py-40"
                    style={{ background: 'var(--c-surface)' }}
                >
                    <div className="mx-auto grid max-w-[1440px] items-center gap-10 px-5 md:grid-cols-12 md:px-16">
                        <Reveal className="md:col-span-5">
                            <div className="relative aspect-[3/4] overflow-hidden">
                                <img
                                    src={storyImage}
                                    alt="Our story"
                                    className="size-full object-cover grayscale transition-all duration-700 hover:grayscale-0"
                                    loading="lazy"
                                />
                            </div>
                        </Reveal>
                        <Reveal
                            delay={0.15}
                            className="md:col-span-6 md:col-start-7"
                        >
                            <h2
                                className="mb-8 text-xs tracking-[0.25em] uppercase"
                                style={{ color: 'var(--c-primary)' }}
                            >
                                The Beginning
                            </h2>
                            <h3
                                className={`${serif} mb-10 text-4xl leading-tight sm:text-5xl`}
                            >
                                How it began
                            </h3>
                            <p
                                className="text-lg leading-relaxed whitespace-pre-line"
                                style={{ color: 'var(--c-muted)' }}
                            >
                                {content.our_story}
                            </p>
                            <div
                                className="mt-8 h-px w-16"
                                style={{
                                    background: 'var(--c-primary)',
                                    opacity: 0.4,
                                }}
                            />
                        </Reveal>
                    </div>
                </section>
            )}

            {/* Details */}
            {published &&
                (content?.venue_name ||
                    content?.ceremony_time ||
                    content?.dress_code) && (
                    <section
                        id="details"
                        className="py-24 md:py-40"
                        style={{ background: 'var(--c-bg)' }}
                    >
                        <div className="mx-auto max-w-[1440px] px-5 md:px-16">
                            <Reveal className="mb-16 text-center">
                                <h2 className={`${serif} text-4xl sm:text-5xl`}>
                                    The{' '}
                                    <span
                                        className="italic"
                                        style={{ color: 'var(--c-primary)' }}
                                    >
                                        Celebration
                                    </span>
                                </h2>
                                {eventLong && (
                                    <p
                                        className="mt-4 text-sm tracking-[0.15em] uppercase"
                                        style={{ color: 'var(--c-muted)' }}
                                    >
                                        {eventLong}
                                    </p>
                                )}
                            </Reveal>
                            <Stagger
                                className="grid gap-px overflow-hidden border md:grid-cols-3"
                                style={{
                                    borderColor: 'var(--c-border)',
                                    background: 'var(--c-border)',
                                }}
                            >
                                {[
                                    {
                                        label: 'The Venue',
                                        value: content.venue_name,
                                        sub: content.venue_address,
                                    },
                                    {
                                        label: 'Ceremony',
                                        value: content.ceremony_time,
                                        sub: eventLong,
                                    },
                                    {
                                        label: 'Dress Code',
                                        value: content.dress_code,
                                        sub: null,
                                    },
                                ]
                                    .filter((d) => d.value)
                                    .map((d) => (
                                        <StaggerItem
                                            key={d.label}
                                            className="p-10 text-center"
                                            style={{
                                                background: 'var(--c-bg)',
                                            }}
                                        >
                                            <p
                                                className="text-xs tracking-[0.2em] uppercase"
                                                style={{
                                                    color: 'var(--c-primary)',
                                                }}
                                            >
                                                {d.label}
                                            </p>
                                            <p
                                                className={`${serif} mt-4 text-2xl`}
                                            >
                                                {d.value}
                                            </p>
                                            {d.sub && (
                                                <p
                                                    className="mt-2 text-sm"
                                                    style={{
                                                        color: 'var(--c-muted)',
                                                    }}
                                                >
                                                    {d.sub}
                                                </p>
                                            )}
                                        </StaggerItem>
                                    ))}
                            </Stagger>
                            {content.venue_address && (
                                <Reveal className="mt-10 text-center">
                                    <a
                                        href={`https://maps.google.com/?q=${encodeURIComponent(`${content.venue_name ?? ''} ${content.venue_address}`)}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-2 text-sm tracking-wide transition-opacity hover:opacity-70"
                                        style={{ color: 'var(--c-primary)' }}
                                    >
                                        <MapPin className="size-4" /> Get
                                        directions
                                    </a>
                                </Reveal>
                            )}
                        </div>
                    </section>
                )}

            {/* Wedding party */}
            {published && <WebsiteParty party={party} />}

            {/* Celebration weekend — multiple events with per-event RSVP */}
            {published && <WebsiteSchedule events={events} />}

            {/* Order of the Day */}
            {published && schedule.length > 0 && (
                <section
                    id="schedule"
                    className="py-24 md:py-40"
                    style={{ background: 'var(--c-surface)' }}
                >
                    <div className="mx-auto max-w-3xl px-5 md:px-16">
                        <Reveal className="mb-16 text-center">
                            <p
                                className="text-xs tracking-[0.25em] uppercase"
                                style={{ color: 'var(--c-primary)' }}
                            >
                                What to expect
                            </p>
                            <h2
                                className={`${serif} mt-4 text-4xl sm:text-5xl`}
                            >
                                Order of the Day
                            </h2>
                        </Reveal>
                        <div className="relative">
                            <div
                                className="absolute top-2 bottom-2 left-[27px] w-px md:left-1/2 md:-translate-x-1/2"
                                style={{ background: 'var(--c-border)' }}
                            />
                            <div className="flex flex-col gap-2">
                                {schedule.map((ev, i) => {
                                    const Icon = eventIcon(ev.type);

                                    return (
                                        <Reveal key={i} delay={i * 0.08}>
                                            <div
                                                className={`relative flex items-center gap-5 py-5 md:gap-0 ${i % 2 === 0 ? 'md:flex-row' : 'md:flex-row-reverse'}`}
                                            >
                                                <div
                                                    className={`hidden md:block md:w-1/2 ${i % 2 === 0 ? 'md:pr-12 md:text-right' : 'md:pl-12 md:text-left'}`}
                                                >
                                                    <p
                                                        className={`${serif} text-xl`}
                                                    >
                                                        {ev.title}
                                                    </p>
                                                    {ev.location && (
                                                        <p
                                                            className="mt-1 text-sm"
                                                            style={{
                                                                color: 'var(--c-muted)',
                                                            }}
                                                        >
                                                            {ev.location}
                                                        </p>
                                                    )}
                                                    {ev.time && (
                                                        <p
                                                            className="mt-1 text-xs tracking-widest uppercase"
                                                            style={{
                                                                color: 'var(--c-primary)',
                                                            }}
                                                        >
                                                            {ev.time}
                                                        </p>
                                                    )}
                                                </div>
                                                <div
                                                    className="z-10 flex size-14 shrink-0 items-center justify-center rounded-full border-2 md:absolute md:left-1/2 md:-translate-x-1/2"
                                                    style={{
                                                        borderColor:
                                                            'var(--c-primary)',
                                                        background:
                                                            'var(--c-bg)',
                                                        color: 'var(--c-primary)',
                                                    }}
                                                >
                                                    <Icon className="size-6" />
                                                </div>
                                                <div className="md:hidden">
                                                    <p
                                                        className={`${serif} text-xl`}
                                                    >
                                                        {ev.title}
                                                    </p>
                                                    {ev.time && (
                                                        <p
                                                            className="text-xs tracking-widest uppercase"
                                                            style={{
                                                                color: 'var(--c-primary)',
                                                            }}
                                                        >
                                                            {ev.time}
                                                        </p>
                                                    )}
                                                    {ev.location && (
                                                        <p
                                                            className="text-sm"
                                                            style={{
                                                                color: 'var(--c-muted)',
                                                            }}
                                                        >
                                                            {ev.location}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="hidden md:block md:w-1/2" />
                                            </div>
                                        </Reveal>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </section>
            )}

            {/* Journey timeline */}
            {published && timeline.length > 0 && (
                <section
                    id="timeline"
                    className="py-24 md:py-40"
                    style={{ background: 'var(--c-bg)' }}
                >
                    <div className="mx-auto max-w-3xl px-5 md:px-16">
                        <Reveal className="mb-16 text-center">
                            <p
                                className="text-xs tracking-[0.25em] uppercase"
                                style={{ color: 'var(--c-primary)' }}
                            >
                                Our journey
                            </p>
                            <h2
                                className={`${serif} mt-4 text-4xl sm:text-5xl`}
                            >
                                How we got here
                            </h2>
                        </Reveal>
                        <div className="relative flex flex-col gap-0">
                            <div
                                className="absolute inset-y-0 left-16 w-px md:left-1/2"
                                style={{ background: 'var(--c-border)' }}
                            />
                            {timeline.map((item, i) => (
                                <Reveal key={i} delay={i * 0.1}>
                                    <div
                                        className={`relative flex gap-8 pb-14 ${i % 2 === 0 ? 'md:flex-row' : 'md:flex-row-reverse'}`}
                                    >
                                        <div className="flex w-32 shrink-0 flex-col items-center md:w-1/2 md:items-end">
                                            <span
                                                className="z-10 rounded-full px-4 py-1.5 text-xs font-semibold tracking-widest text-white uppercase"
                                                style={{
                                                    background:
                                                        'var(--c-primary)',
                                                }}
                                            >
                                                {item.year}
                                            </span>
                                        </div>
                                        <div
                                            className={`flex-1 ${i % 2 === 0 ? '' : 'md:text-right'}`}
                                        >
                                            <h4
                                                className={`${serif} text-xl font-semibold`}
                                            >
                                                {item.title}
                                            </h4>
                                            {item.body && (
                                                <p
                                                    className="mt-2 text-sm leading-relaxed"
                                                    style={{
                                                        color: 'var(--c-muted)',
                                                    }}
                                                >
                                                    {item.body}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Gallery */}
            {photos.length > 0 && (
                <section
                    id="gallery"
                    className="py-24 md:py-40"
                    style={{ background: 'var(--c-dark)' }}
                >
                    <div className="mx-auto max-w-[1440px] px-5 md:px-16">
                        <Reveal className="mb-16 text-center">
                            <p
                                className="text-xs tracking-[0.25em] uppercase"
                                style={{ color: 'var(--c-primary)' }}
                            >
                                A glimpse
                            </p>
                            <h2
                                className={`${serif} mt-4 text-4xl sm:text-5xl`}
                                style={{ color: 'var(--c-bg)' }}
                            >
                                The Atmosphere
                            </h2>
                        </Reveal>
                        <Stagger className="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
                            {photos.map((g) => (
                                <StaggerItem
                                    key={g.id}
                                    className="group relative aspect-[4/5] overflow-hidden"
                                >
                                    <button
                                        type="button"
                                        onClick={() => setLightbox(g)}
                                        className="absolute inset-0 block size-full cursor-pointer"
                                    >
                                        <img
                                            src={g.url}
                                            alt={g.caption ?? ''}
                                            loading="lazy"
                                            className="size-full object-cover grayscale transition-all duration-700 group-hover:scale-105 group-hover:grayscale-0"
                                        />
                                        <div className="absolute inset-0 bg-gradient-to-t from-[var(--c-dark)]/70 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100" />
                                        {g.caption && (
                                            <span className="absolute bottom-4 left-4 text-xs tracking-[0.2em] text-white/90 uppercase opacity-0 transition-opacity duration-500 group-hover:opacity-100">
                                                {g.caption}
                                            </span>
                                        )}
                                    </button>
                                </StaggerItem>
                            ))}
                        </Stagger>
                    </div>
                </section>
            )}

            {/* Lightbox */}
            <AnimatePresence>
                {lightbox && (
                    <motion.div
                        className="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={() => setLightbox(null)}
                    >
                        <button
                            className="absolute top-6 right-6 text-white/80 hover:text-white"
                            onClick={() => setLightbox(null)}
                            aria-label="Close"
                        >
                            <X className="size-7" />
                        </button>
                        <motion.img
                            src={lightbox.url}
                            alt={lightbox.caption ?? ''}
                            className="max-h-[88vh] max-w-[92vw] object-contain shadow-2xl"
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            exit={{ scale: 0.92, opacity: 0 }}
                            transition={{
                                type: 'spring',
                                stiffness: 260,
                                damping: 26,
                            }}
                            onClick={(e) => e.stopPropagation()}
                        />
                        {lightbox.caption && (
                            <p className="absolute bottom-6 left-1/2 -translate-x-1/2 text-sm tracking-[0.15em] text-white/80 uppercase">
                                {lightbox.caption}
                            </p>
                        )}
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Video */}
            {published && videoEmbed && (
                <section
                    className="py-24 md:py-32"
                    style={{ background: 'var(--c-surface)' }}
                >
                    <div className="mx-auto max-w-4xl px-5 md:px-16">
                        <Reveal className="mb-12 text-center">
                            <p
                                className="text-xs tracking-[0.25em] uppercase"
                                style={{ color: 'var(--c-primary)' }}
                            >
                                Our story
                            </p>
                            <h2
                                className={`${serif} mt-4 text-4xl sm:text-5xl`}
                            >
                                Watch our video
                            </h2>
                        </Reveal>
                        <Reveal>
                            <div className="relative aspect-video overflow-hidden rounded-lg shadow-2xl">
                                <iframe
                                    src={videoEmbed}
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowFullScreen
                                    className="absolute inset-0 size-full border-0"
                                    title="Wedding video"
                                />
                            </div>
                        </Reveal>
                    </div>
                </section>
            )}

            {/* Registry */}
            {published && <WebsiteTravel travel={travel} />}

            {/* Things to do nearby + FAQ */}
            {published && <WebsiteLocalGuide items={local_guide} />}
            {published && <WebsiteFaq items={faq} />}

            <WebsiteRegistry registry={registry} slug={wedding.slug} />

            {/* Guestbook — approved well-wishes + leave-a-message form */}
            {published && (
                <WebsiteGuestbook entries={guestbook} slug={wedding.slug} />
            )}

            {/* RSVP CTA */}
            <section
                className="relative overflow-hidden py-28 text-center md:py-44"
                style={{ background: 'var(--c-bg)' }}
            >
                <Reveal className="mx-auto max-w-2xl px-5">
                    <Heart
                        className="mx-auto mb-6 size-7"
                        style={{ color: 'var(--c-primary)' }}
                    />
                    <h2 className={`${serif} mb-6 text-4xl sm:text-5xl`}>
                        Kindly{' '}
                        <span
                            className="italic"
                            style={{ color: 'var(--c-primary)' }}
                        >
                            Respond
                        </span>
                    </h2>
                    <p className="mb-12" style={{ color: 'var(--c-muted)' }}>
                        We would be honoured to have you celebrate with us. Find
                        your name to send your reply.
                    </p>
                    <div className="flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href={`/w/${wedding.slug}/rsvp`}
                            className="px-12 py-4 text-xs font-medium tracking-[0.3em] text-white uppercase transition-opacity hover:opacity-80"
                            style={{ background: 'var(--c-dark)' }}
                        >
                            RSVP
                        </Link>
                        <Link
                            href={`/w/${wedding.slug}/seats`}
                            className="border px-12 py-4 text-xs font-medium tracking-[0.3em] uppercase transition-colors hover:opacity-80"
                            style={{
                                borderColor: 'var(--c-dark)',
                                color: 'var(--c-text)',
                            }}
                        >
                            Find your seat
                        </Link>
                    </div>
                </Reveal>
            </section>

            {/* Footer */}
            <footer
                className="border-t py-16"
                style={{
                    borderColor: 'var(--c-border)',
                    background: 'var(--c-bg)',
                }}
            >
                <div className="mx-auto flex max-w-[1440px] flex-col items-center gap-6 px-5 text-center md:flex-row md:justify-between md:px-16 md:text-left">
                    <div>
                        <p className={`${serif} text-3xl`}>
                            <CoupleName name={wedding.name} />
                        </p>
                        {eventShort && (
                            <p
                                className="mt-2 text-xs tracking-[0.2em] uppercase"
                                style={{ color: 'var(--c-muted)' }}
                            >
                                {eventShort}
                            </p>
                        )}
                    </div>
                    <p
                        className="text-xs tracking-[0.15em] uppercase opacity-70"
                        style={{ color: 'var(--c-muted)' }}
                    >
                        {t('public.footer', 'Made with VowNook')}
                    </p>
                </div>
            </footer>
        </div>
    );
}

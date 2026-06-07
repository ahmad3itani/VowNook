import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Armchair,
    ArrowRight,
    CalendarClock,
    Check,
    ChevronDown,
    LayoutGrid,
    Sparkles,
    Users,
    Wallet,
} from 'lucide-react';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { dashboard, login, register } from '@/routes';

const serif = "font-['Playfair_Display']";
const DEMO = '/w/amelia-and-julian';

const experience = [
    {
        icon: LayoutGrid,
        title: 'Immersive Floor Plans',
        body: 'Arrange tables and chairs to scale, place the dance floor and bar, and seat every guest by chair.',
    },
    {
        icon: Sparkles,
        title: 'The Material Library',
        body: 'Curate inspiration, palettes, and the details that make the day unmistakably yours.',
    },
];

const tiers = [
    {
        name: 'Essential',
        price: '$0',
        cadence: 'per wedding',
        features: ['Guest list & RSVP', 'Budget tracker', 'Vendor directory', 'Checklist & timeline'],
        cta: 'Begin journey',
        featured: false,
    },
    {
        name: 'The Atelier',
        price: '$99',
        cadence: 'per wedding',
        features: [
            'Everything in Essential',
            'Public wedding website',
            'Floor-plan & seat finder',
            'Collaborators & gallery',
        ],
        cta: 'Choose Atelier',
        featured: true,
    },
    {
        name: 'Studio Pro',
        price: '$499',
        cadence: 'for planners',
        features: ['Unlimited weddings', 'White-label portals', 'Priority support', 'All Atelier features'],
        cta: 'For professionals',
        featured: false,
    },
];

export default function Welcome() {
    const { auth } = usePage().props;
    const authed = !!auth?.user;

    return (
        <div className="min-h-screen bg-[#fff8f3] font-['DM_Sans'] text-[#1e1b17] antialiased selection:bg-[#fed488]/40">
            <Head title="WedFlow Atelier — A wedding, composed." />

            {/* Header */}
            <header className="fixed inset-x-0 top-0 z-50 border-b border-[#cec5bd]/30 bg-[#fff8f3]/80 backdrop-blur-md">
                <nav className="mx-auto flex max-w-[1440px] items-center justify-between px-5 py-5 md:px-16">
                    <span className={`${serif} text-2xl tracking-tight`}>WedFlow Atelier</span>
                    <div className="hidden items-center gap-10 md:flex">
                        <a href="#experience" className="text-sm tracking-wide text-[#4c4640] transition-colors hover:text-[#775a19]">
                            The Atelier
                        </a>
                        <a href="#craft" className="text-sm tracking-wide text-[#4c4640] transition-colors hover:text-[#775a19]">
                            The Craft
                        </a>
                        <a href="#pricing" className="text-sm tracking-wide text-[#4c4640] transition-colors hover:text-[#775a19]">
                            Memberships
                        </a>
                    </div>
                    {authed ? (
                        <Link
                            href={dashboard()}
                            className="bg-[#1e1b18] px-7 py-2.5 text-xs font-medium tracking-[0.2em] text-white uppercase transition-opacity hover:opacity-80"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <div className="flex items-center gap-3">
                            <Link href={login()} className="hidden text-sm tracking-wide text-[#4c4640] hover:text-[#775a19] sm:block">
                                Sign in
                            </Link>
                            <Link
                                href={register()}
                                className="bg-[#1e1b18] px-7 py-2.5 text-xs font-medium tracking-[0.2em] text-white uppercase transition-opacity hover:opacity-80"
                            >
                                Get started
                            </Link>
                        </div>
                    )}
                </nav>
            </header>

            {/* Hero */}
            <section className="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#1e1b18] px-5 py-32 md:px-16">
                <motion.div
                    className="absolute inset-0 bg-cover bg-center opacity-40 grayscale"
                    style={{ backgroundImage: 'url(/images/wedding/reception.jpg)' }}
                    initial={{ scale: 1.15 }}
                    animate={{ scale: 1 }}
                    transition={{ duration: 16, ease: 'easeOut' }}
                />
                <div className="absolute inset-0 bg-[#1e1b18]/40" />

                <motion.div
                    className="relative z-10 mx-auto max-w-4xl text-center"
                    initial={{ opacity: 0, y: 30 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                >
                    <span className="text-xs tracking-[0.3em] text-[#e9c176] uppercase">Curated Planning</span>
                    <h1 className={`${serif} mt-6 text-5xl leading-[1.05] text-white sm:text-7xl md:text-8xl`}>
                        A wedding, composed.
                    </h1>
                    <p className="mx-auto mt-6 max-w-xl text-lg text-[#e9e1dc]/80">
                        The private studio for your most personal day — architectural precision with the soul of a
                        storyteller.
                    </p>
                    <div className="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Link
                            href={authed ? dashboard() : register()}
                            className="bg-[#775a19] px-10 py-4 text-xs font-medium tracking-[0.25em] text-white uppercase transition-colors hover:bg-[#fed488] hover:text-[#1e1b18]"
                        >
                            {authed ? 'Open your studio' : 'Begin your composition'}
                        </Link>
                        <a
                            href={DEMO}
                            className="border border-white/30 px-10 py-4 text-xs font-medium tracking-[0.25em] text-white uppercase transition-colors hover:bg-white hover:text-[#1e1b18]"
                        >
                            View a live site
                        </a>
                    </div>
                </motion.div>

                <motion.div
                    className="absolute bottom-10 left-1/2 z-10 -translate-x-1/2 text-white/50"
                    animate={{ y: [0, 10, 0] }}
                    transition={{ duration: 1.8, repeat: Infinity, ease: 'easeInOut' }}
                >
                    <ChevronDown className="size-6" />
                </motion.div>
            </section>

            {/* The Atelier Experience */}
            <section id="experience" className="overflow-hidden bg-[#faf2ec] px-5 py-24 md:px-16 md:py-40">
                <div className="mx-auto grid max-w-[1440px] items-center gap-16 md:grid-cols-12">
                    <div className="md:col-span-5">
                        <Reveal>
                            <h2 className={`${serif} text-4xl tracking-tight text-[#1e1b18] uppercase sm:text-5xl`}>
                                The Atelier Experience
                            </h2>
                            <div className="mt-6 h-px w-24 bg-gradient-to-r from-[#775a19]/60 to-transparent" />
                            <p className="mt-6 text-lg leading-relaxed text-[#4c4640]">
                                A workspace designed for clarity. Walk through your floor plan, curate every tactile
                                detail, and keep your whole team in step — in one calm, digital sanctuary.
                            </p>
                        </Reveal>
                        <Stagger className="mt-12 space-y-8">
                            {experience.map((e) => (
                                <StaggerItem key={e.title} className="flex items-start gap-4">
                                    <e.icon className="mt-1 size-5 shrink-0 text-[#775a19]" />
                                    <div>
                                        <h4 className="text-sm font-bold tracking-wide text-[#1e1b18]">{e.title}</h4>
                                        <p className="mt-1 text-[#4c4640]">{e.body}</p>
                                    </div>
                                </StaggerItem>
                            ))}
                        </Stagger>
                    </div>
                    <Reveal delay={0.15} className="md:col-span-7">
                        <div className="relative">
                            <div className="relative z-10 bg-white p-4 shadow-2xl">
                                <img
                                    src="/images/wedding/venue.jpg"
                                    alt="A venue laid out in the Atelier"
                                    className="aspect-video w-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                            <div className="absolute -top-8 -right-8 z-0 hidden size-full border border-[#cec5bd]/40 md:block" />
                        </div>
                    </Reveal>
                </div>
            </section>

            {/* The Craft — feature bento */}
            <section id="craft" className="bg-[#fff8f3] px-5 py-24 md:px-16 md:py-32">
                <div className="mx-auto max-w-[1440px]">
                    <Reveal className="mb-20 text-center">
                        <h2 className={`${serif} text-4xl text-[#1e1b18] sm:text-5xl`}>Refined Utilitarianism</h2>
                        <p className="mt-4 text-xs tracking-[0.2em] text-[#775a19] uppercase">The tools of the craft</p>
                    </Reveal>

                    <Stagger className="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <StaggerItem className="group flex flex-col justify-between border border-[#cec5bd]/30 bg-[#f4ece6] p-12 transition-colors hover:border-[#775a19] md:col-span-2">
                            <Wallet className="size-9 text-[#1e1b18]/30 transition-colors group-hover:text-[#775a19]" />
                            <div className="mt-20">
                                <h3 className={`${serif} mb-3 text-2xl`}>Financial Composition</h3>
                                <p className="text-[#4c4640]">
                                    A transparent, itemised approach to your wedding investment — estimates, actuals, and
                                    what is paid, in balance.
                                </p>
                            </div>
                        </StaggerItem>

                        <StaggerItem className="group flex flex-col justify-between border border-[#cec5bd]/30 bg-[#efe7e0] p-12 transition-colors hover:border-[#775a19]">
                            <Users className="size-9 text-[#1e1b18]/30 transition-colors group-hover:text-[#775a19]" />
                            <div className="mt-12">
                                <h3 className="mb-3 text-sm font-bold tracking-widest uppercase">Guest Matrix</h3>
                                <p className="text-sm text-[#4c4640]">Intelligent RSVP tracking and a guest portal.</p>
                            </div>
                        </StaggerItem>

                        <StaggerItem className="group flex flex-col justify-between border border-[#cec5bd]/30 bg-[#faf2ec] p-12 transition-colors hover:border-[#775a19]">
                            <Armchair className="size-9 text-[#1e1b18]/30 transition-colors group-hover:text-[#775a19]" />
                            <div className="mt-12">
                                <h3 className="mb-3 text-sm font-bold tracking-widest uppercase">Seating Cartography</h3>
                                <p className="text-sm text-[#4c4640]">Drag-and-drop floor plans, chair by chair.</p>
                            </div>
                        </StaggerItem>

                        <StaggerItem className="group flex flex-col justify-between border border-[#cec5bd]/30 bg-[#e9e1db] p-12 transition-colors hover:border-[#775a19] md:col-span-2">
                            <div className="flex items-start justify-between">
                                <CalendarClock className="size-9 text-[#1e1b18]/30 transition-colors group-hover:text-[#775a19]" />
                                <span className="border border-[#775a19] px-3 py-1 text-xs tracking-wider text-[#775a19] uppercase">
                                    Real-time
                                </span>
                            </div>
                            <div className="mt-20">
                                <h3 className={`${serif} mb-3 text-2xl`}>The Master Timeline</h3>
                                <p className="text-[#4c4640]">
                                    A synchronised clock for you, your planner, and every vendor — every minute
                                    choreographed.
                                </p>
                            </div>
                        </StaggerItem>

                        <StaggerItem className="group relative col-span-1 h-[300px] overflow-hidden md:col-span-2">
                            <img
                                src="/images/wedding/table-setting.jpg"
                                alt="Attention to detail"
                                loading="lazy"
                                className="size-full object-cover transition-transform duration-700 group-hover:scale-105"
                            />
                            <div className="absolute inset-0 bg-[#1e1b18]/30 transition-colors group-hover:bg-[#1e1b18]/10" />
                            <p className={`${serif} absolute bottom-8 left-8 text-2xl text-white`}>Attention to detail.</p>
                        </StaggerItem>
                    </Stagger>
                </div>
            </section>

            {/* Memberships */}
            <section id="pricing" className="bg-[#faf2ec] px-5 py-24 md:px-16 md:py-40">
                <div className="mx-auto max-w-[1440px]">
                    <Reveal className="mb-20">
                        <h2 className={`${serif} text-4xl text-[#1e1b18] sm:text-5xl`}>Studio Memberships</h2>
                        <p className="mt-2 text-lg text-[#4c4640]">Choose the level of support your journey requires.</p>
                    </Reveal>

                    <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                        {tiers.map((tier) => (
                            <div
                                key={tier.name}
                                className={`flex flex-col justify-between p-12 ${
                                    tier.featured
                                        ? 'relative bg-[#1e1b18] text-white shadow-xl md:-translate-y-4'
                                        : 'border border-[#cec5bd]/30 bg-[#fff8f3]'
                                }`}
                            >
                                {tier.featured && (
                                    <div className="absolute top-0 right-0 bg-[#775a19] px-6 py-2 text-xs tracking-widest text-white uppercase">
                                        Recommended
                                    </div>
                                )}
                                <div>
                                    <h3
                                        className={`mb-2 text-sm font-bold tracking-widest uppercase ${
                                            tier.featured ? 'text-[#e9c176]' : 'text-[#1e1b18]'
                                        }`}
                                    >
                                        {tier.name}
                                    </h3>
                                    <div className="mb-8 flex items-baseline gap-2">
                                        <span className={`${serif} text-4xl`}>{tier.price}</span>
                                        <span className={tier.featured ? 'text-sm text-[#e9e1dc]/70' : 'text-sm text-[#4c4640]'}>
                                            {tier.cadence}
                                        </span>
                                    </div>
                                    <ul className="mb-12 space-y-4">
                                        {tier.features.map((f) => (
                                            <li key={f} className="flex items-center gap-3 text-sm">
                                                <Check className={`size-4 ${tier.featured ? 'text-[#e9c176]' : 'text-[#775a19]'}`} />
                                                <span className={tier.featured ? 'text-[#e9e1dc]' : 'text-[#4c4640]'}>{f}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <Link
                                    href={register()}
                                    className={`py-4 text-center text-xs font-medium tracking-widest uppercase transition-all ${
                                        tier.featured
                                            ? 'bg-[#775a19] text-white hover:bg-[#fed488] hover:text-[#1e1b18]'
                                            : 'border border-[#1e1b18] text-[#1e1b18] hover:bg-[#1e1b18] hover:text-white'
                                    }`}
                                >
                                    {tier.cta}
                                </Link>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Final CTA */}
            <section className="bg-[#fff8f3] px-5 py-28 text-center md:py-40">
                <Reveal className="mx-auto max-w-2xl">
                    <Sparkles className="mx-auto mb-6 size-8 text-[#775a19]" />
                    <h2 className={`${serif} mb-6 text-4xl sm:text-5xl`}>Begin your composition.</h2>
                    <p className="mb-12 text-lg text-[#4c4640]">
                        Your personal atelier is ready. Start creating a day that is unmistakably yours.
                    </p>
                    <Link
                        href={authed ? dashboard() : register()}
                        className="inline-flex items-center gap-3 bg-[#1e1b18] px-14 py-5 text-xs font-medium tracking-[0.3em] text-white uppercase transition-colors hover:bg-[#775a19]"
                    >
                        {authed ? 'Open your studio' : 'Create account'}
                        <ArrowRight className="size-4" />
                    </Link>
                </Reveal>
            </section>

            {/* Footer */}
            <footer className="border-t border-[#cec5bd]/30 bg-[#fff8f3] py-12">
                <div className="mx-auto flex max-w-[1440px] flex-col items-center justify-between gap-6 px-5 md:flex-row md:px-16">
                    <span className={`${serif} text-2xl text-[#1e1b18]`}>WedFlow Atelier</span>
                    <p className="text-xs tracking-[0.15em] text-[#4c4640]/70 uppercase">
                        © {new Date().getFullYear()} WedFlow Atelier. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}

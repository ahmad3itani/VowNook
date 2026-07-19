import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Reveal } from '@/components/motion/reveal';

const fraunces = "font-['Newsreader']";

/**
 * Shared chrome for secondary public pages (terms, privacy, contact) — the
 * same editorial header/footer as the landing and how-it-works pages.
 */
export function PublicPageShell({
    title,
    description,
    eyebrow,
    heading,
    headingAccent,
    intro,
    children,
}: {
    title: string;
    description: string;
    eyebrow: string;
    heading: string;
    headingAccent: string;
    intro?: string;
    children: ReactNode;
}) {
    return (
        <div className="min-h-screen bg-[#f1f0ea] font-['Instrument_Sans'] text-[#0f1c17] antialiased selection:bg-[#7fb79e]/40">
            {/* Description/canonical/OG are server-rendered in the blade head. */}
            <Head title={title} />

            <header className="fixed inset-x-0 top-0 z-50 border-b border-[#0f1c17]/8 bg-[#f1f0ea]/85 backdrop-blur-md">
                <nav className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-4 md:px-12">
                    <Link href="/" className={`${fraunces} text-[22px] font-medium tracking-tight`}>
                        VowNook <span className="italic font-light text-[#1f5142]">Atelier</span>
                    </Link>
                    <div className="hidden items-center gap-9 md:flex">
                        <Link href="/marketplace" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">Marketplace</Link>
                        <Link href="/how-it-works" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">How it works</Link>
                        <Link href="/contact" className="text-[13px] tracking-wide text-[#4b5850] hover:text-[#1f5142]">Contact</Link>
                    </div>
                    <Link
                        href="/register"
                        className="bg-[#0f1c17] px-6 py-2.5 text-[11px] font-medium tracking-[0.18em] text-[#f1f0ea] uppercase transition-colors hover:bg-[#1f5142]"
                    >
                        Get started
                    </Link>
                </nav>
            </header>

            <section className="px-5 pt-36 pb-12 md:px-12 md:pt-44 md:pb-16">
                <div className="mx-auto max-w-[900px]">
                    <Reveal>
                        <p className="mb-4 text-[11px] tracking-[0.3em] text-[#1f5142] uppercase">{eyebrow}</p>
                        <h1 className={`${fraunces} text-5xl leading-[1.02] font-light sm:text-6xl`}>
                            {heading} <em className="text-[#1f5142]">{headingAccent}</em>
                        </h1>
                        {intro && (
                            <p className="mt-6 max-w-xl text-[15px] leading-relaxed text-[#4b5850]">{intro}</p>
                        )}
                    </Reveal>
                </div>
            </section>

            <main className="border-t border-[#0f1c17]/10 px-5 py-14 md:px-12 md:py-20">
                <div className="mx-auto max-w-[900px]">{children}</div>
            </main>

            <footer className="border-t border-[#0f1c17]/10 bg-[#f1f0ea] py-12">
                <div className="mx-auto flex max-w-[1480px] flex-col items-center justify-between gap-6 px-5 md:flex-row md:px-12">
                    <span className={`${fraunces} text-xl`}>
                        VowNook <span className="italic text-[#1f5142]">Atelier</span>
                    </span>
                    <div className="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-[13px] text-[#4b5850]">
                        <Link href="/marketplace" className="hover:text-[#1f5142]">Marketplace</Link>
                        <Link href="/how-it-works" className="hover:text-[#1f5142]">How it works</Link>
                        <Link href="/terms" className="hover:text-[#1f5142]">Terms</Link>
                        <Link href="/privacy" className="hover:text-[#1f5142]">Privacy</Link>
                        <Link href="/marketplace-rules" className="hover:text-[#1f5142]">Marketplace Rules</Link>
                        <Link href="/vendor-agreement" className="hover:text-[#1f5142]">Vendor Agreement</Link>
                        <Link href="/contact" className="hover:text-[#1f5142]">Contact</Link>
                    </div>
                    <p className="text-[11px] tracking-[0.15em] text-[#4b5850]/70 uppercase">
                        © {new Date().getFullYear()} VowNook
                    </p>
                </div>
            </footer>
        </div>
    );
}

/** A numbered legal/document section in the editorial style. */
export function DocSection({ n, title, children }: { n: string; title: string; children: ReactNode }) {
    return (
        <section className="mb-12">
            <div className="mb-4 flex items-baseline gap-4">
                <span className={`${fraunces} text-2xl font-light text-[#1f5142]/50`}>{n}</span>
                <h2 className={`${fraunces} text-2xl font-light sm:text-3xl`}>{title}</h2>
            </div>
            <div className="space-y-3 pl-0 text-[15px] leading-relaxed text-[#4b5850] sm:pl-12 [&_li]:flex [&_li]:items-start [&_li]:gap-3 [&_strong]:font-semibold [&_strong]:text-[#0f1c17] [&_ul]:space-y-2">
                {children}
            </div>
        </section>
    );
}

/** Bullet with the gold dot used across the public pages. */
export function Dot() {
    return <span className="mt-2 size-1 shrink-0 rounded-full bg-[#1f5142]" />;
}

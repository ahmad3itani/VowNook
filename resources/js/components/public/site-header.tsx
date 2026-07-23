import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Menu, X } from 'lucide-react';
import { useState } from 'react';

const fraunces = "font-['Newsreader']";

/**
 * The one public header for the whole marketing + marketplace surface.
 *
 * Every page used to roll its own — the homepage even navigated with anchor
 * links (#couples, #suite) that broke on every other page, and the link set
 * differed page to page. This is the single source of truth, so the nav is
 * identical everywhere. It reads auth from the Inertia page props, so the
 * primary action is correct (Get started vs Open studio) without threading a
 * prop through every page.
 *
 * The value strip above the nav states the three real differentiators on every
 * page — the cheapest, most honest "why us" we can show, and it reinforces the
 * brand promise before a visitor has scrolled anything.
 */

type Auth = { user?: { account_type?: string } | null };

const NAV: { label: string; href: string }[] = [
    { label: 'Marketplace', href: '/marketplace' },
    { label: 'Guides', href: '/blog' },
    { label: 'Features', href: '/features' },
    { label: 'How it works', href: '/how-it-works' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'Shop', href: '/shop' },
];

export function SiteHeader() {
    const [open, setOpen] = useState(false);
    const { auth } = usePage<{ auth?: Auth }>().props;
    const authed = !!auth?.user;
    const primaryHref = authed ? '/dashboard' : '/register';
    const primaryLabel = authed ? 'Open studio' : 'Get started';

    return (
        <header className="sticky top-0 z-50">
            {/* Value strip — the three differentiators, on every page. */}
            <div className="bg-[#0f2a21] text-[#e4e9e4]">
                <div className="mx-auto flex max-w-[1480px] items-center justify-center gap-x-6 gap-y-1 px-5 py-2 text-center text-[11px] tracking-[0.12em] md:px-12">
                    <span>Plan your wedding <strong className="font-semibold text-white">free</strong> — no card</span>
                    <span aria-hidden className="hidden text-white/25 sm:inline">·</span>
                    <span className="hidden sm:inline">Real Ontario prices, <strong className="font-semibold text-white">by city</strong></span>
                    <span aria-hidden className="hidden text-white/25 lg:inline">·</span>
                    <span className="hidden lg:inline">Reviews tied to <strong className="font-semibold text-white">real bookings</strong></span>
                </div>
            </div>

            {/* Main bar */}
            <div className="border-b border-[#0f1c17]/8 bg-[#f1f0ea]/85 backdrop-blur-md">
                <div className="mx-auto flex max-w-[1480px] items-center justify-between px-5 py-3.5 md:px-12">
                    <Link href="/" className="flex items-center gap-2.5" aria-label="VowNook home">
                        <img
                            src="/images/brand/logo-mark.svg"
                            alt=""
                            className="size-8 rounded-md border border-[#0f1c17]/10"
                        />
                        <span className={`${fraunces} text-[21px] font-medium tracking-tight text-[#0f1c17]`}>
                            VowNook
                        </span>
                    </Link>

                    {/* Desktop nav */}
                    <nav className="hidden items-center gap-7 lg:flex">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="text-[13px] tracking-wide text-[#4b5850] transition-colors hover:text-[#1b4638]"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-3">
                        {!authed && (
                            <Link
                                href="/login"
                                className="hidden text-[13px] text-[#4b5850] transition-colors hover:text-[#1b4638] sm:block"
                            >
                                Sign in
                            </Link>
                        )}
                        <Link
                            href={primaryHref}
                            className="cta-press group flex items-center gap-2 px-5 py-2.5 text-[11px] font-semibold tracking-[0.16em] uppercase"
                        >
                            <span className="relative z-10 flex items-center gap-2">
                                {primaryLabel}
                                <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </Link>
                        <button
                            type="button"
                            onClick={() => setOpen((o) => !o)}
                            aria-label={open ? 'Close menu' : 'Open menu'}
                            aria-expanded={open}
                            className="flex size-9 items-center justify-center rounded-md border border-[#0f1c17]/15 text-[#0f1c17] lg:hidden"
                        >
                            {open ? <X className="size-5" /> : <Menu className="size-5" />}
                        </button>
                    </div>
                </div>

                {/* Mobile dropdown */}
                {open && (
                    <nav className="flex flex-col border-t border-[#0f1c17]/10 bg-[#f1f0ea] px-5 py-2 lg:hidden">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={() => setOpen(false)}
                                className="border-b border-[#0f1c17]/6 py-3 text-sm tracking-wide text-[#0f1c17] last:border-0"
                            >
                                {item.label}
                            </Link>
                        ))}
                        {!authed && (
                            <Link
                                href="/login"
                                onClick={() => setOpen(false)}
                                className="py-3 text-sm tracking-wide text-[#4b5850]"
                            >
                                Sign in
                            </Link>
                        )}
                    </nav>
                )}
            </div>
        </header>
    );
}

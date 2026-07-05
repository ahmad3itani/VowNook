import { Link } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useState } from 'react';

const fraunces = "font-['Fraunces']";

/**
 * Shared editorial header for the public marketplace surface (marketplace,
 * vendor profiles, programmatic city/category pages). Token-based so it adapts
 * to light/dark, with the Fraunces wordmark + gold accent for brand cohesion.
 * Collapses the nav into a tap-to-open menu on phones.
 */
export function SiteHeader() {
    const [open, setOpen] = useState(false);

    return (
        <header className="sticky top-0 z-40 border-b border-border bg-background/85 backdrop-blur-md">
            <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 md:px-6">
                <Link href="/" className="flex items-center gap-2.5" aria-label="VowNook home">
                    <img src="/images/brand/logo-mark.svg" alt="" className="size-8 rounded-md border border-border" />
                    <span className={`${fraunces} text-xl font-medium tracking-tight`}>VowNook</span>
                </Link>

                {/* Desktop nav */}
                <nav className="hidden items-center gap-6 text-sm sm:flex">
                    <Link href="/marketplace" className="text-muted-foreground transition-colors hover:text-foreground">
                        Marketplace
                    </Link>
                    <a href="/shop" className="text-muted-foreground transition-colors hover:text-foreground">
                        Shop
                    </a>
                    <Link href="/features" className="text-muted-foreground transition-colors hover:text-foreground">
                        Features
                    </Link>
                    <Link href="/pricing" className="text-muted-foreground transition-colors hover:text-foreground">
                        Pricing
                    </Link>
                    <Link href="/login" className="text-muted-foreground transition-colors hover:text-foreground">
                        Sign in
                    </Link>
                    <Link
                        href="/register"
                        className="rounded-full bg-foreground px-5 py-2 text-[11px] font-semibold tracking-[0.15em] text-background uppercase transition-colors hover:bg-[#8a651c] hover:text-white"
                    >
                        Get started
                    </Link>
                </nav>

                {/* Mobile: primary CTA stays visible + hamburger toggles the rest */}
                <div className="flex items-center gap-2 sm:hidden">
                    <Link
                        href="/register"
                        className="rounded-full bg-foreground px-4 py-2 text-[11px] font-semibold tracking-[0.15em] text-background uppercase"
                    >
                        Get started
                    </Link>
                    <button
                        type="button"
                        onClick={() => setOpen((o) => !o)}
                        aria-label={open ? 'Close menu' : 'Open menu'}
                        aria-expanded={open}
                        className="flex size-9 items-center justify-center rounded-full border border-border text-foreground"
                    >
                        {open ? <X className="size-5" /> : <Menu className="size-5" />}
                    </button>
                </div>
            </div>

            {/* Mobile dropdown */}
            {open && (
                <nav className="flex flex-col border-t border-border bg-background px-4 py-2 sm:hidden">
                    <Link href="/marketplace" onClick={() => setOpen(false)} className="py-2.5 text-sm text-foreground">
                        Marketplace
                    </Link>
                    <a href="/shop" onClick={() => setOpen(false)} className="py-2.5 text-sm text-foreground">
                        Shop
                    </a>
                    <Link href="/features" onClick={() => setOpen(false)} className="py-2.5 text-sm text-foreground">
                        Features
                    </Link>
                    <Link href="/pricing" onClick={() => setOpen(false)} className="py-2.5 text-sm text-foreground">
                        Pricing
                    </Link>
                    <Link href="/login" onClick={() => setOpen(false)} className="py-2.5 text-sm text-foreground">
                        Sign in
                    </Link>
                </nav>
            )}
        </header>
    );
}

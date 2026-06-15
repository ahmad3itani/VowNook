import { Link } from '@inertiajs/react';

const fraunces = "font-['Fraunces']";

/**
 * Shared editorial header for the public marketplace surface (marketplace,
 * vendor profiles, programmatic city/category pages). Token-based so it adapts
 * to light/dark, with the Fraunces wordmark + gold accent for brand cohesion.
 */
export function SiteHeader() {
    return (
        <header className="sticky top-0 z-40 border-b border-border bg-background/85 backdrop-blur-md">
            <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 md:px-6">
                <Link href="/" className={`${fraunces} text-xl font-medium tracking-tight`}>
                    VowNook <span className="font-light text-[#8a651c] italic">Atelier</span>
                </Link>
                <nav className="flex items-center gap-4 text-sm sm:gap-6">
                    <Link href="/marketplace" className="hidden text-muted-foreground transition-colors hover:text-foreground sm:block">
                        Marketplace
                    </Link>
                    <Link href="/how-it-works" className="hidden text-muted-foreground transition-colors hover:text-foreground sm:block">
                        How it works
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
            </div>
        </header>
    );
}

import { type ReactNode } from 'react';

/**
 * The marketing site's structural signature.
 *
 * A left column carries a chapter number and a short italic label while the
 * content sits offset in the right column. Every public section uses it, so the
 * site reads as one composed system instead of a stack of centred blocks —
 * centred heading over centred body over centred button, repeated down a page,
 * is the strongest "generated template" signal there is. The consistent left
 * edge is what makes a page feel typeset.
 *
 * The rail is decorative wayfinding, so it hides below lg rather than stacking
 * a number above every section on mobile.
 */
export function Rail({
    n,
    label,
    children,
    className = '',
    tone = 'light',
}: {
    n: string;
    label: ReactNode;
    children: ReactNode;
    className?: string;
    tone?: 'light' | 'dark';
}) {
    const muted = tone === 'dark' ? 'text-white/55' : 'text-[#4b5850]';

    return (
        <div
            className={`mx-auto grid max-w-[1480px] gap-8 lg:grid-cols-[7rem_minmax(0,1fr)] lg:gap-12 ${className}`}
        >
            <div className="hidden lg:block">
                <div className="sticky top-32">
                    <span
                        aria-hidden
                        className={`block h-px w-12 ${tone === 'dark' ? 'bg-[#7fb79e]' : 'bg-[#c4502e]'}`}
                    />
                    <p className={`eyebrow mt-4 ${muted}`}>{n}</p>
                    <p className={`mt-2 font-['Newsreader'] text-[15px] leading-snug italic ${muted}`}>
                        {label}
                    </p>
                </div>
            </div>
            <div className="min-w-0">{children}</div>
        </div>
    );
}

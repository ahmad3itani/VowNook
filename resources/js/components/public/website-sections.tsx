import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export type PartyMember = {
    id: number;
    name: string;
    role: string | null;
    side: string;
    bio: string | null;
    photo_url: string | null;
};
export type FaqItem = { question: string; answer: string };
export type LocalItem = {
    title: string;
    category: string | null;
    description: string | null;
    url?: string | null;
};
export type GuestbookItem = {
    id: number;
    name: string;
    message: string;
    date: string | null;
};

const serif = "font-['Playfair_Display']";

/** Public "Wedding party" section — the people standing beside the couple. */
export function WebsiteParty({ party }: { party: PartyMember[] }) {
    if (party.length === 0) {
        return null;
    }

    return (
        <section
            id="party"
            className="px-6 py-24 md:py-32"
            style={{ background: 'var(--c-bg)' }}
        >
            <div className="mx-auto max-w-5xl">
                <p
                    className="text-center text-xs tracking-[0.3em] uppercase"
                    style={{ color: 'var(--c-primary)' }}
                >
                    The people we love
                </p>
                <h2
                    className={`${serif} mt-3 text-center text-4xl sm:text-5xl`}
                    style={{ color: 'var(--c-text)' }}
                >
                    Our Wedding Party
                </h2>

                <div className="mt-14 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    {party.map((m) => (
                        <div
                            key={m.id}
                            className="flex flex-col items-center text-center"
                        >
                            {m.photo_url ? (
                                <img
                                    src={m.photo_url}
                                    alt={m.name}
                                    loading="lazy"
                                    className="size-32 rounded-full object-cover grayscale transition-all duration-700 hover:grayscale-0"
                                    style={{
                                        boxShadow: '0 0 0 1px var(--c-border)',
                                    }}
                                />
                            ) : (
                                <div
                                    className={`${serif} flex size-32 items-center justify-center rounded-full text-3xl`}
                                    style={{
                                        background: 'var(--c-surface)',
                                        color: 'var(--c-primary)',
                                        boxShadow: '0 0 0 1px var(--c-border)',
                                    }}
                                >
                                    {m.name.slice(0, 1)}
                                </div>
                            )}
                            <h3
                                className={`${serif} mt-5 text-2xl`}
                                style={{ color: 'var(--c-text)' }}
                            >
                                {m.name}
                            </h3>
                            {m.role && (
                                <p
                                    className="mt-1 text-xs tracking-[0.18em] uppercase"
                                    style={{ color: 'var(--c-primary)' }}
                                >
                                    {m.role}
                                </p>
                            )}
                            {m.bio && (
                                <p
                                    className="mt-3 max-w-xs text-sm leading-relaxed"
                                    style={{ color: 'var(--c-muted)' }}
                                >
                                    {m.bio}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

/** Public "Things to do" section — local recommendations for out-of-town guests. */
export function WebsiteLocalGuide({ items }: { items: LocalItem[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <section
            id="local"
            className="px-6 py-24 md:py-32"
            style={{ background: 'var(--c-bg)' }}
        >
            <div className="mx-auto max-w-4xl">
                <p
                    className="text-center text-xs tracking-[0.3em] uppercase"
                    style={{ color: 'var(--c-primary)' }}
                >
                    While you're in town
                </p>
                <h2
                    className={`${serif} mt-3 text-center text-4xl sm:text-5xl`}
                    style={{ color: 'var(--c-text)' }}
                >
                    Things to Do
                </h2>

                <div className="mt-12 grid gap-6 sm:grid-cols-2">
                    {items.map((r, i) => (
                        <div
                            key={i}
                            className="rounded-2xl border p-6"
                            style={{
                                borderColor: 'var(--c-border)',
                                background: 'var(--c-surface)',
                            }}
                        >
                            {r.category && (
                                <p
                                    className="text-[11px] tracking-[0.2em] uppercase"
                                    style={{ color: 'var(--c-primary)' }}
                                >
                                    {r.category}
                                </p>
                            )}
                            <h3
                                className={`${serif} mt-1 text-2xl`}
                                style={{ color: 'var(--c-text)' }}
                            >
                                {r.title}
                            </h3>
                            {r.description && (
                                <p
                                    className="mt-2 text-sm leading-relaxed"
                                    style={{ color: 'var(--c-muted)' }}
                                >
                                    {r.description}
                                </p>
                            )}
                            {r.url && (
                                <a
                                    href={r.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-3 inline-block text-xs font-semibold tracking-wide uppercase"
                                    style={{ color: 'var(--c-primary)' }}
                                >
                                    Learn more →
                                </a>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

/** Public FAQ section — a simple tap-to-expand accordion. */
export function WebsiteFaq({ items }: { items: FaqItem[] }) {
    const [open, setOpen] = useState<number | null>(0);

    if (items.length === 0) {
        return null;
    }

    return (
        <section
            id="faq"
            className="px-6 py-24 md:py-32"
            style={{ background: 'var(--c-surface)' }}
        >
            <div className="mx-auto max-w-3xl">
                <p
                    className="text-center text-xs tracking-[0.3em] uppercase"
                    style={{ color: 'var(--c-primary)' }}
                >
                    Good to know
                </p>
                <h2
                    className={`${serif} mt-3 text-center text-4xl sm:text-5xl`}
                    style={{ color: 'var(--c-text)' }}
                >
                    Questions &amp; Answers
                </h2>

                <div className="mt-12 flex flex-col gap-3">
                    {items.map((f, i) => (
                        <div
                            key={i}
                            className="overflow-hidden rounded-xl border"
                            style={{
                                borderColor: 'var(--c-border)',
                                background: 'var(--c-bg)',
                            }}
                        >
                            <button
                                type="button"
                                onClick={() =>
                                    setOpen((o) => (o === i ? null : i))
                                }
                                className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                                aria-expanded={open === i}
                            >
                                <span
                                    className={`${serif} text-lg`}
                                    style={{ color: 'var(--c-text)' }}
                                >
                                    {f.question}
                                </span>
                                <span
                                    className="shrink-0 text-xl leading-none"
                                    style={{ color: 'var(--c-primary)' }}
                                >
                                    {open === i ? '–' : '+'}
                                </span>
                            </button>
                            {open === i && f.answer && (
                                <p
                                    className="px-5 pb-5 text-sm leading-relaxed whitespace-pre-line"
                                    style={{ color: 'var(--c-muted)' }}
                                >
                                    {f.answer}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

/**
 * Public guestbook — approved well-wishes plus a "leave a message" form. New
 * messages are held for the couple to approve before they appear here.
 */
export function WebsiteGuestbook({
    entries,
    slug,
}: {
    entries: GuestbookItem[];
    slug: string;
}) {
    const form = useForm({ name: '', message: '' });
    const [sent, setSent] = useState(false);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(`/w/${slug}/guestbook`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setSent(true);
            },
        });
    }

    return (
        <section
            id="guestbook"
            className="px-6 py-24 md:py-32"
            style={{ background: 'var(--c-bg)' }}
        >
            <div className="mx-auto max-w-3xl">
                <p
                    className="text-center text-xs tracking-[0.3em] uppercase"
                    style={{ color: 'var(--c-primary)' }}
                >
                    Share the love
                </p>
                <h2
                    className={`${serif} mt-3 text-center text-4xl sm:text-5xl`}
                    style={{ color: 'var(--c-text)' }}
                >
                    Guestbook
                </h2>

                {entries.length > 0 && (
                    <div className="mt-12 columns-1 gap-5 sm:columns-2">
                        {entries.map((g) => (
                            <div
                                key={g.id}
                                className="mb-5 break-inside-avoid rounded-2xl border p-6"
                                style={{
                                    borderColor: 'var(--c-border)',
                                    background: 'var(--c-surface)',
                                }}
                            >
                                <p
                                    className="text-sm leading-relaxed italic"
                                    style={{ color: 'var(--c-text)' }}
                                >
                                    “{g.message}”
                                </p>
                                <p
                                    className="mt-3 text-xs tracking-[0.18em] uppercase"
                                    style={{ color: 'var(--c-primary)' }}
                                >
                                    {g.name}
                                    {g.date ? ` · ${g.date}` : ''}
                                </p>
                            </div>
                        ))}
                    </div>
                )}

                <div
                    className="mx-auto mt-12 max-w-xl rounded-2xl border p-7"
                    style={{
                        borderColor: 'var(--c-border)',
                        background: 'var(--c-surface)',
                    }}
                >
                    {sent ? (
                        <p
                            className="text-center text-sm leading-relaxed"
                            style={{ color: 'var(--c-muted)' }}
                        >
                            Thank you — your message has been sent to the couple
                            and will appear once they approve it. 💛
                        </p>
                    ) : (
                        <form onSubmit={submit} className="flex flex-col gap-4">
                            <p
                                className="text-center text-xs tracking-[0.25em] uppercase"
                                style={{ color: 'var(--c-primary)' }}
                            >
                                Leave a well-wish
                            </p>
                            <input
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="Your name"
                                required
                                maxLength={120}
                                className="rounded-lg border bg-transparent px-4 py-2.5 text-sm outline-none"
                                style={{
                                    borderColor: 'var(--c-border)',
                                    color: 'var(--c-text)',
                                }}
                            />
                            {form.errors.name && (
                                <p className="text-xs text-red-600">
                                    {form.errors.name}
                                </p>
                            )}
                            <textarea
                                value={form.data.message}
                                onChange={(e) =>
                                    form.setData('message', e.target.value)
                                }
                                placeholder="Share a memory or a message for the couple…"
                                required
                                rows={4}
                                maxLength={2000}
                                className="rounded-lg border bg-transparent px-4 py-2.5 text-sm outline-none"
                                style={{
                                    borderColor: 'var(--c-border)',
                                    color: 'var(--c-text)',
                                }}
                            />
                            {form.errors.message && (
                                <p className="text-xs text-red-600">
                                    {form.errors.message}
                                </p>
                            )}
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="self-center px-10 py-3 text-xs font-medium tracking-[0.25em] text-white uppercase transition-opacity hover:opacity-85 disabled:opacity-60"
                                style={{ background: 'var(--c-dark)' }}
                            >
                                {form.processing
                                    ? 'Sending…'
                                    : 'Sign the guestbook'}
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </section>
    );
}

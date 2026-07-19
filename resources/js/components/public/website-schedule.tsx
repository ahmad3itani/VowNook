export type EventData = {
    id: number;
    name: string;
    type: string;
    date: string | null;
    start_time: string | null;
    end_time: string | null;
    venue_name: string | null;
    address: string | null;
    dress_code: string | null;
    description: string | null;
    is_rsvpable: boolean;
};

const serif = "font-['Newsreader']";

const TYPE_LABEL: Record<string, string> = {
    ceremony: 'Ceremony',
    reception: 'Reception',
    rehearsal: 'Rehearsal dinner',
    welcome: 'Welcome party',
    brunch: 'Farewell brunch',
    party: 'After-party',
    other: 'Celebration',
};

/** Public "Events" / celebration-weekend schedule on the wedding website. */
export function WebsiteSchedule({ events }: { events: EventData[] }) {
    if (events.length === 0) {
        return null;
    }

    return (
        <section id="events" className="px-6 py-24 md:py-32" style={{ background: 'var(--c-bg)' }}>
            <div className="mx-auto max-w-3xl">
                <p className="text-center text-xs tracking-[0.3em] uppercase" style={{ color: 'var(--c-primary)' }}>
                    Join us
                </p>
                <h2 className={`${serif} mt-3 text-center text-4xl sm:text-5xl`} style={{ color: 'var(--c-text)' }}>
                    The Celebration
                </h2>

                <div className="mt-14 space-y-6">
                    {events.map((ev) => (
                        <div
                            key={ev.id}
                            className="rounded-2xl border p-6 sm:p-8"
                            style={{ borderColor: 'var(--c-border)', background: 'var(--c-surface)' }}
                        >
                            <p className="text-xs tracking-[0.25em] uppercase" style={{ color: 'var(--c-primary)' }}>
                                {TYPE_LABEL[ev.type] ?? 'Celebration'}
                            </p>
                            <h3 className={`${serif} mt-2 text-2xl sm:text-3xl`} style={{ color: 'var(--c-text)' }}>
                                {ev.name}
                            </h3>

                            <div className="mt-4 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2" style={{ color: 'var(--c-muted)' }}>
                                {ev.date && (
                                    <p>
                                        <span className="font-medium" style={{ color: 'var(--c-text)' }}>When</span>
                                        {'  '}
                                        {ev.date}
                                        {ev.start_time ? ` · ${ev.start_time}` : ''}
                                        {ev.end_time ? `–${ev.end_time}` : ''}
                                    </p>
                                )}
                                {(ev.venue_name || ev.address) && (
                                    <p>
                                        <span className="font-medium" style={{ color: 'var(--c-text)' }}>Where</span>
                                        {'  '}
                                        {ev.venue_name}
                                        {ev.venue_name && ev.address ? ', ' : ''}
                                        {ev.address}
                                    </p>
                                )}
                                {ev.dress_code && (
                                    <p>
                                        <span className="font-medium" style={{ color: 'var(--c-text)' }}>Dress code</span>
                                        {'  '}
                                        {ev.dress_code}
                                    </p>
                                )}
                            </div>

                            {ev.description && (
                                <p className="mt-4 text-sm leading-relaxed" style={{ color: 'var(--c-muted)' }}>
                                    {ev.description}
                                </p>
                            )}

                            {ev.address && (
                                <a
                                    href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${ev.venue_name ?? ''} ${ev.address}`)}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-4 inline-block text-xs font-semibold tracking-wide uppercase underline"
                                    style={{ color: 'var(--c-primary)' }}
                                >
                                    View map
                                </a>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

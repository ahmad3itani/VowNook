export type Stay = {
    id: number;
    name: string;
    type: string;
    address: string | null;
    blurb: string | null;
    booking_url: string | null;
    block_code: string | null;
    price_note: string | null;
    distance_note: string | null;
    image_url: string | null;
};

export type TravelData = { notes: string | null; stays: Stay[] };

const serif = "font-['Playfair_Display']";

/** Public "Travel & Stays" section — hotel blocks, rentals, transport + notes. */
export function WebsiteTravel({ travel }: { travel: TravelData }) {
    const hasStays = travel.stays.length > 0;
    const hasNotes = !!travel.notes && travel.notes.trim().length > 0;

    if (!hasStays && !hasNotes) {
        return null;
    }

    return (
        <section id="travel" className="px-6 py-24 md:py-32" style={{ background: 'var(--c-surface)' }}>
            <div className="mx-auto max-w-4xl">
                <p className="text-center text-xs tracking-[0.3em] uppercase" style={{ color: 'var(--c-primary)' }}>
                    Getting here
                </p>
                <h2 className={`${serif} mt-3 text-center text-4xl sm:text-5xl`} style={{ color: 'var(--c-text)' }}>
                    Travel &amp; Stays
                </h2>

                {hasStays && (
                    <div className="mt-12 grid gap-6 sm:grid-cols-2">
                        {travel.stays.map((s) => (
                            <div
                                key={s.id}
                                className="overflow-hidden rounded-2xl border"
                                style={{ borderColor: 'var(--c-border)', background: 'var(--c-bg)' }}
                            >
                                {s.image_url && <img src={s.image_url} alt="" className="h-44 w-full object-cover" />}
                                <div className="space-y-2 p-6">
                                    <h3 className={`${serif} text-2xl`} style={{ color: 'var(--c-text)' }}>{s.name}</h3>
                                    {s.price_note && <p className="text-sm font-medium" style={{ color: 'var(--c-primary)' }}>{s.price_note}</p>}
                                    {s.blurb && <p className="text-sm leading-relaxed" style={{ color: 'var(--c-muted)' }}>{s.blurb}</p>}
                                    <div className="space-y-1 text-sm" style={{ color: 'var(--c-muted)' }}>
                                        {s.address && <p>{s.address}</p>}
                                        {s.distance_note && <p>{s.distance_note}</p>}
                                        {s.block_code && <p>Group code: <span className="font-medium" style={{ color: 'var(--c-text)' }}>{s.block_code}</span></p>}
                                    </div>
                                    <div className="flex flex-wrap gap-3 pt-2">
                                        {s.booking_url && (
                                            <a
                                                href={s.booking_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="rounded-full px-5 py-2 text-xs font-semibold tracking-wide text-white uppercase"
                                                style={{ background: 'var(--c-primary)' }}
                                            >
                                                Book
                                            </a>
                                        )}
                                        {s.address && (
                                            <a
                                                href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${s.name} ${s.address}`)}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="rounded-full border px-5 py-2 text-xs font-semibold tracking-wide uppercase"
                                                style={{ borderColor: 'var(--c-border)', color: 'var(--c-text)' }}
                                            >
                                                Map
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {hasNotes && (
                    <div className="mx-auto mt-12 max-w-2xl rounded-2xl border p-7 text-center" style={{ borderColor: 'var(--c-border)', background: 'var(--c-bg)' }}>
                        <p className="text-xs tracking-[0.25em] uppercase" style={{ color: 'var(--c-primary)' }}>Good to know</p>
                        <p className="mt-3 text-sm leading-relaxed whitespace-pre-line" style={{ color: 'var(--c-muted)' }}>
                            {travel.notes}
                        </p>
                    </div>
                )}
            </div>
        </section>
    );
}

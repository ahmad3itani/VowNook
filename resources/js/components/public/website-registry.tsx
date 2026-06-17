import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

export type RegistryFund = {
    id: number;
    title: string;
    blurb: string | null;
    type: string;
    goal_cents: number | null;
    raised_cents: number;
    payout_url: string | null;
    image_url: string | null;
};

export type RegistryItem = {
    id: number;
    name: string;
    blurb: string | null;
    price_cents: number | null;
    store_url: string | null;
    quantity: number;
    claimed_count: number;
    image_url: string | null;
};

export type RegistryData = { funds: RegistryFund[]; items: RegistryItem[] };

const money = (cents: number) =>
    new Intl.NumberFormat('en-CA', { style: 'currency', currency: 'CAD', maximumFractionDigits: 0 }).format(cents / 100);

const serif = "font-['Playfair_Display']";

/** Public registry section on the wedding website — funds (pass-through) + items. */
export function WebsiteRegistry({ registry, slug }: { registry: RegistryData; slug: string }) {
    const [logFor, setLogFor] = useState<number | null>(null);
    const [amount, setAmount] = useState('');
    const [name, setName] = useState('');
    const [message, setMessage] = useState('');

    if (registry.funds.length === 0 && registry.items.length === 0) {
        return null;
    }

    function logGift(fundId: number) {
        const cents = Math.round(parseFloat(amount || '0') * 100);
        if (!cents || cents < 100) {
            toast.error('Enter the amount you gave.');
            return;
        }
        router.post(
            `/w/${slug}/registry/funds/${fundId}/contribute`,
            { amount_cents: cents, contributor_name: name, message } as Record<string, string | number>,
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Thank you! Your gift was recorded.');
                    setLogFor(null);
                    setAmount('');
                    setName('');
                    setMessage('');
                },
            },
        );
    }

    function claim(itemId: number) {
        router.post(`/w/${slug}/registry/items/${itemId}/claim`, {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Marked as yours — thank you!'),
        });
    }

    return (
        <section className="px-6 py-20" style={{ backgroundColor: 'var(--c-surface)' }}>
            <div className="mx-auto max-w-4xl">
                <p className="text-center text-xs tracking-[0.3em] uppercase" style={{ color: 'var(--c-primary)' }}>
                    With love
                </p>
                <h2 className={`${serif} mt-3 text-center text-4xl sm:text-5xl`} style={{ color: 'var(--c-text)' }}>
                    Registry
                </h2>

                {/* Funds */}
                {registry.funds.length > 0 && (
                    <div className="mt-12 grid gap-6 sm:grid-cols-2">
                        {registry.funds.map((f) => {
                            const pct = f.goal_cents ? Math.min(100, Math.round((f.raised_cents / f.goal_cents) * 100)) : null;
                            return (
                                <div key={f.id} className="overflow-hidden rounded-xl border" style={{ borderColor: 'var(--c-border)', backgroundColor: 'var(--c-bg)' }}>
                                    {f.image_url && <img src={f.image_url} alt="" className="h-40 w-full object-cover" />}
                                    <div className="space-y-3 p-5">
                                        <h3 className={`${serif} text-2xl`} style={{ color: 'var(--c-text)' }}>{f.title}</h3>
                                        {f.blurb && <p className="text-sm" style={{ color: 'var(--c-muted)' }}>{f.blurb}</p>}
                                        <p className="text-sm font-medium" style={{ color: 'var(--c-primary)' }}>
                                            {money(f.raised_cents)} raised{f.goal_cents ? ` of ${money(f.goal_cents)}` : ''}
                                        </p>
                                        {pct !== null && (
                                            <div className="h-2 overflow-hidden rounded-full" style={{ backgroundColor: 'var(--c-surface)' }}>
                                                <div className="h-full rounded-full" style={{ width: `${pct}%`, backgroundColor: 'var(--c-primary)' }} />
                                            </div>
                                        )}
                                        <div className="flex flex-wrap gap-2 pt-1">
                                            {f.payout_url && (
                                                <a href={f.payout_url} target="_blank" rel="noopener noreferrer"
                                                    className="rounded-full px-5 py-2 text-xs font-semibold tracking-wide text-white uppercase"
                                                    style={{ backgroundColor: 'var(--c-primary)' }}>
                                                    Contribute
                                                </a>
                                            )}
                                            <button type="button" onClick={() => setLogFor(logFor === f.id ? null : f.id)}
                                                className="rounded-full border px-5 py-2 text-xs font-semibold tracking-wide uppercase"
                                                style={{ borderColor: 'var(--c-border)', color: 'var(--c-text)' }}>
                                                I already gave
                                            </button>
                                        </div>
                                        {logFor === f.id && (
                                            <div className="space-y-2 rounded-lg p-3" style={{ backgroundColor: 'var(--c-surface)' }}>
                                                <input value={amount} onChange={(e) => setAmount(e.target.value)} type="number" min={1} placeholder="Amount ($)"
                                                    className="w-full rounded border-0 bg-white/70 px-3 py-2 text-sm" />
                                                <input value={name} onChange={(e) => setName(e.target.value)} placeholder="Your name (optional)"
                                                    className="w-full rounded border-0 bg-white/70 px-3 py-2 text-sm" />
                                                <input value={message} onChange={(e) => setMessage(e.target.value)} placeholder="A note (optional)"
                                                    className="w-full rounded border-0 bg-white/70 px-3 py-2 text-sm" />
                                                <button type="button" onClick={() => logGift(f.id)}
                                                    className="w-full rounded-full px-4 py-2 text-xs font-semibold tracking-wide text-white uppercase"
                                                    style={{ backgroundColor: 'var(--c-primary)' }}>
                                                    Record my gift
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Items */}
                {registry.items.length > 0 && (
                    <div className="mt-12 grid gap-5 sm:grid-cols-3">
                        {registry.items.map((i) => {
                            const claimed = i.claimed_count >= i.quantity;
                            return (
                                <div key={i.id} className="overflow-hidden rounded-xl border" style={{ borderColor: 'var(--c-border)', backgroundColor: 'var(--c-bg)' }}>
                                    {i.image_url && <img src={i.image_url} alt="" className={`aspect-square w-full object-cover ${claimed ? 'opacity-50' : ''}`} />}
                                    <div className="space-y-1 p-4">
                                        <p className="font-medium" style={{ color: 'var(--c-text)' }}>{i.name}</p>
                                        {i.price_cents != null && <p className="text-sm" style={{ color: 'var(--c-primary)' }}>{money(i.price_cents)}</p>}
                                        {i.blurb && <p className="text-xs" style={{ color: 'var(--c-muted)' }}>{i.blurb}</p>}
                                        <div className="flex flex-wrap gap-2 pt-2">
                                            {i.store_url && (
                                                <a href={i.store_url} target="_blank" rel="noopener noreferrer" className="text-xs font-semibold underline" style={{ color: 'var(--c-primary)' }}>
                                                    View in store
                                                </a>
                                            )}
                                            {claimed ? (
                                                <span className="text-xs" style={{ color: 'var(--c-muted)' }}>Claimed 🤍</span>
                                            ) : (
                                                <button type="button" onClick={() => claim(i.id)} className="text-xs font-semibold" style={{ color: 'var(--c-text)' }}>
                                                    I'll buy this
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </section>
    );
}

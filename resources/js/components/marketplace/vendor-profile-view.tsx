import { Link, router, useForm } from '@inertiajs/react';
import { formatMoney } from '@/lib/format';
import {
    BadgeCheck,
    CalendarCheck,
    CheckCircle2,
    ExternalLink,
    Flag,
    Globe,
    Mail,
    MapPin,
    MessageSquare,
    Phone,
    Star,
    Store,
    Tag,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export type MarketplaceService = {
    id: number;
    name: string;
    description: string | null;
    price_cents: number | null;
    price_unit: string | null;
    price_type: string;
};

export type MarketplaceMediaItem = {
    id: number;
    url: string;
    caption: string | null;
    alt: string | null;
};

export type MarketplaceReview = {
    id: number;
    rating: number;
    body: string | null;
    vendor_response: string | null;
    author: string;
    created_at: string | null;
};

export type MarketplaceProfile = {
    id: number;
    slug: string;
    business_name: string;
    category: string | null;
    category_label: string | null;
    tagline: string | null;
    description: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    service_area: string | null;
    base_price_cents: number | null;
    price_unit: string | null;
    website: string | null;
    video_url: string | null;
    brochure_url: string | null;
    phone: string | null;
    email: string | null;
    socials: { instagram?: string; facebook?: string; tiktok?: string };
    rating_avg: number;
    rating_count: number;
    response_hours: number | null;
    is_accepting_bookings: boolean;
    is_demo?: boolean;
    is_verified: boolean;
    logo_url: string | null;
    cover_url: string | null;
    media: MarketplaceMediaItem[];
    services: MarketplaceService[];
    reviews: MarketplaceReview[];
};

export type MarketplaceAuthContext = {
    is_couple: boolean;
    has_wedding: boolean;
    existing_inquiry: number | null;
};

export type ServiceOption = { id: number; name: string };

const PRICE_UNITS: Record<string, string> = {
    per_event: 'event',
    per_hour: 'hr',
    per_person: 'person',
};

function priceLabel(service: MarketplaceService) {
    if (service.price_type === 'quote_only') return 'Quote only';
    if (!service.price_cents) return '—';
    const fmt = formatMoney(service.price_cents);
    const u = service.price_unit ? PRICE_UNITS[service.price_unit] ?? service.price_unit : null;
    const prefix = service.price_type === 'from' ? 'From ' : '';
    return `${prefix}${fmt}${u ? ` / ${u}` : ''}`;
}

function StarRating({ avg, count }: { avg: number; count: number }) {
    return (
        <div className="flex items-center gap-2">
            <div className="flex">
                {[1, 2, 3, 4, 5].map((i) => (
                    <Star
                        key={i}
                        className={`size-4 ${i <= Math.round(avg) ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'}`}
                    />
                ))}
            </div>
            <span className="text-sm font-medium">{avg.toFixed(1)}</span>
            {count > 0 && <span className="text-sm text-muted-foreground">({count} review{count !== 1 ? 's' : ''})</span>}
        </div>
    );
}

/** Convert a YouTube/Vimeo URL to an embeddable iframe src, or null. */
function toVideoEmbed(url: string | null): string | null {
    if (!url) return null;
    try {
        const u = new URL(url);
        if (u.hostname.includes('youtube.com')) {
            const id = u.searchParams.get('v');
            return id ? `https://www.youtube-nocookie.com/embed/${id}` : null;
        }
        if (u.hostname === 'youtu.be') {
            return `https://www.youtube-nocookie.com/embed/${u.pathname.slice(1)}`;
        }
        if (u.hostname.includes('vimeo.com')) {
            const id = u.pathname.split('/').filter(Boolean)[0];
            return id ? `https://player.vimeo.com/video/${id}` : null;
        }
    } catch {
        return null;
    }
    return null;
}

/**
 * Shared marketplace vendor profile display — cover, header, about, gallery,
 * services, the request-a-quote CTA, and contact card. Rendered by both the
 * public page (`public/vendor-profile`) and the in-portal couple page
 * (`vendors/marketplace-show`); each supplies its own surrounding chrome.
 */
export function VendorProfileView({
    profile,
    authContext,
    services,
}: {
    profile: MarketplaceProfile;
    authContext: MarketplaceAuthContext;
    services: ServiceOption[];
}) {
    const locationStr = [profile.city, profile.region].filter(Boolean).join(', ');
    const videoEmbed = toVideoEmbed(profile.video_url);
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        vendor_profile_id: profile.id,
        vendor_service_id: '' as number | '',
        event_date: '',
        guest_count: '' as number | '',
        budget_cents: '' as number | '',
        message: '',
    });

    function submitInquiry(e: React.FormEvent) {
        e.preventDefault();
        post('/vendors/quotes', { preserveScroll: true, onSuccess: () => { reset(); setShowForm(false); } });
    }

    return (
        <>
            {/* Cover */}
            <div className="relative h-64 overflow-hidden bg-muted sm:h-80">
                {profile.cover_url ? (
                    <img
                        src={profile.cover_url}
                        alt={`${profile.business_name} cover`}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <Store className="size-16 text-muted-foreground/20" />
                    </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" />
            </div>

            <div className="mx-auto max-w-7xl px-4">
                {/* Profile header */}
                <div className="-mt-12 mb-8 flex flex-wrap items-end gap-5">
                    {/* Logo */}
                    <div className="relative size-24 shrink-0 overflow-hidden rounded-2xl border-4 border-background bg-card shadow-lg sm:size-28">
                        {profile.logo_url ? (
                            <img src={profile.logo_url} alt={profile.business_name} className="h-full w-full object-cover" />
                        ) : (
                            <div className="flex h-full items-center justify-center bg-muted">
                                <Store className="size-10 text-muted-foreground/40" />
                            </div>
                        )}
                    </div>

                    <div className="flex-1 pb-1">
                        <div className="flex flex-wrap items-start gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-2xl font-bold sm:text-3xl">
                                    {profile.business_name}
                                    {profile.is_verified && <BadgeCheck className="size-6 text-sky-600" aria-label="Verified vendor" />}
                                </h1>
                                {profile.tagline && (
                                    <p className="mt-0.5 text-muted-foreground">{profile.tagline}</p>
                                )}
                            </div>
                            <div className="ml-auto flex flex-wrap items-center gap-2">
                                {profile.category_label && (
                                    <Badge variant="secondary">{profile.category_label}</Badge>
                                )}
                                {profile.is_demo ? (
                                    <Badge variant="outline" className="text-muted-foreground">Sample listing</Badge>
                                ) : !profile.is_accepting_bookings ? (
                                    <Badge variant="outline" className="text-muted-foreground">Fully booked</Badge>
                                ) : null}
                            </div>
                        </div>

                        <div className="mt-2 flex flex-wrap items-center gap-4">
                            {locationStr && (
                                <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <MapPin className="size-4" /> {locationStr}
                                </span>
                            )}
                            {profile.service_area && (
                                <span className="text-sm text-muted-foreground">· Serves {profile.service_area}</span>
                            )}
                            {profile.rating_count > 0 && (
                                <StarRating avg={profile.rating_avg} count={profile.rating_count} />
                            )}
                            {profile.response_hours !== null && (
                                <span className="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                    <Zap className="size-3.5" />
                                    Usually responds within {profile.response_hours}h
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                <div className="grid gap-8 pb-16 lg:grid-cols-3">
                    {/* Main content */}
                    <div className="lg:col-span-2 space-y-8">
                        {/* About */}
                        {profile.description && (
                            <section>
                                <h2 className="mb-3 text-lg font-semibold">About</h2>
                                <p className="whitespace-pre-line text-muted-foreground leading-relaxed">{profile.description}</p>
                            </section>
                        )}

                        {/* Gallery */}
                        {profile.media.length > 0 && (
                            <section>
                                <h2 className="mb-3 text-lg font-semibold">Gallery</h2>
                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    {profile.media.map((m) => (
                                        <a
                                            key={m.id}
                                            href={m.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="group overflow-hidden rounded-lg"
                                        >
                                            <img
                                                src={m.url}
                                                alt={m.alt ?? m.caption ?? profile.business_name}
                                                loading="lazy"
                                                decoding="async"
                                                className="aspect-square w-full object-cover transition-transform group-hover:scale-105"
                                            />
                                        </a>
                                    ))}
                                </div>
                            </section>
                        )}

                        {/* Portfolio video */}
                        {videoEmbed && (
                            <section>
                                <h2 className="mb-3 text-lg font-semibold">Watch</h2>
                                <div className="aspect-video w-full overflow-hidden rounded-xl bg-muted">
                                    <iframe src={videoEmbed} title="Portfolio video" allow="encrypted-media; picture-in-picture" allowFullScreen className="h-full w-full border-0" />
                                </div>
                            </section>
                        )}

                        {/* Brochure */}
                        {profile.brochure_url && (
                            <a href={profile.brochure_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-muted">
                                Download brochure (PDF)
                            </a>
                        )}

                        {/* Services */}
                        {profile.services.length > 0 && (
                            <section>
                                <h2 className="mb-3 text-lg font-semibold">Services &amp; packages</h2>
                                <div className="divide-y rounded-xl border">
                                    {profile.services.map((s) => (
                                        <div key={s.id} className="flex items-start justify-between gap-4 px-4 py-4">
                                            <div className="flex-1">
                                                <p className="font-medium">{s.name}</p>
                                                {s.description && (
                                                    <p className="mt-0.5 text-sm text-muted-foreground">{s.description}</p>
                                                )}
                                            </div>
                                            <p className="shrink-0 text-sm font-medium text-[#775a19] flex items-center gap-1">
                                                <Tag className="size-3.5" />
                                                {priceLabel(s)}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {/* Reviews */}
                        {profile.reviews.length > 0 && (
                            <section>
                                <h2 className="mb-3 text-lg font-semibold">Reviews</h2>
                                <div className="mb-4">
                                    <StarRating avg={profile.rating_avg} count={profile.rating_count} />
                                </div>
                                <div className="space-y-4">
                                    {profile.reviews.map((r) => (
                                        <div key={r.id} className="rounded-xl border bg-card p-4">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div className="flex">
                                                    {[1, 2, 3, 4, 5].map((i) => (
                                                        <Star
                                                            key={i}
                                                            className={`size-4 ${i <= r.rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'}`}
                                                        />
                                                    ))}
                                                </div>
                                                <span className="text-xs text-muted-foreground">
                                                    {r.author}{r.created_at ? ` · ${r.created_at}` : ''}
                                                </span>
                                            </div>
                                            {r.body && (
                                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{r.body}</p>
                                            )}
                                            {r.vendor_response && (
                                                <div className="mt-3 ml-4 rounded-lg border-l-2 border-[#775a19]/40 bg-muted/50 p-3">
                                                    <p className="text-xs font-semibold text-[#775a19]">Response from {profile.business_name}</p>
                                                    <p className="mt-1 text-sm text-muted-foreground">{r.vendor_response}</p>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        <ReportListing slug={profile.slug} />
                    </div>

                    {/* Sidebar */}
                    <aside className="space-y-6">
                        {/* CTA card */}
                        <div className="rounded-xl border bg-card p-5">
                            {profile.base_price_cents && (
                                <p className="mb-1 text-2xl font-bold text-[#775a19]">
                                    {formatMoney(profile.base_price_cents)}
                                    {profile.price_unit && (
                                        <span className="text-sm font-normal text-muted-foreground">
                                            {' '}/ {PRICE_UNITS[profile.price_unit] ?? profile.price_unit}
                                        </span>
                                    )}
                                </p>
                            )}
                            <p className="mb-4 text-sm text-muted-foreground">
                                {profile.is_demo
                                    ? 'This is a sample listing that shows how real vendors will appear. It is not a real business and cannot be contacted yet — real Ontario vendors are coming soon.'
                                    : profile.is_accepting_bookings
                                      ? 'This vendor is accepting new bookings.'
                                      : 'This vendor is fully booked.'}
                            </p>

                            {/* Already has an open inquiry */}
                            {authContext.existing_inquiry ? (
                                <Link
                                    href={`/vendors/quotes/${authContext.existing_inquiry}`}
                                    className="flex w-full items-center justify-center gap-2 rounded-lg border border-[#775a19] px-4 py-2.5 text-sm font-semibold text-[#775a19] hover:bg-[#775a19]/5"
                                >
                                    <CheckCircle2 className="size-4" />
                                    View your inquiry
                                </Link>
                            ) : authContext.is_couple && authContext.has_wedding && profile.is_accepting_bookings && !profile.is_demo ? (
                                <>
                                    {!showForm ? (
                                        <button
                                            type="button"
                                            onClick={() => setShowForm(true)}
                                            className="flex w-full items-center justify-center gap-2 rounded-lg bg-[#775a19] px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90"
                                        >
                                            <MessageSquare className="size-4" />
                                            Request a quote
                                        </button>
                                    ) : (
                                        <form onSubmit={submitInquiry} className="space-y-3">
                                            {services.length > 0 && (
                                                <div>
                                                    <Label className="text-xs">Service (optional)</Label>
                                                    <Select
                                                        value={data.vendor_service_id === '' ? '_none' : String(data.vendor_service_id)}
                                                        onValueChange={(v: string) => setData('vendor_service_id', v === '_none' ? '' : Number(v))}
                                                    >
                                                        <SelectTrigger className="mt-1 h-8 text-sm">
                                                            <SelectValue placeholder="Any service" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="_none">Any service</SelectItem>
                                                            {services.map((s) => (
                                                                <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            )}
                                            <div className="grid grid-cols-2 gap-2">
                                                <div>
                                                    <Label className="text-xs">Event date</Label>
                                                    <Input type="date" value={data.event_date} onChange={(e) => setData('event_date', e.target.value)} className="mt-1 h-8 text-sm" />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">Guests</Label>
                                                    <Input type="number" min={1} value={data.guest_count} onChange={(e) => setData('guest_count', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 h-8 text-sm" placeholder="0" />
                                                </div>
                                            </div>
                                            <div>
                                                <Label className="text-xs">Budget (CAD cents, optional)</Label>
                                                <Input type="number" min={0} value={data.budget_cents} onChange={(e) => setData('budget_cents', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 h-8 text-sm" placeholder="e.g. 300000" />
                                            </div>
                                            <div>
                                                <Label className="text-xs">Message *</Label>
                                                <Textarea required value={data.message} onChange={(e) => setData('message', e.target.value)} rows={3} className="mt-1 text-sm" placeholder="Tell the vendor about your event…" />
                                            </div>
                                            <div className="flex gap-2">
                                                <button type="submit" disabled={processing} className="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#775a19] px-3 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50">
                                                    <MessageSquare className="size-3.5" />
                                                    {processing ? 'Sending…' : 'Send inquiry'}
                                                </button>
                                                <button type="button" onClick={() => setShowForm(false)} className="rounded-lg border px-3 py-2 text-sm hover:bg-muted">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    )}
                                </>
                            ) : profile.is_accepting_bookings && !profile.is_demo ? (
                                <Link
                                    href="/register"
                                    className="flex w-full items-center justify-center gap-2 rounded-lg bg-[#775a19] px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90"
                                >
                                    <MessageSquare className="size-4" />
                                    Request a quote
                                </Link>
                            ) : (
                                <Button variant="outline" className="w-full" disabled>
                                    <CalendarCheck className="mr-2 size-4" />
                                    {profile.is_demo ? 'Sample — coming soon' : 'Fully booked'}
                                </Button>
                            )}

                            {!authContext.is_couple && profile.is_accepting_bookings && !profile.is_demo && (
                                <p className="mt-3 text-center text-xs text-muted-foreground">
                                    Create a free account to message this vendor
                                </p>
                            )}
                        </div>

                        {/* Contact */}
                        <div className="rounded-xl border bg-card p-5 space-y-3">
                            <h3 className="text-sm font-semibold">Contact</h3>

                            {profile.phone && (
                                <a href={`tel:${profile.phone}`} className="flex items-center gap-2 text-sm hover:text-[#775a19]">
                                    <Phone className="size-4 shrink-0 text-muted-foreground" />
                                    {profile.phone}
                                </a>
                            )}

                            {profile.email && (
                                <a href={`mailto:${profile.email}`} className="flex items-center gap-2 text-sm hover:text-[#775a19]">
                                    <Mail className="size-4 shrink-0 text-muted-foreground" />
                                    {profile.email}
                                </a>
                            )}

                            {profile.website && (
                                <a
                                    href={profile.website}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="flex items-center gap-2 text-sm hover:text-[#775a19]"
                                >
                                    <Globe className="size-4 shrink-0 text-muted-foreground" />
                                    {profile.website.replace(/^https?:\/\//, '')}
                                    <ExternalLink className="size-3 text-muted-foreground" />
                                </a>
                            )}

                            {Object.entries(profile.socials ?? {}).some(([, v]) => v) && (
                                <div className="pt-2 border-t space-y-2">
                                    {profile.socials?.instagram && (
                                        <p className="text-sm text-muted-foreground">
                                            Instagram: <span className="text-foreground">{profile.socials.instagram}</span>
                                        </p>
                                    )}
                                    {profile.socials?.facebook && (
                                        <p className="text-sm text-muted-foreground">
                                            Facebook: <span className="text-foreground">{profile.socials.facebook}</span>
                                        </p>
                                    )}
                                    {profile.socials?.tiktok && (
                                        <p className="text-sm text-muted-foreground">
                                            TikTok: <span className="text-foreground">{profile.socials.tiktok}</span>
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}

/** A small "report this listing" control that flags it for admin review. */
function ReportListing({ slug }: { slug: string }) {
    const [open, setOpen] = useState(false);
    const [reason, setReason] = useState('inappropriate');
    const [details, setDetails] = useState('');

    function submit() {
        router.post('/report', { type: 'vendor', id: slug, reason, details }, {
            preserveScroll: true,
            onSuccess: () => { setOpen(false); setDetails(''); },
        });
    }

    if (!open) {
        return (
            <button onClick={() => setOpen(true)} className="mt-4 inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground">
                <Flag className="size-3.5" /> Report this listing
            </button>
        );
    }

    return (
        <div className="mt-4 flex flex-col gap-2 rounded-lg border p-3">
            <p className="text-sm font-medium">Report this listing</p>
            <select value={reason} onChange={(e) => setReason(e.target.value)} className="rounded-md border px-2 py-1.5 text-sm">
                <option value="inappropriate">Inappropriate or offensive content</option>
                <option value="fake_or_scam">Fake listing or possible scam</option>
                <option value="not_as_described">Misleading — not as described</option>
                <option value="spam">Spam</option>
                <option value="other">Something else</option>
            </select>
            <textarea value={details} onChange={(e) => setDetails(e.target.value)} rows={2} placeholder="Add any details (optional)" className="rounded-md border px-2 py-1.5 text-sm" />
            <div className="flex gap-2">
                <Button size="sm" onClick={submit}>Submit report</Button>
                <Button size="sm" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
            </div>
        </div>
    );
}

import { Link } from '@inertiajs/react';
import { Award, BadgeCheck, MapPin, Star, Store, Zap } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { formatMoney } from '@/lib/format';

export type VendorCardData = {
    id: number;
    slug: string;
    business_name: string;
    category: string | null;
    category_label: string | null;
    tagline: string | null;
    city: string | null;
    region: string | null;
    base_price_cents: number | null;
    price_unit: string | null;
    rating_avg: number;
    rating_count: number;
    response_hours: number | null;
    services_count: number;
    is_accepting_bookings: boolean;
    is_demo?: boolean;
    is_founding?: boolean;
    is_featured?: boolean;
    is_verified?: boolean;
    fits_budget?: boolean;
    near_you?: boolean;
    thumb_url: string | null;
};

const PRICE_UNITS: Record<string, string> = {
    per_event: 'event',
    per_hour: 'hr',
    per_person: 'person',
};

function priceDisplay(cents: number | null, unit: string | null) {
    if (!cents) return null;
    const formatted = formatMoney(cents);
    const u = unit ? PRICE_UNITS[unit] ?? unit : null;
    return u ? `From ${formatted} / ${u}` : `From ${formatted}`;
}

/**
 * One marketplace vendor card. Used by both the public browse grid and the
 * in-portal couple browse grid — `hrefBase` controls where the card links
 * (`/marketplace` for public, `/vendors/marketplace` in the portal).
 */
export function VendorCard({ vendor, hrefBase }: { vendor: VendorCardData; hrefBase: string }) {
    return (
        <Link
            href={`${hrefBase}/${vendor.slug}`}
            className="group lift overflow-hidden rounded-2xl border bg-card shadow-atelier transition-all hover:border-[#1f5142]/40 hover:shadow-atelier-lg"
        >
            {/* Thumbnail */}
            <div className="relative aspect-[4/3] overflow-hidden bg-muted">
                {vendor.thumb_url ? (
                    <img
                        src={vendor.thumb_url}
                        alt={vendor.business_name}
                        className="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.06]"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center bg-gradient-to-br from-secondary to-muted">
                        <Store className="size-12 text-[#1f5142]/30" />
                    </div>
                )}
                {/* Soft gradient for legibility + depth */}
                <div className="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/15 to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
                {(vendor.is_founding || vendor.is_featured) && (
                    <div className="absolute left-2 top-2">
                        <Badge className="gap-1 border-0 bg-[#1f5142] text-xs text-white hover:bg-[#1f5142]">
                            <Award className="size-3" />
                            {vendor.is_featured ? 'Featured' : 'Founding vendor'}
                        </Badge>
                    </div>
                )}
                {vendor.is_demo ? (
                    <div className="absolute right-2 top-2">
                        <Badge variant="secondary" className="text-xs">Sample listing</Badge>
                    </div>
                ) : !vendor.is_accepting_bookings ? (
                    <div className="absolute right-2 top-2">
                        <Badge variant="secondary" className="text-xs">Fully booked</Badge>
                    </div>
                ) : null}
            </div>

            {/* Content */}
            <div className="p-4">
                <div className="flex items-start justify-between gap-2">
                    <div>
                        <h3 className="flex items-center gap-1 font-semibold leading-tight group-hover:text-[#1b4638]">
                            {vendor.business_name}
                            {vendor.is_verified && <BadgeCheck className="size-4 text-sky-600" aria-label="Verified" />}
                        </h3>
                        {vendor.tagline && (
                            <p className="mt-0.5 line-clamp-1 text-sm text-muted-foreground">
                                {vendor.tagline}
                            </p>
                        )}
                    </div>
                    {vendor.category_label && (
                        <Badge variant="outline" className="shrink-0 text-xs">
                            {vendor.category_label}
                        </Badge>
                    )}
                </div>

                <div className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                    {(vendor.city || vendor.region) && (
                        <span className="flex items-center gap-1">
                            <MapPin className="size-3.5" />
                            {[vendor.city, vendor.region].filter(Boolean).join(', ')}
                        </span>
                    )}
                    {vendor.rating_count > 0 && (
                        <span className="flex items-center gap-1">
                            <Star className="size-3.5 fill-amber-400 text-amber-400" />
                            <span className="font-medium">{vendor.rating_avg.toFixed(1)}</span>
                            <span>({vendor.rating_count})</span>
                        </span>
                    )}
                    {vendor.response_hours !== null && (
                        <span className="flex items-center gap-1 text-emerald-700">
                            <Zap className="size-3.5" />
                            Responds in ~{vendor.response_hours}h
                        </span>
                    )}
                </div>

                {vendor.base_price_cents && (
                    <p className="mt-2 text-sm font-medium text-[#1b4638]">
                        {priceDisplay(vendor.base_price_cents, vendor.price_unit)}
                    </p>
                )}

                {(vendor.fits_budget || vendor.near_you) && (
                    <div className="mt-2 flex flex-wrap items-center gap-1.5">
                        {vendor.fits_budget && (
                            <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                Fits your budget
                            </span>
                        )}
                        {vendor.near_you && (
                            <span className="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:bg-sky-950/40 dark:text-sky-400">
                                Near you
                            </span>
                        )}
                    </div>
                )}
            </div>
        </Link>
    );
}

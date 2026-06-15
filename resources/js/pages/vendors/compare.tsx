import { Head, Link, router } from '@inertiajs/react';
import { formatMoney } from '@/lib/format';
import { ArrowLeft, FileDown, Globe, Mail, Phone, Star } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Option = { value: string; label: string };
type CategoryOption = { value: string; label: string; count: number };

type CompareVendor = {
    id: number;
    name: string;
    status: string;
    rating: number | null;
    price_level: number | null;
    cost: number | null;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    notes: string | null;
};

type PageProps = {
    categories: CategoryOption[];
    active: string | null;
    vendors: CompareVendor[];
    bestValueId: number | null;
    statuses: Option[];
};

function Stars({ rating }: { rating: number | null }) {
    if (!rating) {
        return <span className="text-sm text-muted-foreground">Not rated</span>;
    }

    return (
        <div className="flex gap-0.5 text-[#775a19]">
            {Array.from({ length: 5 }, (_, i) => (
                <Star key={i} className={`size-4 ${i < rating ? 'fill-current' : 'opacity-25'}`} />
            ))}
        </div>
    );
}

function PriceLevel({ level }: { level: number | null }) {
    if (!level) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <span className="font-serif text-2xl">
            <span className="text-[#775a19]">{'$'.repeat(level)}</span>
            <span className="text-muted-foreground/40">{'$'.repeat(4 - level)}</span>
        </span>
    );
}

export default function VendorCompare({ categories, active, vendors, bestValueId, statuses }: PageProps) {
    const statusLabel = (v: string) => statuses.find((s) => s.value === v)?.label ?? v;

    function changeCategory(value: string) {
        router.get('/vendors/compare', { category: value }, { preserveState: true, preserveScroll: true });
    }

    return (
        <>
            <Head title="Vendor comparison" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link
                            href="/vendors"
                            className="mb-2 inline-flex items-center gap-1 text-xs tracking-wide text-muted-foreground uppercase hover:text-[#775a19]"
                        >
                            <ArrowLeft className="size-3.5" /> Vendors
                        </Link>
                        <Heading
                            title="Vendor comparison"
                            description="Evaluate the vendors in a category side by side."
                        />
                    </div>
                    <div className="flex items-end gap-3">
                        {categories.length > 0 && active && (
                            <Select value={active} onValueChange={changeCategory}>
                                <SelectTrigger className="w-52">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((c) => (
                                        <SelectItem key={c.value} value={c.value}>
                                            {c.label} ({c.count})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                        {active && vendors.length > 0 && (
                            <Button variant="outline" asChild>
                                <a href={`/vendors/compare/pdf?category=${active}`}>
                                    <FileDown className="size-4" />
                                    PDF
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                {vendors.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-2 py-16 text-center text-muted-foreground">
                        <Star className="size-8 opacity-40" />
                        <p>Add a couple of vendors in a category to compare them here.</p>
                    </div>
                ) : (
                    <div className="grid items-stretch gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {vendors.map((v) => {
                            const best = v.id === bestValueId;

                            return (
                                <div
                                    key={v.id}
                                    className={`relative flex flex-col p-8 ${
                                        best
                                            ? 'border-2 border-[#775a19] bg-card shadow-xl shadow-[#775a19]/5 xl:-translate-y-2'
                                            : 'border border-border bg-card'
                                    }`}
                                >
                                    {best && (
                                        <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#775a19] px-5 py-1 text-xs tracking-[0.15em] whitespace-nowrap text-white uppercase">
                                            Best value
                                        </div>
                                    )}

                                    <div className="mb-6 flex items-start justify-between gap-3">
                                        <h3 className="font-serif text-2xl leading-tight">{v.name}</h3>
                                        <span className="shrink-0 border border-border px-2.5 py-1 text-[10px] tracking-wider text-muted-foreground uppercase">
                                            {statusLabel(v.status)}
                                        </span>
                                    </div>

                                    <dl className="flex-1 space-y-5">
                                        <div>
                                            <dt className="mb-1 text-xs tracking-widest text-muted-foreground uppercase">
                                                Rating
                                            </dt>
                                            <dd>
                                                <Stars rating={v.rating} />
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="mb-1 text-xs tracking-widest text-muted-foreground uppercase">
                                                Price range
                                            </dt>
                                            <dd>
                                                <PriceLevel level={v.price_level} />
                                            </dd>
                                        </div>
                                        <div className="border-t border-border pt-4">
                                            <dt className="mb-1 text-xs tracking-widest text-muted-foreground uppercase">
                                                Estimated cost
                                            </dt>
                                            <dd className="font-serif text-xl">
                                                {v.cost !== null ? formatMoney(v.cost * 100) : '—'}
                                            </dd>
                                        </div>
                                        {(v.contact_name || v.email || v.phone || v.website) && (
                                            <div className="space-y-1.5 border-t border-border pt-4 text-sm text-muted-foreground">
                                                {v.contact_name && <p>{v.contact_name}</p>}
                                                {v.email && (
                                                    <a href={`mailto:${v.email}`} className="flex items-center gap-2 hover:text-[#775a19]">
                                                        <Mail className="size-3.5" /> {v.email}
                                                    </a>
                                                )}
                                                {v.phone && (
                                                    <a href={`tel:${v.phone}`} className="flex items-center gap-2 hover:text-[#775a19]">
                                                        <Phone className="size-3.5" /> {v.phone}
                                                    </a>
                                                )}
                                                {v.website && (
                                                    <a
                                                        href={v.website}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="flex items-center gap-2 hover:text-[#775a19]"
                                                    >
                                                        <Globe className="size-3.5" /> Website
                                                    </a>
                                                )}
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

VendorCompare.layout = {
    breadcrumbs: [
        { title: 'Vendors', href: '/vendors' },
        { title: 'Compare', href: '/vendors/compare' },
    ],
};

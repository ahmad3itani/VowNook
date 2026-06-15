import { router } from '@inertiajs/react';
import { Search, Store, X } from 'lucide-react';
import { useState } from 'react';
import { VendorCard, VendorCardData } from '@/components/marketplace/vendor-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CA_PROVINCES } from '@/lib/provinces';

export type MarketplaceCategory = { value: string; label: string };

export type MarketplaceFilters = {
    category: string;
    city: string;
    region: string;
    min_price: string;
    max_price: string;
};

/**
 * The filter sidebar + vendor grid shared by the public `/marketplace` page and
 * the in-portal `/vendors/marketplace` page. `endpoint` is the GET URL filters
 * navigate to; `hrefBase` is the link prefix for each vendor card.
 */
export function MarketplaceBrowse({
    profiles,
    categories,
    filters,
    endpoint,
    hrefBase,
}: {
    profiles: VendorCardData[];
    categories: MarketplaceCategory[];
    filters: MarketplaceFilters;
    endpoint: string;
    hrefBase: string;
}) {
    const [form, setForm] = useState<MarketplaceFilters>(filters);

    function applyFilters() {
        const params: Record<string, string> = {};
        if (form.category) params.category = form.category;
        if (form.city) params.city = form.city;
        if (form.region) params.region = form.region;
        if (form.min_price) params.min_price = form.min_price;
        if (form.max_price) params.max_price = form.max_price;
        router.get(endpoint, params, { preserveState: true, replace: true });
    }

    function clearFilters() {
        setForm({ category: '', city: '', region: '', min_price: '', max_price: '' });
        router.get(endpoint, {}, { preserveState: true, replace: true });
    }

    const hasFilters = !!(form.category || form.city || form.region || form.min_price || form.max_price);

    return (
        <div className="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
            {/* Sidebar filters */}
            <aside className="mb-6 lg:mb-0">
                <div className="sticky top-4 rounded-xl border bg-card p-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold">Filters</h2>
                        {hasFilters && (
                            <button
                                type="button"
                                onClick={clearFilters}
                                className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                            >
                                <X className="size-3" /> Clear
                            </button>
                        )}
                    </div>

                    <div className="mt-4 space-y-4">
                        <div>
                            <label className="mb-1 block text-xs font-medium text-muted-foreground">Category</label>
                            <Select
                                value={form.category || '_all'}
                                onValueChange={(v: string) => setForm((f) => ({ ...f, category: v === '_all' ? '' : v }))}
                            >
                                <SelectTrigger className="h-8 text-sm">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_all">All categories</SelectItem>
                                    {categories.map((c) => (
                                        <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-medium text-muted-foreground">Province</label>
                            <Select
                                value={form.region || '_all'}
                                onValueChange={(v: string) => setForm((f) => ({ ...f, region: v === '_all' ? '' : v }))}
                            >
                                <SelectTrigger className="h-8 text-sm">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_all">All provinces</SelectItem>
                                    {CA_PROVINCES.map((p) => (
                                        <SelectItem key={p.value} value={p.value}>{p.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-medium text-muted-foreground">City / area</label>
                            <Input
                                value={form.city}
                                onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
                                placeholder="e.g. Toronto"
                                className="h-8 text-sm"
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-medium text-muted-foreground">Min price (cents)</label>
                            <Input
                                type="number"
                                min={0}
                                value={form.min_price}
                                onChange={(e) => setForm((f) => ({ ...f, min_price: e.target.value }))}
                                placeholder="e.g. 100000"
                                className="h-8 text-sm"
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-medium text-muted-foreground">Max price (cents)</label>
                            <Input
                                type="number"
                                min={0}
                                value={form.max_price}
                                onChange={(e) => setForm((f) => ({ ...f, max_price: e.target.value }))}
                                placeholder="e.g. 500000"
                                className="h-8 text-sm"
                            />
                        </div>

                        <Button size="sm" className="w-full" onClick={applyFilters}>
                            <Search className="mr-1.5 size-3.5" />
                            Apply filters
                        </Button>
                    </div>
                </div>
            </aside>

            {/* Vendor grid */}
            <main>
                {profiles.length === 0 ? (
                    <div className="rounded-xl border bg-card py-20 text-center">
                        <Store className="mx-auto size-10 text-muted-foreground/30" />
                        <p className="mt-3 text-muted-foreground">No vendors match your filters.</p>
                        {hasFilters && (
                            <Button variant="outline" size="sm" className="mt-4" onClick={clearFilters}>
                                Clear filters
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {profiles.map((v) => (
                            <VendorCard key={v.id} vendor={v} hrefBase={hrefBase} />
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}

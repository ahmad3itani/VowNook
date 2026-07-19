import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type GapRow = {
    city: string;
    city_name: string;
    category: string;
    category_label: string;
    real_supply: number;
    demand: number;
};

type PageProps = {
    rows: GapRow[];
    summary: {
        total_real_vendors: number;
        total_categories_with_zero_supply: number;
        total_cities_with_zero_supply: number;
    };
};

const num = new Intl.NumberFormat('en-CA', { maximumFractionDigits: 0 });

export default function AdminVendorGaps({ rows, summary }: PageProps) {
    return (
        <>
            <Head title="Vendor gaps" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Vendor recruitment gaps"
                    description="Real (non-demo) vendor supply versus couple demand, by city and category — the priority list for who to recruit next."
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <Stat label="Real vendors on the platform" value={num.format(summary.total_real_vendors)} accent />
                    <Stat label="Categories with zero real supply" value={num.format(summary.total_categories_with_zero_supply)} />
                    <Stat label="Cities with zero real supply" value={num.format(summary.total_cities_with_zero_supply)} />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Top recruitment priorities</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {rows.length === 0 ? (
                            <Empty />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-xs text-muted-foreground">
                                            <th className="px-4 py-2 font-medium">City</th>
                                            <th className="px-4 py-2 font-medium">Category</th>
                                            <th className="px-4 py-2 font-medium">Real supply</th>
                                            <th className="px-4 py-2 font-medium">Demand</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.map((r) => (
                                            <tr key={`${r.city}-${r.category}`} className="border-b last:border-0">
                                                <td className="px-4 py-2">{r.city_name}</td>
                                                <td className="px-4 py-2">{r.category_label}</td>
                                                <td className="px-4 py-2 tabular-nums">
                                                    <span className={r.real_supply === 0 ? 'font-semibold text-red-600' : r.real_supply <= 2 ? 'font-semibold text-amber-600' : ''}>
                                                        {r.real_supply}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-2 tabular-nums text-muted-foreground">{r.demand}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Stat({ label, value, accent }: { label: string; value: string; accent?: boolean }) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className={`mt-1 text-2xl font-semibold tabular-nums ${accent ? 'text-[#1b4638]' : ''}`}>{value}</div>
            </CardContent>
        </Card>
    );
}

function Empty() {
    return <p className="px-2 py-6 text-center text-sm text-muted-foreground">Nothing yet.</p>;
}

AdminVendorGaps.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Vendor gaps', href: '/admin/vendor-gaps' },
    ],
};

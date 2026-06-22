import { Head } from '@inertiajs/react';
import { ExternalLink, Hotel, Plane } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type Balance = {
    connected: boolean;
    amount: number | null;
    currency: string | null;
    error: string | null;
};

type PageProps = {
    adoption: {
        stays_live: number;
        flights_live: number;
        honeymoons: number;
        honeymoons_planned: number;
    };
    stay22: { enabled: boolean; dashboard_url: string };
    travelpayouts: { enabled: boolean; dashboard_url: string; balance: Balance };
};

const num = new Intl.NumberFormat('en-CA', { maximumFractionDigits: 0 });

function formatMoney(amount: number, currency: string | null): string {
    try {
        return amount.toLocaleString('en-CA', {
            style: 'currency',
            currency: currency || 'USD',
        });
    } catch {
        return `${num.format(amount)} ${currency ?? ''}`.trim();
    }
}

function StatCard({ label, value, sub }: { label: string; value: string; sub?: string }) {
    return (
        <Card>
            <CardContent className="py-5">
                <p className="text-xs text-muted-foreground">{label}</p>
                <p className="mt-1 text-2xl font-semibold">{value}</p>
                {sub && <p className="mt-1 text-xs text-muted-foreground">{sub}</p>}
            </CardContent>
        </Card>
    );
}

export default function AdminAffiliates({ adoption, stay22, travelpayouts }: PageProps) {
    const bal = travelpayouts.balance;

    return (
        <>
            <Head title="Affiliate revenue" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Affiliate revenue"
                    description="Travel-affiliate adoption across the platform, plus your partner earnings."
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Sites with hotel map" value={num.format(adoption.stays_live)} sub="published, venue set" />
                    <StatCard label="Sites with flight search" value={num.format(adoption.flights_live)} sub="airport set" />
                    <StatCard label="Honeymoon plans" value={num.format(adoption.honeymoons)} />
                    <StatCard label="With a destination" value={num.format(adoption.honeymoons_planned)} sub="ready to book" />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Stay22 */}
                    <Card>
                        <CardContent className="flex flex-col gap-3 py-5">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Hotel className="size-5 text-[#775a19]" />
                                    <h2 className="font-medium">Stay22 — hotels</h2>
                                </div>
                                <Badge variant={stay22.enabled ? 'default' : 'outline'}>
                                    {stay22.enabled ? 'Connected' : 'Not set'}
                                </Badge>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Live earnings aren’t available via API yet — view your bookings and commissions in the
                                Stay22 Hub.
                            </p>
                            <a
                                href={stay22.dashboard_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex w-fit items-center gap-1.5 text-sm font-medium text-[#775a19] hover:underline"
                            >
                                Open Stay22 Hub <ExternalLink className="size-3.5" />
                            </a>
                        </CardContent>
                    </Card>

                    {/* Travelpayouts */}
                    <Card>
                        <CardContent className="flex flex-col gap-3 py-5">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Plane className="size-5 text-[#775a19]" />
                                    <h2 className="font-medium">Travelpayouts — flights</h2>
                                </div>
                                <Badge variant={travelpayouts.enabled ? 'default' : 'outline'}>
                                    {travelpayouts.enabled ? 'Connected' : 'Not set'}
                                </Badge>
                            </div>

                            {bal.connected && bal.amount !== null ? (
                                <div>
                                    <p className="text-xs text-muted-foreground">Current balance</p>
                                    <p className="text-2xl font-semibold text-[#775a19]">
                                        {formatMoney(bal.amount, bal.currency)}
                                    </p>
                                </div>
                            ) : bal.error ? (
                                <p className="text-sm text-muted-foreground">{bal.error}</p>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Add your Travelpayouts API token (<code>TRAVELPAYOUTS_API_TOKEN</code>) to show your
                                    live balance here.
                                </p>
                            )}

                            <a
                                href={travelpayouts.dashboard_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex w-fit items-center gap-1.5 text-sm font-medium text-[#775a19] hover:underline"
                            >
                                Open Travelpayouts <ExternalLink className="size-3.5" />
                            </a>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

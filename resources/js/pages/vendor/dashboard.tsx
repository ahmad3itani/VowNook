import { Head, Link, router } from '@inertiajs/react';
import { formatMoney } from '@/lib/format';
import {
    ArrowRight,
    BadgeCheck,
    CalendarRange,
    CheckCircle2,
    CreditCard,
    MessageSquare,
    PackageOpen,
    Store,
    Wallet,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Profile = {
    id: number;
    business_name: string;
    slug: string;
    category: string;
    status: string;
    status_label: string;
    is_published: boolean;
};

type Stats = {
    services: number;
    inquiries: number;
    bookings: number;
    earnings: number;
};

type Payouts = {
    configured: boolean;
    connected: boolean;
    charges_enabled: boolean;
    details_submitted: boolean;
};

type PageProps = {
    profile: Profile | null;
    stats: Stats;
    payouts: Payouts;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    published: 'default',
    pending_review: 'secondary',
    draft: 'outline',
    suspended: 'destructive',
};

export default function VendorDashboard({ profile, stats, payouts }: PageProps) {
    return (
        <>
            <Head title="Vendor Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={profile?.business_name ?? 'Your business'}
                        description="Manage your marketplace listing, inquiries, and bookings."
                    />
                    {profile && (
                        <Badge variant={STATUS_VARIANT[profile.status] ?? 'secondary'}>
                            {profile.status_label}
                        </Badge>
                    )}
                </div>

                {/* Onboarding nudge until the listing is published */}
                {profile && !profile.is_published && (
                    <Card className="border-[#775a19]/30 bg-[#775a19]/5">
                        <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                            <div className="flex items-center gap-3">
                                <Store className="size-5 text-[#775a19]" />
                                <div>
                                    <p className="font-medium">Finish setting up your listing</p>
                                    <p className="text-sm text-muted-foreground">
                                        Complete your business profile and submit it for review to go live in the marketplace.
                                    </p>
                                </div>
                            </div>
                            <Link
                                href="/vendor/profile"
                                className="inline-flex items-center gap-1.5 rounded-md bg-[#775a19] px-3 py-2 text-sm font-medium text-white hover:opacity-90"
                            >
                                Complete profile
                                <ArrowRight className="size-4" />
                            </Link>
                        </CardContent>
                    </Card>
                )}

                {/* Quick stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Services listed" value={String(stats.services)} icon={<PackageOpen className="size-4" />} />
                    <StatCard label="Open inquiries" value={String(stats.inquiries)} icon={<MessageSquare className="size-4" />} />
                    <StatCard label="Bookings" value={String(stats.bookings)} icon={<CalendarRange className="size-4" />} />
                    <StatCard label="Earnings" value={formatMoney(stats.earnings * 100)} icon={<Wallet className="size-4" />} accent="text-[#775a19]" />
                </div>

                {/* Payouts — Stripe Connect onboarding */}
                {profile && payouts.configured && <PayoutsCard payouts={payouts} />}

                {/* Setup checklist (Phase 0 shell) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Getting started</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <SetupRow
                            done={!!profile}
                            label="Create your vendor account"
                        />
                        <SetupRow
                            done={false}
                            label="Complete your business profile (logo, description, location)"
                            href="/vendor/profile"
                        />
                        <SetupRow
                            done={false}
                            label="Add at least one service or package"
                            href="/vendor/services"
                        />
                        <SetupRow
                            done={false}
                            label="Submit your listing for review"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function PayoutsCard({ payouts }: { payouts: Payouts }) {
    const ready = payouts.connected && payouts.charges_enabled;
    const inProgress = payouts.connected && !payouts.charges_enabled;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <CreditCard className="size-4" /> Payouts
                </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-wrap items-center justify-between gap-4">
                {ready ? (
                    <div className="flex items-center gap-2 text-sm">
                        <BadgeCheck className="size-5 text-[#775a19]" />
                        <span className="font-medium">Payouts active.</span>
                        <span className="text-muted-foreground">You can receive bookings — funds land in your Stripe account minus the platform fee.</span>
                    </div>
                ) : (
                    <p className="max-w-prose text-sm text-muted-foreground">
                        {inProgress
                            ? 'Your Stripe onboarding is incomplete. Finish it so couples can pay you and you can receive payouts.'
                            : 'Connect a Stripe account to accept payments from couples. Stripe handles identity verification and payouts; the platform fee is deducted automatically.'}
                    </p>
                )}
                {!ready && (
                    <Button onClick={() => router.post('/vendor/payouts/connect')}>
                        {inProgress ? 'Finish onboarding' : 'Connect payouts'}
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}

function StatCard({
    label,
    value,
    icon,
    accent,
}: {
    label: string;
    value: string;
    icon?: React.ReactNode;
    accent?: string;
}) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="flex items-center justify-between">
                    <div className="text-sm text-muted-foreground">{label}</div>
                    {icon && <div className="text-muted-foreground">{icon}</div>}
                </div>
                <div className={`mt-1 text-2xl font-semibold tabular-nums ${accent ?? ''}`}>{value}</div>
            </CardContent>
        </Card>
    );
}

function SetupRow({ done, label, href }: { done: boolean; label: string; href?: string }) {
    const content = (
        <div className="flex items-center gap-3">
            <CheckCircle2 className={`size-5 shrink-0 ${done ? 'text-[#775a19]' : 'text-muted-foreground/30'}`} />
            <span className={done ? 'text-muted-foreground line-through' : ''}>{label}</span>
            {href && !done && <ArrowRight className="ml-auto size-4 text-muted-foreground" />}
        </div>
    );

    if (href && !done) {
        return (
            <Link href={href} className="block rounded-md px-1 py-1 transition-colors hover:bg-muted">
                {content}
            </Link>
        );
    }
    return <div className="px-1 py-1">{content}</div>;
}

VendorDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: '/vendor' }],
};

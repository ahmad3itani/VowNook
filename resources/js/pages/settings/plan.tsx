import { Head, useForm } from '@inertiajs/react';
import { Check, Gift, Ticket } from 'lucide-react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Tier = {
    key: string;
    name: string;
    price: number;
    max_weddings: number | null;
    max_guests_per_wedding: number | null;
    max_collaborators_per_wedding: number | null;
    max_gallery_photos: number | null;
};

type PageProps = {
    current: string;
    tiers: Tier[];
    comped_until: string | null;
    referral: { code: string; url: string; count: number; reward_days: number };
};

function cap(value: number | null): string {
    return value === null ? 'Unlimited' : value.toLocaleString();
}

function features(tier: Tier): string[] {
    return [
        `${cap(tier.max_weddings)} ${tier.max_weddings === 1 ? 'wedding' : 'weddings'}`,
        `${cap(tier.max_guests_per_wedding)} guests per wedding`,
        `${cap(tier.max_collaborators_per_wedding)} collaborators`,
        `${cap(tier.max_gallery_photos)} gallery photos`,
    ];
}

export default function Plan({ current, tiers, comped_until, referral }: PageProps) {
    const promo = useForm({ code: '' });

    function redeem(e: React.FormEvent) {
        e.preventDefault();
        promo.post('/settings/plan/redeem', {
            preserveScroll: true,
            onSuccess: () => {
                promo.reset('code');
                toast.success('Code applied! Your plan has been upgraded.');
            },
        });
    }

    return (
        <>
            <Head title="Plan & billing" />

            <h1 className="sr-only">Plan and billing</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Plan & billing"
                    description="Your current subscription and what each plan includes."
                />

                {comped_until && (
                    <div className="rounded-lg border border-[#775a19]/30 bg-[#fed488]/15 px-4 py-3 text-sm text-[#775a19]">
                        Your <strong>{current}</strong> access is active until <strong>{comped_until}</strong>.
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    {tiers.map((tier) => {
                        const isCurrent = tier.key === current;

                        return (
                            <div
                                key={tier.key}
                                className={cn(
                                    'flex flex-col rounded-xl border p-5',
                                    isCurrent
                                        ? 'border-[#775a19] ring-1 ring-[#775a19]'
                                        : 'border-border',
                                )}
                            >
                                <div className="flex items-center justify-between">
                                    <span className="font-serif text-lg">
                                        {tier.name}
                                    </span>
                                    {isCurrent && (
                                        <Badge className="bg-[#fed488]/40 text-[#775a19] hover:bg-[#fed488]/40 dark:bg-[#fed488]/15 dark:text-[#c5a059]">
                                            Current
                                        </Badge>
                                    )}
                                </div>

                                <div className="mt-2">
                                    <span className="text-3xl font-semibold">
                                        ${tier.price}
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        {' '}
                                        /year
                                    </span>
                                </div>

                                <ul className="mt-4 flex flex-col gap-2 text-sm">
                                    {features(tier).map((f) => (
                                        <li
                                            key={f}
                                            className="flex items-center gap-2"
                                        >
                                            <Check className="size-4 text-[#775a19]" />
                                            {f}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        );
                    })}
                </div>

                {/* Redeem a promo code */}
                <form onSubmit={redeem} className="rounded-xl border border-border p-5">
                    <div className="flex items-center gap-2 font-medium">
                        <Ticket className="size-4 text-[#775a19]" />
                        Have a promo code?
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Redeem a code to unlock an upgraded plan.
                    </p>
                    <div className="mt-3 flex max-w-sm gap-2">
                        <Input
                            value={promo.data.code}
                            onChange={(e) => promo.setData('code', e.target.value)}
                            placeholder="WEDDING2026"
                            className="uppercase"
                        />
                        <Button type="submit" disabled={promo.processing || !promo.data.code}>
                            Redeem
                        </Button>
                    </div>
                    <InputError className="mt-2" message={promo.errors.code} />
                </form>

                {/* Referral program */}
                <div className="rounded-xl border border-border p-5">
                    <div className="flex items-center gap-2 font-medium">
                        <Gift className="size-4 text-[#775a19]" />
                        Refer a friend, get {referral.reward_days} days of Premium
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                        When a couple you invite publishes their wedding website, we’ll add{' '}
                        {referral.reward_days} days of Premium to your account. You’ve referred{' '}
                        <strong>{referral.count}</strong> so far.
                    </p>
                    <div className="mt-3 flex max-w-md gap-2">
                        <Input readOnly value={referral.url} className="text-sm" />
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                navigator.clipboard?.writeText(referral.url);
                                toast.success('Referral link copied.');
                            }}
                        >
                            Copy
                        </Button>
                    </div>
                </div>

                <p className="text-sm text-muted-foreground">
                    Online billing is coming soon. To change your plan in the
                    meantime, redeem a code above or contact your administrator.
                </p>
            </div>
        </>
    );
}

Plan.layout = {
    breadcrumbs: [
        {
            title: 'Plan & billing',
            href: '/settings/plan',
        },
    ],
};

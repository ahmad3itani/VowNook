import { Head } from '@inertiajs/react';
import { Check } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
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

export default function Plan({ current, tiers }: PageProps) {
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

                <div className="grid gap-4 md:grid-cols-3">
                    {tiers.map((tier) => {
                        const isCurrent = tier.key === current;

                        return (
                            <div
                                key={tier.key}
                                className={cn(
                                    'flex flex-col rounded-xl border p-5',
                                    isCurrent
                                        ? 'border-rose-400 ring-1 ring-rose-400'
                                        : 'border-border',
                                )}
                            >
                                <div className="flex items-center justify-between">
                                    <span className="font-serif text-lg">
                                        {tier.name}
                                    </span>
                                    {isCurrent && (
                                        <Badge className="bg-rose-100 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300">
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
                                            <Check className="size-4 text-emerald-500" />
                                            {f}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        );
                    })}
                </div>

                <p className="text-sm text-muted-foreground">
                    Online billing is coming soon. To change your plan in the
                    meantime, contact your administrator.
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

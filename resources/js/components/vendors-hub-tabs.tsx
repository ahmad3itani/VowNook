import { Link } from '@inertiajs/react';
import { Briefcase, MessageSquare, Store } from 'lucide-react';
import type { ComponentType } from 'react';

export type VendorsHubTab = 'shortlist' | 'marketplace' | 'quotes';

type TabDef = {
    key: VendorsHubTab;
    label: string;
    href: string;
    icon: ComponentType<{ className?: string }>;
};

const TABS: TabDef[] = [
    { key: 'marketplace', label: 'Browse marketplace', href: '/vendors/marketplace', icon: Store },
    { key: 'quotes', label: 'My quotes', href: '/vendors/quotes', icon: MessageSquare },
    { key: 'shortlist', label: 'Booked & shortlist', href: '/vendors', icon: Briefcase },
];

/**
 * Sub-navigation shared across the couple's Vendors hub pages — keeps marketplace
 * discovery, quotes, and the private CRM under one coherent section.
 * `quoteBadge` optionally shows a count of offers awaiting the couple's response.
 */
export function VendorsHubTabs({ active, quoteBadge }: { active: VendorsHubTab; quoteBadge?: number }) {
    return (
        <div className="flex flex-wrap gap-1 border-b">
            {TABS.map((tab) => {
                const Icon = tab.icon;
                const isActive = tab.key === active;
                return (
                    <Link
                        key={tab.key}
                        href={tab.href}
                        className={`-mb-px flex items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium transition-colors ${
                            isActive
                                ? 'border-[#1b4638] text-[#1b4638]'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        <Icon className="size-4" />
                        {tab.label}
                        {tab.key === 'quotes' && quoteBadge ? (
                            <span className="ml-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-[#1b4638] px-1.5 py-0.5 text-xs font-semibold text-white">
                                {quoteBadge}
                            </span>
                        ) : null}
                    </Link>
                );
            })}
        </div>
    );
}

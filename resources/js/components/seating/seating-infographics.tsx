import { motion } from 'framer-motion';
import { Armchair, CalendarCheck, LayoutGrid, PieChart, Utensils } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export type SeatingStats = {
    tables: number;
    capacity: number;
    seated: number;
    unseated: number;
    guest_total: number;
    attending: number;
    unseated_attending: number;
    utilization: number;
    tables_at_capacity: number;
    sides: { partner_one: number; partner_two: number; both: number };
    meals: { name: string; count: number }[];
    fullest: { name: string; seated: number; capacity: number } | null;
};

const GOLD = '#8a651c';

function Kpi({
    icon: Icon,
    label,
    value,
    sub,
    progress,
    tone = 'default',
}: {
    icon: LucideIcon;
    label: string;
    value: string;
    sub?: string;
    progress?: number; // 0..1
    tone?: 'default' | 'warn' | 'ok';
}) {
    const accent = tone === 'warn' ? 'text-amber-600' : tone === 'ok' ? 'text-emerald-600' : 'text-[#8a651c]';

    return (
        <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4 }}
            className="rounded-xl border bg-card p-4"
        >
            <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <Icon className={`size-3.5 ${accent}`} />
                {label}
            </div>
            <div className={`mt-1 text-2xl font-semibold tabular-nums ${tone === 'warn' ? 'text-amber-600' : ''}`}>{value}</div>
            {sub && <div className="text-xs text-muted-foreground">{sub}</div>}
            {progress !== undefined && (
                <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                    <motion.div
                        initial={{ width: 0 }}
                        animate={{ width: `${Math.min(100, Math.round(progress * 100))}%` }}
                        transition={{ duration: 0.7, ease: 'easeOut' }}
                        className="h-full rounded-full"
                        style={{ backgroundColor: GOLD }}
                    />
                </div>
            )}
        </motion.div>
    );
}

/** Caterer- and planner-ready infographics for the floor plan. */
export function SeatingInfographics({ stats }: { stats: SeatingStats }) {
    const sideTotal = stats.sides.partner_one + stats.sides.partner_two + stats.sides.both || 1;
    const seatedPct = stats.guest_total > 0 ? Math.round((stats.seated / stats.guest_total) * 100) : 0;

    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Kpi
                icon={Armchair}
                label="Seated"
                value={`${stats.seated}/${stats.guest_total}`}
                sub={`${seatedPct}% of guests placed`}
                progress={stats.guest_total > 0 ? stats.seated / stats.guest_total : 0}
            />
            <Kpi
                icon={PieChart}
                label="Capacity used"
                value={`${stats.utilization}%`}
                sub={`${stats.seated} of ${stats.capacity} seats`}
                progress={stats.utilization / 100}
            />
            <Kpi
                icon={CalendarCheck}
                label="Still to seat"
                value={String(stats.unseated_attending)}
                sub="attending guests"
                tone={stats.unseated_attending > 0 ? 'warn' : 'ok'}
            />
            <Kpi
                icon={LayoutGrid}
                label="Tables"
                value={String(stats.tables)}
                sub={`${stats.tables_at_capacity} at capacity${stats.fullest ? ` · fullest: ${stats.fullest.name}` : ''}`}
            />

            {/* Side balance */}
            <motion.div
                initial={{ opacity: 0, y: 8 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.4, delay: 0.05 }}
                className="rounded-xl border bg-card p-4 sm:col-span-2"
            >
                <div className="text-xs font-medium text-muted-foreground">Side balance</div>
                <div className="mt-2 flex h-3 overflow-hidden rounded-full bg-muted">
                    <div style={{ width: `${(stats.sides.partner_one / sideTotal) * 100}%`, backgroundColor: '#8a651c' }} />
                    <div style={{ width: `${(stats.sides.both / sideTotal) * 100}%`, backgroundColor: '#c9a84c' }} />
                    <div style={{ width: `${(stats.sides.partner_two / sideTotal) * 100}%`, backgroundColor: '#3d7a8c' }} />
                </div>
                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1"><span className="size-2 rounded-full" style={{ backgroundColor: '#8a651c' }} />Partner 1 · {stats.sides.partner_one}</span>
                    <span className="flex items-center gap-1"><span className="size-2 rounded-full" style={{ backgroundColor: '#c9a84c' }} />Both · {stats.sides.both}</span>
                    <span className="flex items-center gap-1"><span className="size-2 rounded-full" style={{ backgroundColor: '#3d7a8c' }} />Partner 2 · {stats.sides.partner_two}</span>
                </div>
            </motion.div>

            {/* Meal totals — caterer-ready */}
            <motion.div
                initial={{ opacity: 0, y: 8 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.4, delay: 0.1 }}
                className="rounded-xl border bg-card p-4 sm:col-span-2"
            >
                <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <Utensils className="size-3.5 text-[#8a651c]" />
                    Meal counts (attending)
                </div>
                {stats.meals.length === 0 ? (
                    <p className="mt-2 text-sm text-muted-foreground">No meal choices recorded yet.</p>
                ) : (
                    <ul className="mt-2 space-y-1 text-sm">
                        {stats.meals.slice(0, 5).map((m) => (
                            <li key={m.name} className="flex items-center justify-between gap-3">
                                <span className="truncate">{m.name}</span>
                                <span className="font-medium tabular-nums">{m.count}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </motion.div>
        </div>
    );
}

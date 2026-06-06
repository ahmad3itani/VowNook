import { TriangleAlert } from 'lucide-react';

/**
 * A compact usage meter shown when a plan cap applies to a resource.
 * Hidden by the caller when the limit is null (unlimited).
 */
export function PlanUsage({ used, limit, noun }: { used: number; limit: number; noun: string }) {
    const pct = limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 100;
    const atLimit = used >= limit;

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-lg border px-4 py-3 text-sm">
            <span className="text-muted-foreground">
                {used} of {limit} {noun} on your plan
            </span>
            <div className="bg-muted h-2 min-w-32 flex-1 overflow-hidden rounded-full">
                <div
                    className={`h-full rounded-full ${atLimit ? 'bg-destructive' : 'bg-rose-400'}`}
                    style={{ width: `${pct}%` }}
                />
            </div>
            {atLimit && (
                <span className="text-destructive inline-flex items-center gap-1 font-medium">
                    <TriangleAlert className="size-3.5" />
                    Limit reached
                </span>
            )}
        </div>
    );
}

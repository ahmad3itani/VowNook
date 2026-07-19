import { Head, router } from '@inertiajs/react';
import { CalendarRange, ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';

type Entry = { date: string; status: 'booked' | 'blocked'; note: string | null };

type PageProps = {
    month: string; // YYYY-MM
    entries: Entry[];
};

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const STATUS_STYLE: Record<string, string> = {
    available: 'bg-background hover:bg-muted',
    booked: 'bg-[#1b4638] text-white hover:opacity-90',
    blocked: 'bg-muted-foreground/20 text-muted-foreground line-through hover:bg-muted-foreground/30',
};

const NEXT_STATUS: Record<string, string> = {
    available: 'booked',
    booked: 'blocked',
    blocked: 'available',
};

function monthLabel(month: string): string {
    const [y, m] = month.split('-').map(Number);
    return new Date(y, m - 1, 1).toLocaleDateString('en-CA', { month: 'long', year: 'numeric' });
}

function shiftMonth(month: string, delta: number): string {
    const [y, m] = month.split('-').map(Number);
    const d = new Date(y, m - 1 + delta, 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

export default function VendorAvailability({ month, entries }: PageProps) {
    const statusByDate = useMemo(() => {
        const map = new Map<string, Entry['status']>();
        entries.forEach((e) => map.set(e.date, e.status));
        return map;
    }, [entries]);

    // Build the day cells for the visible month, padded to a Monday start.
    const cells = useMemo(() => {
        const [y, m] = month.split('-').map(Number);
        const first = new Date(y, m - 1, 1);
        const daysInMonth = new Date(y, m, 0).getDate();
        const leadingBlanks = (first.getDay() + 6) % 7; // Monday-based

        const result: Array<{ key: string; day?: number; date?: string }> = [];
        for (let i = 0; i < leadingBlanks; i++) {
            result.push({ key: `blank-${i}` });
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const date = `${month}-${String(day).padStart(2, '0')}`;
            result.push({ key: date, day, date });
        }
        return result;
    }, [month]);

    function navigate(delta: number) {
        router.get('/vendor/availability', { month: shiftMonth(month, delta) }, { preserveState: false });
    }

    function cycle(date: string) {
        const current = statusByDate.get(date) ?? 'available';
        const next = NEXT_STATUS[current];
        router.post(
            '/vendor/availability',
            { date, status: next },
            {
                preserveScroll: true,
                onError: () => toast.error('Could not update that date. Please try again.'),
            },
        );
    }

    const today = new Date().toISOString().slice(0, 10);

    return (
        <>
            <Head title="Availability" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Availability"
                    description="Click a date to cycle it through available → booked → blocked. Couples see blocked and booked dates as unavailable."
                />

                <Card>
                    <CardContent className="space-y-4 py-5">
                        <div className="flex items-center justify-between">
                            <button
                                type="button"
                                onClick={() => navigate(-1)}
                                className="rounded-md border p-2 hover:bg-muted"
                                aria-label="Previous month"
                            >
                                <ChevronLeft className="size-4" />
                            </button>
                            <p className="flex items-center gap-2 text-lg font-semibold">
                                <CalendarRange className="size-5 text-[#1b4638]" />
                                {monthLabel(month)}
                            </p>
                            <button
                                type="button"
                                onClick={() => navigate(1)}
                                className="rounded-md border p-2 hover:bg-muted"
                                aria-label="Next month"
                            >
                                <ChevronRight className="size-4" />
                            </button>
                        </div>

                        <div className="grid grid-cols-7 gap-1.5 text-center">
                            {WEEKDAYS.map((d) => (
                                <span key={d} className="py-1 text-xs font-medium text-muted-foreground">
                                    {d}
                                </span>
                            ))}
                            {cells.map((cell) =>
                                cell.date ? (
                                    <button
                                        key={cell.key}
                                        type="button"
                                        onClick={() => cycle(cell.date!)}
                                        className={`flex aspect-square items-center justify-center rounded-md border text-sm transition-colors ${
                                            STATUS_STYLE[statusByDate.get(cell.date) ?? 'available']
                                        } ${cell.date === today ? 'ring-2 ring-[#1b4638]/50' : ''}`}
                                    >
                                        {cell.day}
                                    </button>
                                ) : (
                                    <span key={cell.key} />
                                ),
                            )}
                        </div>

                        <div className="flex flex-wrap gap-4 border-t pt-3 text-xs text-muted-foreground">
                            <span className="flex items-center gap-1.5">
                                <span className="size-3 rounded border bg-background" /> Available
                            </span>
                            <span className="flex items-center gap-1.5">
                                <span className="size-3 rounded bg-[#1b4638]" /> Booked
                            </span>
                            <span className="flex items-center gap-1.5">
                                <span className="size-3 rounded bg-muted-foreground/20" /> Blocked
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

VendorAvailability.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/vendor' },
        { title: 'Availability', href: '/vendor/availability' },
    ],
};

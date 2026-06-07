import { useEffect, useState } from 'react';

type Parts = { days: number; hours: number; minutes: number; seconds: number; past: boolean };

function partsFrom(target: Date): Parts {
    const ms = target.getTime() - Date.now();

    if (ms <= 0) {
        return { days: 0, hours: 0, minutes: 0, seconds: 0, past: true };
    }

    const s = Math.floor(ms / 1000);

    return {
        days: Math.floor(s / 86400),
        hours: Math.floor((s % 86400) / 3600),
        minutes: Math.floor((s % 3600) / 60),
        seconds: s % 60,
        past: false,
    };
}

export function Countdown({ date, light = false }: { date: string; light?: boolean }) {
    const target = new Date(date);
    const [parts, setParts] = useState<Parts>(() => partsFrom(target));

    useEffect(() => {
        const id = setInterval(() => setParts(partsFrom(new Date(date))), 1000);

        return () => clearInterval(id);
    }, [date]);

    if (parts.past) {
        return null;
    }

    const units = [
        { label: 'Days', value: parts.days },
        { label: 'Hours', value: parts.hours },
        { label: 'Minutes', value: parts.minutes },
        { label: 'Seconds', value: parts.seconds },
    ];

    return (
        <div className="flex justify-center gap-3 sm:gap-5">
            {units.map((u) => (
                <div key={u.label} className="flex flex-col items-center">
                    <div
                        className={`flex min-w-14 items-center justify-center rounded-xl px-3 py-2 font-serif text-3xl tabular-nums sm:min-w-20 sm:text-4xl ${
                            light
                                ? 'bg-white/15 text-white backdrop-blur'
                                : 'bg-white text-rose-600 shadow-sm dark:bg-stone-900 dark:text-rose-300'
                        }`}
                    >
                        {String(u.value).padStart(2, '0')}
                    </div>
                    <span
                        className={`mt-2 text-xs tracking-widest uppercase ${
                            light ? 'text-white/80' : 'text-stone-400'
                        }`}
                    >
                        {u.label}
                    </span>
                </div>
            ))}
        </div>
    );
}

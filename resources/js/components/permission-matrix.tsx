import { Eye, Pencil, Ban } from 'lucide-react';

export type Section = { value: string; label: string };
export type AccessMap = Record<string, string>;

const LEVELS: { value: string; label: string; icon: typeof Eye }[] = [
    { value: 'none', label: 'No access', icon: Ban },
    { value: 'read', label: 'View', icon: Eye },
    { value: 'write', label: 'Edit', icon: Pencil },
];

/**
 * A per-section access editor: each workspace section can be set to No access /
 * View / Edit. Used in the invite form and the per-member editor.
 */
export function PermissionMatrix({
    sections,
    value,
    onChange,
    disabled = false,
}: {
    sections: Section[];
    value: AccessMap;
    onChange: (next: AccessMap) => void;
    disabled?: boolean;
}) {
    function set(section: string, level: string) {
        onChange({ ...value, [section]: level });
    }

    return (
        <div className="overflow-hidden rounded-lg border border-border">
            {sections.map((section, i) => {
                const current = value[section.value] ?? 'none';
                return (
                    <div
                        key={section.value}
                        className={`flex items-center justify-between gap-3 px-3 py-2 ${i > 0 ? 'border-t border-border' : ''}`}
                    >
                        <span className="text-sm">{section.label}</span>
                        <div className="flex shrink-0 items-center gap-0.5 rounded-md bg-muted p-0.5">
                            {LEVELS.map((lvl) => {
                                const Icon = lvl.icon;
                                const active = current === lvl.value;
                                return (
                                    <button
                                        key={lvl.value}
                                        type="button"
                                        disabled={disabled}
                                        onClick={() => set(section.value, lvl.value)}
                                        title={lvl.label}
                                        className={`flex items-center gap-1 rounded px-2 py-1 text-xs font-medium transition-colors disabled:opacity-50 ${
                                            active
                                                ? lvl.value === 'none'
                                                    ? 'bg-card text-muted-foreground shadow-sm'
                                                    : 'bg-[#775a19] text-white shadow-sm'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }`}
                                    >
                                        <Icon className="size-3.5" />
                                        <span className="hidden sm:inline">{lvl.label}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

/** Short human summary of an access map, e.g. "Edit 4 · View 2". */
export function accessSummary(value: AccessMap): string {
    const counts = { write: 0, read: 0, none: 0 } as Record<string, number>;
    for (const level of Object.values(value)) counts[level] = (counts[level] ?? 0) + 1;
    const parts: string[] = [];
    if (counts.write) parts.push(`Edit ${counts.write}`);
    if (counts.read) parts.push(`View ${counts.read}`);
    return parts.length ? parts.join(' · ') : 'No access';
}

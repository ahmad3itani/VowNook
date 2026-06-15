import { router } from '@inertiajs/react';
import { Plus, UtensilsCrossed, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';

export type Course = { enabled: boolean; options: string[] };
export type MealConfig = { appetizer: Course; main: Course; dessert: Course };

const COURSES: { key: keyof MealConfig; label: string }[] = [
    { key: 'appetizer', label: 'Appetizer' },
    { key: 'main', label: 'Main' },
    { key: 'dessert', label: 'Dessert' },
];

function CourseEditor({ label, course, onChange }: { label: string; course: Course; onChange: (c: Course) => void }) {
    const [draft, setDraft] = useState('');

    function addOption() {
        const value = draft.trim();
        if (!value) return;
        if (course.options.some((o) => o.toLowerCase() === value.toLowerCase())) {
            setDraft('');
            return;
        }
        onChange({ ...course, options: [...course.options, value].slice(0, 12) });
        setDraft('');
    }

    return (
        <div className="rounded-lg border border-border p-3">
            <label className="flex items-center gap-2">
                <Checkbox checked={course.enabled} onCheckedChange={(v) => onChange({ ...course, enabled: !!v })} />
                <span className="font-medium">{label}</span>
                <span className="text-xs text-muted-foreground">on the RSVP form</span>
            </label>

            {course.enabled && (
                <div className="mt-3 flex flex-col gap-2">
                    {course.options.length > 0 && (
                        <div className="flex flex-wrap gap-1.5">
                            {course.options.map((opt) => (
                                <span key={opt} className="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2.5 py-1 text-xs">
                                    {opt}
                                    <button type="button" onClick={() => onChange({ ...course, options: course.options.filter((o) => o !== opt) })} aria-label={`Remove ${opt}`}>
                                        <X className="size-3 text-muted-foreground hover:text-foreground" />
                                    </button>
                                </span>
                            ))}
                        </div>
                    )}
                    <div className="flex gap-2">
                        <Input
                            value={draft}
                            onChange={(e) => setDraft(e.target.value)}
                            onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); addOption(); } }}
                            placeholder="Add a choice (e.g. Grilled chicken)"
                            className="h-9"
                        />
                        <Button type="button" variant="outline" size="sm" onClick={addOption} disabled={course.options.length >= 12}>
                            <Plus className="size-4" />
                        </Button>
                    </div>
                    {course.options.length === 0 && <p className="text-xs text-muted-foreground">Add at least one choice for guests to pick from.</p>}
                </div>
            )}
        </div>
    );
}

export function MealOptionsSheet({ open, onOpenChange, meals }: { open: boolean; onOpenChange: (o: boolean) => void; meals: MealConfig }) {
    const [config, setConfig] = useState<MealConfig>(meals);
    const [saving, setSaving] = useState(false);

    function setCourse(key: keyof MealConfig, course: Course) {
        setConfig((c) => ({ ...c, [key]: course }));
    }

    function save() {
        setSaving(true);
        router.put('/guests/meal-options', { meals: config }, {
            preserveScroll: true,
            onSuccess: () => { toast.success('Meal options saved.'); onOpenChange(false); },
            onError: () => toast.error('Could not save meal options.'),
            onFinish: () => setSaving(false),
        });
    }

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="overflow-y-auto sm:max-w-md">
                <SheetHeader>
                    <SheetTitle className="flex items-center gap-2"><UtensilsCrossed className="size-4" /> Meal options</SheetTitle>
                    <SheetDescription>
                        Turn on the courses your guests choose from. If a course is the same for everyone, leave it off.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-col gap-3 px-4">
                    {COURSES.map((c) => (
                        <CourseEditor key={c.key} label={c.label} course={config[c.key]} onChange={(course) => setCourse(c.key, course)} />
                    ))}
                </div>

                <SheetFooter className="px-4">
                    <Button onClick={save} disabled={saving}>{saving && <Spinner />} Save options</Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}

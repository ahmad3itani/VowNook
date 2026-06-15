import { Head, router, useForm } from '@inertiajs/react';
import { ClipboardList, Plus, Trash2, Wallet, Wand2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Template = {
    id: number;
    type: 'checklist' | 'budget';
    name: string;
    item_count: number;
    created_at: string;
};

type WeddingOption = {
    id: number;
    name: string;
    event_date: string | null;
};

type Props = {
    templates: Template[];
    weddings: WeddingOption[];
};

function SaveTemplateDialog({ weddings }: { weddings: WeddingOption[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        type: 'checklist',
        wedding_id: '' as number | '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/planner/templates', { onSuccess: () => { reset(); setOpen(false); } });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus className="mr-1.5 size-4" />
                    Save a template
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Save a template</DialogTitle>
                    <DialogDescription>
                        Capture a wedding's checklist or budget as a reusable blueprint. Checklist
                        due dates are stored relative to the event date.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="template-name">Template name</Label>
                        <Input
                            id="template-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="My 12-month plan"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label>What to capture</Label>
                        <Select value={data.type} onValueChange={(v: string) => setData('type', v)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="checklist">Checklist</SelectItem>
                                <SelectItem value="budget">Budget</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.type} />
                    </div>
                    <div className="grid gap-2">
                        <Label>From wedding</Label>
                        <Select
                            value={data.wedding_id === '' ? undefined : String(data.wedding_id)}
                            onValueChange={(v: string) => setData('wedding_id', Number(v))}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pick a wedding" />
                            </SelectTrigger>
                            <SelectContent>
                                {weddings.map((w) => (
                                    <SelectItem key={w.id} value={String(w.id)}>
                                        {w.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.wedding_id} />
                    </div>
                    <Button type="submit" disabled={processing || data.wedding_id === ''} className="w-full">
                        {processing ? 'Saving…' : 'Save template'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ApplyControls({ template, weddings }: { template: Template; weddings: WeddingOption[] }) {
    const [weddingId, setWeddingId] = useState<string | undefined>(undefined);
    const [processing, setProcessing] = useState(false);

    function apply() {
        if (!weddingId) return;
        setProcessing(true);
        router.post(
            `/planner/templates/${template.id}/apply`,
            { wedding_id: Number(weddingId) },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    return (
        <div className="flex items-center gap-2">
            <Select value={weddingId} onValueChange={setWeddingId}>
                <SelectTrigger className="h-8 w-44 text-sm">
                    <SelectValue placeholder="Apply to…" />
                </SelectTrigger>
                <SelectContent>
                    {weddings.map((w) => (
                        <SelectItem key={w.id} value={String(w.id)}>
                            {w.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Button size="sm" variant="outline" disabled={!weddingId || processing} onClick={apply}>
                <Wand2 className="mr-1 size-3.5" />
                {processing ? 'Applying…' : 'Apply'}
            </Button>
        </div>
    );
}

export default function PlannerTemplates({ templates, weddings }: Props) {
    return (
        <div className="space-y-8 p-4 md:p-6">
            <Head title="Templates" />

            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p className="text-[11px] tracking-[0.25em] text-[#8a651c] uppercase">Planner HQ</p>
                    <h1 className="mt-1 font-serif text-3xl font-light tracking-tight">Templates</h1>
                    <p className="mt-1.5 max-w-xl text-sm text-muted-foreground">
                        Your methodology, reusable — apply a checklist or budget to any client in
                        one click. Due dates recompute from each wedding's date.
                    </p>
                    <div className="rule-gold mt-3" />
                </div>
                <SaveTemplateDialog weddings={weddings} />
            </div>

            {templates.length === 0 ? (
                <div className="rounded-xl border border-dashed p-12 text-center">
                    <ClipboardList className="mx-auto mb-3 size-10 text-muted-foreground/40" />
                    <h2 className="text-lg font-semibold">No templates yet</h2>
                    <p className="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                        Build a checklist or budget once in any wedding, then save it here to reuse
                        for every future client.
                    </p>
                </div>
            ) : (
                <div className="divide-y rounded-xl border bg-card">
                    {templates.map((t) => (
                        <div key={t.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <div className="flex items-center gap-3">
                                {t.type === 'checklist' ? (
                                    <ClipboardList className="size-4 text-[#775a19]" />
                                ) : (
                                    <Wallet className="size-4 text-[#775a19]" />
                                )}
                                <div>
                                    <p className="font-medium">{t.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {t.item_count} item{t.item_count !== 1 ? 's' : ''} · saved {t.created_at}
                                    </p>
                                </div>
                                <Badge variant="outline" className="capitalize">{t.type}</Badge>
                            </div>
                            <div className="flex items-center gap-2">
                                <ApplyControls template={t} weddings={weddings} />
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="text-muted-foreground hover:text-red-600"
                                    onClick={() => {
                                        if (confirm(`Delete the template "${t.name}"?`)) {
                                            router.delete(`/planner/templates/${t.id}`, { preserveScroll: true });
                                        }
                                    }}
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

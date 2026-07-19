import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Feature = {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
    paid_by_default: boolean;
};

type PageProps = { features: Feature[] };

function Toggle({ on, onClick }: { on: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={on}
            onClick={onClick}
            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors ${
                on ? 'bg-[#1b4638]' : 'bg-muted-foreground/30'
            }`}
        >
            <span
                className={`pointer-events-none absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform ${
                    on ? 'translate-x-5' : ''
                }`}
            />
        </button>
    );
}

export default function AdminFeatures({ features }: PageProps) {
    const initial = useMemo(
        () => Object.fromEntries(features.map((f) => [f.key, f.enabled])),
        [features],
    );
    const [state, setState] = useState<Record<string, boolean>>(initial);
    const [saving, setSaving] = useState(false);

    const dirty = useMemo(
        () => features.some((f) => state[f.key] !== initial[f.key]),
        [features, state, initial],
    );

    function save() {
        setSaving(true);
        router.put(
            '/admin/features',
            { features: state },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Free-tier features updated.'),
                onError: () => toast.error('Could not save features.'),
                onFinish: () => setSaving(false),
            },
        );
    }

    return (
        <>
            <Head title="Features" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Free-tier features"
                    description="Unlock premium tools for everyone on the free plan — a “try it free” lever for the guest experience — or lock them back down. Paid plans always include everything."
                />

                <Card className="max-w-3xl">
                    <CardContent className="divide-y p-0">
                        {features.map((f) => (
                            <div key={f.key} className="flex items-start justify-between gap-4 p-4">
                                <div className="min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">{f.label}</span>
                                        {f.paid_by_default && (
                                            <Badge variant="outline" className="text-[10px] uppercase tracking-wide">
                                                Premium
                                            </Badge>
                                        )}
                                        {state[f.key] && (
                                            <Badge className="bg-[#1b4638] text-[10px] uppercase tracking-wide text-white hover:bg-[#1b4638]">
                                                Free
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="mt-0.5 text-sm text-muted-foreground">{f.description}</p>
                                </div>
                                <Toggle
                                    on={state[f.key]}
                                    onClick={() => setState((s) => ({ ...s, [f.key]: !s[f.key] }))}
                                />
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex max-w-3xl items-center gap-3">
                    <Button onClick={save} disabled={!dirty || saving} className="bg-[#1b4638] hover:bg-[#1b4638]">
                        {saving ? 'Saving…' : 'Save changes'}
                    </Button>
                    {dirty && <span className="text-sm text-muted-foreground">You have unsaved changes.</span>}
                </div>
            </div>
        </>
    );
}

AdminFeatures.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Features', href: '/admin/features' },
    ],
};

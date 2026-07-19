import { Head, router, useForm } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Option = { value: string; label: string };
type StringRow = { key: string; default: string; value: string | null };

type PageProps = {
    locales: Option[];
    active: string;
    strings: StringRow[];
    status?: string;
};

export default function AdminLocalisation({ locales, active, strings, status }: PageProps) {
    useEffect(() => {
        if (status === 'localisation-updated') {
            toast.success('Translations saved.');
        }
    }, [status]);

    const form = useForm<{ locale: string; strings: Record<string, string> }>({
        locale: active,
        strings: Object.fromEntries(strings.map((s) => [s.key, s.value ?? ''])),
    });

    function changeLocale(value: string) {
        router.get('/admin/localisation', { locale: value }, { preserveScroll: true });
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put('/admin/localisation', { preserveScroll: true });
    }

    return (
        <>
            <Head title="Localisation" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Localisation"
                        description="Override the interface copy for each language. Empty fields fall back to the default."
                    />
                    <div className="flex items-center gap-2">
                        <Languages className="size-4 text-[#1b4638]" />
                        <Select value={active} onValueChange={changeLocale}>
                            <SelectTrigger className="w-44">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {locales.map((l) => (
                                    <SelectItem key={l.value} value={l.value}>
                                        {l.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <form onSubmit={submit}>
                    <Card>
                        <CardContent className="p-0">
                            <div className="grid grid-cols-12 border-b border-border bg-muted/50 px-6 py-3 text-xs tracking-[0.15em] text-muted-foreground uppercase">
                                <div className="col-span-4">Key</div>
                                <div className="col-span-8">String — {active.toUpperCase()}</div>
                            </div>
                            <div className="divide-y divide-border">
                                {strings.map((s) => (
                                    <div key={s.key} className="grid grid-cols-12 items-center gap-4 px-6 py-3">
                                        <div className="col-span-4">
                                            <code className="text-xs text-muted-foreground">{s.key}</code>
                                            <p className="mt-0.5 truncate text-xs text-muted-foreground/60" title={s.default}>
                                                {s.default}
                                            </p>
                                        </div>
                                        <div className="col-span-8">
                                            <Input
                                                value={form.data.strings[s.key] ?? ''}
                                                placeholder={s.default}
                                                onChange={(e) =>
                                                    form.setData('strings', {
                                                        ...form.data.strings,
                                                        [s.key]: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <div className="mt-4 flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Save translations
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

AdminLocalisation.layout = {
    breadcrumbs: [{ title: 'Localisation', href: '/admin/localisation' }],
};

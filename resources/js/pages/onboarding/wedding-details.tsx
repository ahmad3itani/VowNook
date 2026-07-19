import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Band = { key: string; label: string; cents: number };
type City = { slug: string; name: string };
type Option = { key: string; label: string };

type PageProps = {
    wedding: { name: string };
    bands: Band[];
    cities: City[];
    vibes: Option[];
    seasons: Option[];
};

export default function WeddingDetailsOnboarding({ wedding, bands, cities, vibes, seasons }: PageProps) {
    const form = useForm({
        city: '' as string,
        band: '' as string,
        vibe: '' as string,
        season: '' as string,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/onboarding/wedding-details', { preserveScroll: true });
    };

    const skip = () => {
        router.post('/onboarding/wedding-details', { skip: true }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="A few quick things" />

            <div className="mx-auto w-full max-w-3xl px-4 py-10">
                <Heading
                    title={`Tell us about ${wedding.name}'s wedding`}
                    description="A few quick things — skip anything you're not sure about yet."
                />

                <Card className="mt-6">
                    <CardContent className="p-5">
                        <form onSubmit={submit} className="space-y-7">
                            <div>
                                <Label htmlFor="city" className="text-sm font-medium">Where's the wedding?</Label>
                                <Select value={form.data.city || undefined} onValueChange={(v) => form.setData('city', v)}>
                                    <SelectTrigger id="city" className="mt-2 sm:max-w-xs">
                                        <SelectValue placeholder="Choose a city" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {cities.map((c) => (
                                            <SelectItem key={c.slug} value={c.slug}>{c.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.city} />
                            </div>

                            <div>
                                <Label className="text-sm font-medium">What's your total budget?</Label>
                                <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-5">
                                    {bands.map((b) => {
                                        const active = form.data.band === b.key;
                                        return (
                                            <button
                                                key={b.key}
                                                type="button"
                                                onClick={() => form.setData('band', active ? '' : b.key)}
                                                className={`rounded-lg border px-2 py-2.5 text-center text-sm transition-colors ${
                                                    active
                                                        ? 'border-[#1b4638] bg-[#1b4638]/5 font-medium ring-1 ring-[#1b4638]'
                                                        : 'border-border hover:bg-muted'
                                                }`}
                                            >
                                                {b.label}
                                            </button>
                                        );
                                    })}
                                </div>
                                <InputError message={form.errors.band} />
                            </div>

                            <div>
                                <Label className="text-sm font-medium">What's your wedding vibe?</Label>
                                <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    {vibes.map((v) => {
                                        const active = form.data.vibe === v.key;
                                        return (
                                            <button
                                                key={v.key}
                                                type="button"
                                                onClick={() => form.setData('vibe', active ? '' : v.key)}
                                                className={`rounded-lg border px-2 py-2.5 text-center text-sm transition-colors ${
                                                    active
                                                        ? 'border-[#1b4638] bg-[#1b4638]/5 font-medium ring-1 ring-[#1b4638]'
                                                        : 'border-border hover:bg-muted'
                                                }`}
                                            >
                                                {v.label}
                                            </button>
                                        );
                                    })}
                                </div>
                                <InputError message={form.errors.vibe} />
                            </div>

                            <div>
                                <Label className="text-sm font-medium">Which season?</Label>
                                <div className="mt-2 grid grid-cols-4 gap-2 sm:max-w-sm">
                                    {seasons.map((s) => {
                                        const active = form.data.season === s.key;
                                        return (
                                            <button
                                                key={s.key}
                                                type="button"
                                                onClick={() => form.setData('season', active ? '' : s.key)}
                                                className={`rounded-lg border px-2 py-2.5 text-center text-sm transition-colors ${
                                                    active
                                                        ? 'border-[#1b4638] bg-[#1b4638]/5 font-medium ring-1 ring-[#1b4638]'
                                                        : 'border-border hover:bg-muted'
                                                }`}
                                            >
                                                {s.label}
                                            </button>
                                        );
                                    })}
                                </div>
                                <InputError message={form.errors.season} />
                            </div>

                            <div className="flex flex-col-reverse items-center gap-3 pt-2 sm:flex-row sm:justify-between">
                                <button
                                    type="button"
                                    onClick={skip}
                                    className="text-sm text-muted-foreground underline-offset-4 hover:underline"
                                >
                                    Skip for now
                                </button>
                                <Button type="submit" disabled={form.processing} className="w-full sm:w-auto">
                                    {form.processing && <Spinner />}
                                    Save and continue
                                    <ArrowRight className="size-4" />
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

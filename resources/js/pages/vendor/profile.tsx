import { useForm } from '@inertiajs/react';
import { Head, router } from '@inertiajs/react';
import {
    Camera,
    CheckCircle2,
    Globe,
    ImagePlus,
    Mail,
    MapPin,
    Phone,
    Store,
    Trash2,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { CA_PROVINCES } from '@/lib/provinces';

type MediaItem = {
    id: number;
    url: string;
    caption: string | null;
    alt_text: string | null;
    sort_order: number;
    original_name: string;
};

type Profile = {
    id: number;
    business_name: string;
    slug: string;
    category: string | null;
    tagline: string | null;
    description: string | null;
    logo_url: string | null;
    cover_url: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    service_area: string | null;
    base_price_cents: number | null;
    price_unit: string | null;
    website: string | null;
    video_url: string | null;
    brochure_url: string | null;
    phone: string | null;
    email: string | null;
    socials: { instagram?: string; facebook?: string; tiktok?: string };
    is_accepting_bookings: boolean;
    status: string;
    status_label: string;
    is_published: boolean;
    can_submit: boolean;
    media: MediaItem[];
};

type Category = { value: string; label: string };

type PageProps = {
    profile: Profile;
    categories: Category[];
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    published: 'default',
    pending_review: 'secondary',
    draft: 'outline',
    suspended: 'destructive',
};

const PRICE_UNITS = [
    { value: 'per_event', label: 'Per event' },
    { value: 'per_hour', label: 'Per hour' },
    { value: 'per_person', label: 'Per person' },
];

export default function VendorProfilePage({ profile, categories }: PageProps) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        business_name: profile.business_name,
        tagline: profile.tagline ?? '',
        description: profile.description ?? '',
        category: profile.category ?? '',
        city: profile.city ?? '',
        region: profile.region ?? '',
        country: profile.country ?? '',
        service_area: profile.service_area ?? '',
        base_price_cents: profile.base_price_cents ?? ('' as number | ''),
        price_unit: profile.price_unit ?? '',
        website: profile.website ?? '',
        video_url: profile.video_url ?? '',
        phone: profile.phone ?? '',
        email: profile.email ?? '',
        socials: {
            instagram: profile.socials?.instagram ?? '',
            facebook: profile.socials?.facebook ?? '',
            tiktok: profile.socials?.tiktok ?? '',
        },
        is_accepting_bookings: profile.is_accepting_bookings,
    });

    const logoRef = useRef<HTMLInputElement>(null);
    const coverRef = useRef<HTMLInputElement>(null);
    const mediaRef = useRef<HTMLInputElement>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(profile.logo_url);
    const [coverPreview, setCoverPreview] = useState<string | null>(profile.cover_url);

    function handleLogoChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        setLogoPreview(URL.createObjectURL(file));
        const form = new FormData();
        form.append('logo', file);
        router.post('/vendor/profile/logo', form, { preserveScroll: true });
    }

    function handleCoverChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        setCoverPreview(URL.createObjectURL(file));
        const form = new FormData();
        form.append('cover', file);
        router.post('/vendor/profile/cover', form, { preserveScroll: true });
    }

    function handleMediaUpload(e: React.ChangeEvent<HTMLInputElement>) {
        const files = Array.from(e.target.files ?? []);
        if (files.length === 0) return;
        const form = new FormData();
        files.forEach((f) => form.append('photos[]', f));
        router.post('/vendor/profile/media', form, { preserveScroll: true });
        e.target.value = '';
    }

    function deleteMedia(id: number) {
        router.delete(`/vendor/profile/media/${id}`, { preserveScroll: true });
    }

    function saveAlt(id: number, alt_text: string) {
        router.put(`/vendor/profile/media/${id}`, { alt_text }, { preserveScroll: true });
    }

    function moveMedia(index: number, dir: -1 | 1) {
        const next = [...profile.media];
        const target = index + dir;
        if (target < 0 || target >= next.length) return;
        [next[index], next[target]] = [next[target], next[index]];
        router.post('/vendor/profile/media/reorder', {
            items: next.map((m, i) => ({ id: m.id, sort_order: i + 1 })),
        }, { preserveScroll: true });
    }

    const brochureRef = useRef<HTMLInputElement>(null);
    function handleBrochure(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        const form = new FormData();
        form.append('brochure', file);
        router.post('/vendor/profile/brochure', form, { preserveScroll: true });
        e.target.value = '';
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        put('/vendor/profile', { preserveScroll: true });
    }

    function submitForReview() {
        router.post('/vendor/profile/submit', {}, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Business profile" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Business profile"
                        description="Your public listing in the marketplace."
                    />
                    <div className="flex items-center gap-3">
                        <Badge variant={STATUS_VARIANT[profile.status] ?? 'secondary'}>
                            {profile.status_label}
                        </Badge>
                        {profile.can_submit && (
                            <Button size="sm" onClick={submitForReview}>
                                <CheckCircle2 className="mr-1.5 size-4" />
                                Submit for review
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* ── Left column: images + media ── */}
                    <div className="flex flex-col gap-6 lg:col-span-1">
                        {/* Cover photo */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">Cover photo</CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div
                                    className="relative cursor-pointer overflow-hidden rounded-b-lg bg-muted"
                                    style={{ aspectRatio: '16/9' }}
                                    onClick={() => coverRef.current?.click()}
                                >
                                    {coverPreview ? (
                                        <img
                                            src={coverPreview}
                                            alt="Cover"
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-full items-center justify-center text-muted-foreground">
                                            <Camera className="size-8" />
                                        </div>
                                    )}
                                    <div className="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity hover:opacity-100">
                                        <Upload className="size-6 text-white" />
                                    </div>
                                </div>
                                <input ref={coverRef} type="file" accept="image/*" className="hidden" onChange={handleCoverChange} />
                            </CardContent>
                        </Card>

                        {/* Logo */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">Logo</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center gap-4">
                                    <div
                                        className="relative size-20 cursor-pointer overflow-hidden rounded-xl border bg-muted"
                                        onClick={() => logoRef.current?.click()}
                                    >
                                        {logoPreview ? (
                                            <img src={logoPreview} alt="Logo" className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="flex h-full items-center justify-center">
                                                <Store className="size-8 text-muted-foreground" />
                                            </div>
                                        )}
                                        <div className="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity hover:opacity-100">
                                            <Upload className="size-4 text-white" />
                                        </div>
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        <p>PNG, JPG, WebP</p>
                                        <p>Max 5 MB</p>
                                        <button
                                            type="button"
                                            className="mt-1 text-primary underline underline-offset-2"
                                            onClick={() => logoRef.current?.click()}
                                        >
                                            Upload
                                        </button>
                                    </div>
                                </div>
                                <input ref={logoRef} type="file" accept="image/*" className="hidden" onChange={handleLogoChange} />
                            </CardContent>
                        </Card>

                        {/* Gallery */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">Gallery photos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-col gap-3">
                                    {profile.media.map((m, i) => (
                                        <div key={m.id} className="flex gap-2 rounded-lg border p-2">
                                            <img src={m.url} alt={m.alt_text ?? ''} className="size-16 shrink-0 rounded-md object-cover" />
                                            <div className="flex flex-1 flex-col gap-1">
                                                <Input
                                                    defaultValue={m.alt_text ?? ''}
                                                    placeholder="Alt text (describe the photo for SEO)"
                                                    className="h-8 text-xs"
                                                    onBlur={(e) => { if (e.target.value !== (m.alt_text ?? '')) saveAlt(m.id, e.target.value); }}
                                                />
                                                <div className="flex items-center gap-1">
                                                    <button type="button" onClick={() => moveMedia(i, -1)} disabled={i === 0} className="rounded p-1 text-muted-foreground hover:text-foreground disabled:opacity-30">↑</button>
                                                    <button type="button" onClick={() => moveMedia(i, 1)} disabled={i === profile.media.length - 1} className="rounded p-1 text-muted-foreground hover:text-foreground disabled:opacity-30">↓</button>
                                                    <button type="button" onClick={() => deleteMedia(m.id)} className="ml-auto rounded p-1 text-destructive hover:bg-destructive/10">
                                                        <Trash2 className="size-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => mediaRef.current?.click()}
                                        className="flex items-center justify-center gap-2 rounded-lg border-2 border-dashed py-3 text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                                    >
                                        <ImagePlus className="size-5" /> Add photos
                                    </button>
                                </div>
                                <input ref={mediaRef} type="file" accept="image/*" multiple className="hidden" onChange={handleMediaUpload} />
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {profile.media.length} photo{profile.media.length !== 1 ? 's' : ''} · drag order with ↑↓
                                </p>
                            </CardContent>
                        </Card>

                        {/* Brochure (PDF) */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">Brochure / package PDF</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2">
                                {profile.brochure_url && (
                                    <a href={profile.brochure_url} target="_blank" rel="noreferrer" className="text-sm text-[#1f5142] underline">
                                        Current brochure (PDF)
                                    </a>
                                )}
                                <input ref={brochureRef} type="file" accept="application/pdf" className="hidden" onChange={handleBrochure} />
                                <div className="flex gap-2">
                                    <Button type="button" variant="outline" size="sm" onClick={() => brochureRef.current?.click()}>
                                        {profile.brochure_url ? 'Replace PDF' : 'Upload PDF'}
                                    </Button>
                                    {profile.brochure_url && (
                                        <Button type="button" variant="ghost" size="sm" onClick={() => router.delete('/vendor/profile/brochure', { preserveScroll: true })}>
                                            Remove
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* ── Right column: form ── */}
                    <div className="lg:col-span-2">
                        <form onSubmit={submit} className="flex flex-col gap-6">
                            {/* Basic info */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Basic information</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <Label htmlFor="business_name">Business name</Label>
                                        <Input
                                            id="business_name"
                                            value={data.business_name}
                                            onChange={(e) => setData('business_name', e.target.value)}
                                            className="mt-1"
                                        />
                                        {errors.business_name && <p className="mt-1 text-sm text-destructive">{errors.business_name}</p>}
                                    </div>

                                    <div className="sm:col-span-2">
                                        <Label htmlFor="tagline">Tagline</Label>
                                        <Input
                                            id="tagline"
                                            value={data.tagline}
                                            onChange={(e) => setData('tagline', e.target.value)}
                                            placeholder="Short, punchy description"
                                            className="mt-1"
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="category">Category</Label>
                                        <Select
                                            value={data.category}
                                            onValueChange={(v: string) => setData('category', v)}
                                        >
                                            <SelectTrigger id="category" className="mt-1">
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories.map((c) => (
                                                    <SelectItem key={c.value} value={c.value}>
                                                        {c.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.category && <p className="mt-1 text-sm text-destructive">{errors.category}</p>}
                                    </div>

                                    <div className="flex items-center gap-3 self-end pb-1">
                                        <Checkbox
                                            id="is_accepting_bookings"
                                            checked={data.is_accepting_bookings}
                                            onCheckedChange={(v: boolean) => setData('is_accepting_bookings', v)}
                                        />
                                        <Label htmlFor="is_accepting_bookings">Accepting bookings</Label>
                                    </div>

                                    <div className="sm:col-span-2">
                                        <Label htmlFor="description">About your business</Label>
                                        <Textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            rows={5}
                                            placeholder="Tell couples about your experience, style, and what makes you unique…"
                                            className="mt-1"
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Location */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <MapPin className="size-4" /> Location
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label htmlFor="city">City</Label>
                                        <Input
                                            id="city"
                                            value={data.city}
                                            onChange={(e) => setData('city', e.target.value)}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="region">Province</Label>
                                        <Select
                                            value={data.region}
                                            onValueChange={(v: string) => setData('region', v)}
                                        >
                                            <SelectTrigger id="region" className="mt-1">
                                                <SelectValue placeholder="Province" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {CA_PROVINCES.map((p) => (
                                                    <SelectItem key={p.value} value={p.value}>
                                                        {p.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label htmlFor="country">Country code</Label>
                                        <Input
                                            id="country"
                                            value={data.country}
                                            onChange={(e) => setData('country', e.target.value)}
                                            placeholder="CA"
                                            maxLength={2}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="service_area">Service area</Label>
                                        <Input
                                            id="service_area"
                                            value={data.service_area}
                                            onChange={(e) => setData('service_area', e.target.value)}
                                            placeholder="e.g. Greater Toronto Area"
                                            className="mt-1"
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Pricing */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Starting price</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label htmlFor="base_price_cents">Amount (CAD cents)</Label>
                                        <Input
                                            id="base_price_cents"
                                            type="number"
                                            min={0}
                                            value={data.base_price_cents === '' ? '' : data.base_price_cents}
                                            onChange={(e) => setData('base_price_cents', e.target.value === '' ? '' : Number(e.target.value))}
                                            placeholder="e.g. 250000 for $2,500"
                                            className="mt-1"
                                        />
                                        {data.base_price_cents !== '' && Number(data.base_price_cents) > 0 && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                = ${(Number(data.base_price_cents) / 100).toLocaleString('en-CA')} CAD
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <Label htmlFor="price_unit">Price unit</Label>
                                        <Select
                                            value={data.price_unit}
                                            onValueChange={(v: string) => setData('price_unit', v)}
                                        >
                                            <SelectTrigger id="price_unit" className="mt-1">
                                                <SelectValue placeholder="Select unit" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {PRICE_UNITS.map((u) => (
                                                    <SelectItem key={u.value} value={u.value}>
                                                        {u.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Contact */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Phone className="size-4" /> Contact &amp; links
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="email">Business email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <Label htmlFor="website">
                                            <Globe className="mr-1 inline size-3.5" />Website
                                        </Label>
                                        <Input
                                            id="website"
                                            type="url"
                                            value={data.website}
                                            onChange={(e) => setData('website', e.target.value)}
                                            placeholder="https://"
                                            className="mt-1"
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <Label htmlFor="video_url">Portfolio video (YouTube or Vimeo)</Label>
                                        <Input
                                            id="video_url"
                                            type="url"
                                            value={data.video_url}
                                            onChange={(e) => setData('video_url', e.target.value)}
                                            placeholder="https://www.youtube.com/watch?v=…"
                                            className="mt-1"
                                        />
                                        <InputError message={errors.video_url} className="mt-1" />
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Socials */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Social media</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-4 sm:grid-cols-3">
                                    {(['instagram', 'facebook', 'tiktok'] as const).map((platform) => (
                                        <div key={platform}>
                                            <Label htmlFor={`social_${platform}`} className="capitalize">{platform}</Label>
                                            <Input
                                                id={`social_${platform}`}
                                                value={data.socials[platform]}
                                                onChange={(e) =>
                                                    setData('socials', { ...data.socials, [platform]: e.target.value })
                                                }
                                                placeholder={`@handle`}
                                                className="mt-1"
                                            />
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>

                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving…' : 'Save profile'}
                                </Button>
                                {recentlySuccessful && (
                                    <p className="flex items-center gap-1.5 text-sm text-[#1b4638]">
                                        <CheckCircle2 className="size-4" /> Saved
                                    </p>
                                )}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

VendorProfilePage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/vendor' },
        { title: 'Business profile', href: '/vendor/profile' },
    ],
};

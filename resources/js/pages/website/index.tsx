import { Head, router, useForm } from '@inertiajs/react';
import {
    DndContext,
    DragEndEvent,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    rectSortingStrategy,
    useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    CheckCircle2,
    ExternalLink,
    GripVertical,
    Globe,
    Image,
    Loader2,
    Music,
    Pencil,
    Plus,
    Trash2,
    Upload,
    Video,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';

type TimelineItem = { year: string; title: string; body: string };
type Photo = { id: number; url: string; caption: string | null; sort_order: number };

type TemplateId = 'classic' | 'modern' | 'botanical' | 'blush' | 'royal' | 'dolce' | 'destination' | 'vibrant';

type Website = {
    is_published: boolean;
    subdomain: string | null;
    template: TemplateId;
    headline: string | null;
    welcome_message: string | null;
    our_story: string | null;
    venue_name: string | null;
    venue_address: string | null;
    ceremony_time: string | null;
    dress_code: string | null;
    hero_image_url: string | null;
    hero_image_path: string | null;
    hero_image_preview: string | null;
    hero_video_url: string | null;
    story_image_path: string | null;
    story_image_preview: string | null;
    timeline_items: TimelineItem[];
    video_url: string | null;
    music_path: string | null;
    music_title: string | null;
    music_url: string | null;
    photos: Photo[];
};

type PageProps = {
    website: Website;
    public_url: string;
    can_publish: boolean;
    subdomain_base: string;
    subdomain_enabled: boolean;
};

const TEMPLATES: Array<{
    id: TemplateId;
    label: string;
    bg: string;
    accent: string;
    font: string;
}> = [
    { id: 'classic', label: 'Classic', bg: '#fff8f3', accent: '#775a19', font: 'Playfair Display' },
    { id: 'modern', label: 'Modern', bg: '#f9f9f9', accent: '#1a1a1a', font: 'system-ui' },
    { id: 'botanical', label: 'Botanical', bg: '#f4f7f0', accent: '#4a7c59', font: 'Playfair Display' },
    { id: 'blush', label: 'Blush', bg: '#fdf6f4', accent: '#b06a78', font: 'Playfair Display' },
    { id: 'royal', label: 'Royal Gold', bg: '#fbf8f0', accent: '#b8902f', font: 'Playfair Display' },
    { id: 'dolce', label: 'Dolce', bg: '#fdf6ee', accent: '#c2603d', font: 'Playfair Display' },
    { id: 'destination', label: 'Destination', bg: '#f3f7f8', accent: '#3d7a8c', font: 'Playfair Display' },
    { id: 'vibrant', label: 'Vibrant', bg: '#fff5f2', accent: '#d2436a', font: 'Playfair Display' },
];

export default function WebsiteIndex({ website, public_url, can_publish, subdomain_base, subdomain_enabled }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('website');

    const form = useForm({
        is_published: website.is_published,
        template: website.template,
        headline: website.headline ?? '',
        welcome_message: website.welcome_message ?? '',
        our_story: website.our_story ?? '',
        venue_name: website.venue_name ?? '',
        venue_address: website.venue_address ?? '',
        ceremony_time: website.ceremony_time ?? '',
        dress_code: website.dress_code ?? '',
        hero_image_url: website.hero_image_url ?? '',
        hero_video_url: website.hero_video_url ?? '',
        video_url: website.video_url ?? '',
        music_title: website.music_title ?? '',
        timeline_items: website.timeline_items,
    });

    // Gallery photos are managed separately via dedicated endpoints.
    const [photos, setPhotos] = useState<Photo[]>(website.photos);
    const [heroPreview, setHeroPreview] = useState<string | null>(website.hero_image_preview);
    const [storyPreview, setStoryPreview] = useState<string | null>(website.story_image_preview);
    const [musicUrl, setMusicUrl] = useState<string | null>(website.music_url);
    const [uploading, setUploading] = useState<Record<string, boolean>>({});
    const [selecting, setSelecting] = useState(false);
    const [selectedPhotos, setSelectedPhotos] = useState<Set<number>>(new Set());
    const [captionEdit, setCaptionEdit] = useState<Photo | null>(null);
    const [captionText, setCaptionText] = useState('');

    // Timeline editor state
    const [newItem, setNewItem] = useState<TimelineItem>({ year: '', title: '', body: '' });

    // Subdomain (free web address) state + live availability check.
    const subForm = useForm({ subdomain: website.subdomain ?? '' });
    const [availability, setAvailability] = useState<'idle' | 'checking' | 'ok' | 'taken' | 'reserved' | 'invalid'>('idle');
    const subValue = subForm.data.subdomain;

    useEffect(() => {
        const v = subValue.trim().toLowerCase();
        if (!subdomain_enabled || v === '' || v === (website.subdomain ?? '')) {
            setAvailability('idle');
            return;
        }
        setAvailability('checking');
        const id = setTimeout(() => {
            fetch(`/website/subdomain/check?value=${encodeURIComponent(v)}`, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((d: { available: boolean; reason: string }) => {
                    setAvailability(d.available ? 'ok' : (d.reason as 'taken' | 'reserved' | 'invalid'));
                })
                .catch(() => setAvailability('idle'));
        }, 400);
        return () => clearTimeout(id);
    }, [subValue, subdomain_enabled, website.subdomain]);

    function saveSubdomain(e: React.FormEvent) {
        e.preventDefault();
        subForm.transform((d) => ({ subdomain: d.subdomain.trim().toLowerCase() || null }));
        subForm.put('/website/subdomain', {
            preserveScroll: true,
            onSuccess: () => { setAvailability('idle'); toast.success('Web address saved.'); },
        });
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put('/website', {
            preserveScroll: true,
            onSuccess: () => toast.success('Website saved.'),
        });
    }

    function pickTemplate(id: typeof form.data.template) {
        form.setData('template', id);
        router.put('/website', { ...form.data, template: id } as unknown as Record<string, string>, {
            preserveScroll: true,
            onSuccess: () => toast.success('Template saved.'),
        });
    }

    function uploadFile(fieldName: string, route: string, file: File) {
        setUploading((u) => ({ ...u, [fieldName]: true }));
        const fd = new FormData();
        fd.append(fieldName, file);
        router.post(route, fd as unknown as Record<string, string>, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Image uploaded.');
                if (fieldName === 'hero') setHeroPreview(URL.createObjectURL(file));
                if (fieldName === 'story_image') setStoryPreview(URL.createObjectURL(file));
            },
            onFinish: () => setUploading((u) => ({ ...u, [fieldName]: false })),
        });
    }

    function uploadMusic(file: File) {
        setUploading((u) => ({ ...u, music: true }));
        const fd = new FormData();
        fd.append('music', file);
        router.post('/website/music', fd as unknown as Record<string, string>, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const w = (page.props as unknown as PageProps).website;
                setMusicUrl(w.music_url);
                form.setData('music_title', w.music_title ?? '');
                toast.success('Song uploaded.');
            },
            onError: () => toast.error('Could not upload that file. Use an MP3, M4A, or WAV up to 10 MB.'),
            onFinish: () => setUploading((u) => ({ ...u, music: false })),
        });
    }

    function removeMusic() {
        router.delete('/website/music', {
            preserveScroll: true,
            onSuccess: () => {
                setMusicUrl(null);
                form.setData('music_title', '');
                toast.success('Song removed.');
            },
        });
    }

    function uploadGalleryPhoto(file: File) {
        setUploading((u) => ({ ...u, gallery: true }));
        const fd = new FormData();
        fd.append('photo', file);
        router.post('/website/gallery', fd as unknown as Record<string, string>, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const fresh = (page.props as unknown as PageProps).website.photos;
                setPhotos(fresh);
                toast.success('Photo added.');
            },
            onFinish: () => setUploading((u) => ({ ...u, gallery: false })),
        });
    }

    function deletePhoto(id: number) {
        router.delete(`/website/gallery/${id}`, {
            preserveScroll: true,
            onSuccess: (page) => {
                const fresh = (page.props as unknown as PageProps).website.photos;
                setPhotos(fresh);
            },
        });
    }

    function toggleSelectPhoto(id: number) {
        setSelectedPhotos((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function exitSelecting() {
        setSelecting(false);
        setSelectedPhotos(new Set());
    }

    function deleteSelectedPhotos() {
        if (selectedPhotos.size === 0) return;
        if (!confirm(`Delete ${selectedPhotos.size} selected photo${selectedPhotos.size > 1 ? 's' : ''}?`)) return;
        router.post(
            '/website/gallery/bulk-delete',
            { ids: Array.from(selectedPhotos) } as unknown as Record<string, string>,
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    setPhotos((page.props as unknown as PageProps).website.photos);
                    exitSelecting();
                    toast.success('Photos deleted.');
                },
            },
        );
    }

    function openCaptionEdit(photo: Photo) {
        setCaptionText(photo.caption ?? '');
        setCaptionEdit(photo);
    }

    function saveCaption() {
        if (!captionEdit) return;
        router.put(
            `/website/gallery/${captionEdit.id}`,
            { caption: captionText } as unknown as Record<string, string>,
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    setPhotos((page.props as unknown as PageProps).website.photos);
                    setCaptionEdit(null);
                    toast.success('Caption saved.');
                },
            },
        );
    }

    const sensors = useSensors(useSensor(PointerSensor));

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;
        if (!over || active.id === over.id) return;
        const oldIdx = photos.findIndex((p) => p.id === active.id);
        const newIdx = photos.findIndex((p) => p.id === over.id);
        const reordered = arrayMove(photos, oldIdx, newIdx).map((p, i) => ({
            ...p,
            sort_order: i,
        }));
        setPhotos(reordered);
        router.post(
            '/website/gallery/reorder',
            { items: reordered.map(({ id, sort_order }) => ({ id, sort_order })) } as unknown as Record<string, string>,
            { preserveScroll: true },
        );
    }

    function addTimelineItem() {
        if (!newItem.year || !newItem.title) return;
        form.setData('timeline_items', [...form.data.timeline_items, newItem]);
        setNewItem({ year: '', title: '', body: '' });
    }

    function removeTimelineItem(i: number) {
        form.setData(
            'timeline_items',
            form.data.timeline_items.filter((_, idx) => idx !== i),
        );
    }

    return (
        <>
            <Head title="Wedding website" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Wedding website"
                        description="Craft the public page your guests see at your wedding's link."
                    />
                    <Button variant="outline" asChild>
                        <a href={public_url} target="_blank" rel="noopener noreferrer">
                            <ExternalLink className="size-4" />
                            View site
                        </a>
                    </Button>
                </div>

                {/* Publish toggle */}
                <Card>
                    <CardContent className="flex items-center gap-3">
                        <Checkbox
                            id="is_published"
                            checked={can_publish && form.data.is_published}
                            onCheckedChange={(v) => form.setData('is_published', v === true)}
                            disabled={!writable || !can_publish}
                        />
                        <div>
                            <Label htmlFor="is_published" className="flex items-center gap-2">
                                <Globe className="size-4" />
                                Publish website
                            </Label>
                            {can_publish ? (
                                <p className="text-sm text-muted-foreground">
                                    When off, visitors see a simple page with an RSVP link only.
                                </p>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Going live is an{' '}
                                    <a href="/settings/plan" className="font-medium text-[#8a651c] underline">
                                        Atelier feature
                                    </a>{' '}
                                    — upgrade to publish. Keep building in the meantime; your draft is saved.
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Your web address (free subdomain) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Globe className="size-4" /> Your web address
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {!subdomain_enabled ? (
                            <p className="text-sm text-muted-foreground">
                                Claim a free, easy-to-share address like{' '}
                                <span className="font-medium text-[#1e1b17]">amelia-and-julian.{subdomain_base}</span> — an{' '}
                                <a href="/settings/plan" className="font-medium text-[#8a651c] underline">Atelier feature</a>.
                            </p>
                        ) : (
                            <form onSubmit={saveSubdomain} className="flex flex-col gap-3">
                                <p className="text-sm text-muted-foreground">
                                    A short, shareable link to your site. Lowercase letters, numbers and hyphens.
                                </p>
                                <div className="flex flex-wrap items-stretch gap-2">
                                    <div className="flex flex-1 items-center overflow-hidden rounded-md border focus-within:ring-1 focus-within:ring-[#775a19]">
                                        <input
                                            value={subForm.data.subdomain}
                                            onChange={(e) => subForm.setData('subdomain', e.target.value.toLowerCase())}
                                            placeholder="amelia-and-julian"
                                            disabled={!writable}
                                            className="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm outline-none"
                                        />
                                        <span className="bg-muted px-3 py-2 text-sm text-muted-foreground">.{subdomain_base}</span>
                                    </div>
                                    <Button type="submit" disabled={!writable || subForm.processing || availability === 'taken' || availability === 'reserved' || availability === 'invalid'}>
                                        {subForm.processing ? <Spinner /> : null} Save
                                    </Button>
                                </div>
                                {subForm.errors.subdomain && <p className="text-sm text-red-600">{subForm.errors.subdomain}</p>}
                                {availability === 'checking' && <p className="text-xs text-muted-foreground">Checking availability…</p>}
                                {availability === 'ok' && <p className="text-xs text-green-700">✓ {subForm.data.subdomain}.{subdomain_base} is available.</p>}
                                {availability === 'taken' && <p className="text-xs text-red-600">That address is already taken.</p>}
                                {availability === 'reserved' && <p className="text-xs text-red-600">That address is reserved — choose another.</p>}
                                {availability === 'invalid' && <p className="text-xs text-red-600">Use 3+ lowercase letters, numbers or hyphens.</p>}
                                {website.subdomain && availability === 'idle' && (
                                    <p className="text-xs text-muted-foreground">
                                        Live at{' '}
                                        <a href={`https://${website.subdomain}.${subdomain_base}`} target="_blank" rel="noopener noreferrer" className="font-medium text-[#8a651c] underline">
                                            {website.subdomain}.{subdomain_base}
                                        </a>{' '}
                                        once your site is published.
                                    </p>
                                )}
                            </form>
                        )}
                    </CardContent>
                </Card>

                {/* Template picker */}
                <Card>
                    <CardHeader>
                        <CardTitle>Template</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {TEMPLATES.map((t) => (
                                <button
                                    key={t.id}
                                    type="button"
                                    disabled={!writable}
                                    onClick={() => pickTemplate(t.id)}
                                    className={`relative overflow-hidden rounded-lg border-2 p-4 text-left transition-all ${
                                        form.data.template === t.id
                                            ? 'border-[#775a19] shadow-md'
                                            : 'border-transparent hover:border-muted-foreground/30'
                                    }`}
                                    style={{ background: t.bg }}
                                >
                                    <span
                                        className="block text-xs font-semibold tracking-wide"
                                        style={{ color: t.accent, fontFamily: t.font }}
                                    >
                                        Aa
                                    </span>
                                    <div
                                        className="mt-1 h-1 w-8 rounded-full"
                                        style={{ background: t.accent }}
                                    />
                                    <span className="mt-2 block text-xs font-medium" style={{ color: t.accent }}>
                                        {t.label}
                                    </span>
                                    {form.data.template === t.id && (
                                        <span className="absolute right-2 top-2 size-2 rounded-full bg-[#775a19]" />
                                    )}
                                </button>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    {/* Hero */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Hero</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <div className="flex flex-col gap-2">
                                <Label>Hero image</Label>
                                {heroPreview && (
                                    <div className="relative aspect-video w-full overflow-hidden rounded-lg bg-muted">
                                        <img
                                            src={heroPreview}
                                            alt="Hero preview"
                                            className="size-full object-cover"
                                        />
                                    </div>
                                )}
                                <ImageUploadButton
                                    disabled={!writable}
                                    uploading={!!uploading.hero}
                                    onFile={(f) => uploadFile('hero', '/website/hero', f)}
                                />
                                {!website.hero_image_path && (
                                    <Field label="Or paste a URL" error={form.errors.hero_image_url}>
                                        <Input
                                            value={form.data.hero_image_url}
                                            onChange={(e) => form.setData('hero_image_url', e.target.value)}
                                            placeholder="https://…"
                                            disabled={!writable}
                                        />
                                    </Field>
                                )}
                            </div>
                            <Field label="Hero video (YouTube or Vimeo URL)" error={form.errors.hero_video_url}>
                                <Input
                                    value={form.data.hero_video_url}
                                    onChange={(e) => form.setData('hero_video_url', e.target.value)}
                                    placeholder="https://www.youtube.com/watch?v=…"
                                    disabled={!writable}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    {/* Welcome */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Welcome</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <Field label="Headline" error={form.errors.headline}>
                                <Input
                                    value={form.data.headline}
                                    onChange={(e) => form.setData('headline', e.target.value)}
                                    placeholder="We're getting married!"
                                    disabled={!writable}
                                />
                            </Field>
                            <Field label="Welcome message" error={form.errors.welcome_message}>
                                <Textarea
                                    value={form.data.welcome_message}
                                    onChange={(e) => form.setData('welcome_message', e.target.value)}
                                    disabled={!writable}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    {/* Our story */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Our story</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <Field label="Tell your story" error={form.errors.our_story}>
                                <Textarea
                                    value={form.data.our_story}
                                    onChange={(e) => form.setData('our_story', e.target.value)}
                                    rows={6}
                                    disabled={!writable}
                                />
                            </Field>
                            <div className="flex flex-col gap-2">
                                <Label>Story image</Label>
                                {storyPreview && (
                                    <div className="relative aspect-[3/4] max-w-xs overflow-hidden rounded-lg bg-muted">
                                        <img
                                            src={storyPreview}
                                            alt="Story preview"
                                            className="size-full object-cover"
                                        />
                                    </div>
                                )}
                                <ImageUploadButton
                                    disabled={!writable}
                                    uploading={!!uploading.story_image}
                                    onFile={(f) => uploadFile('story_image', '/website/story-image', f)}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Timeline */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Our timeline</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            {form.data.timeline_items.length > 0 && (
                                <div className="flex flex-col gap-2">
                                    {form.data.timeline_items.map((item, i) => (
                                        <div
                                            key={i}
                                            className="flex items-start gap-3 rounded-lg border p-3"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="text-xs font-semibold text-[#775a19]">
                                                    {item.year}
                                                </p>
                                                <p className="text-sm font-medium">{item.title}</p>
                                                {item.body && (
                                                    <p className="text-xs text-muted-foreground">{item.body}</p>
                                                )}
                                            </div>
                                            {writable && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="shrink-0 text-destructive hover:text-destructive"
                                                    onClick={() => removeTimelineItem(i)}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                            {writable && (
                                <div className="grid gap-3 rounded-lg border p-3 sm:grid-cols-3">
                                    <Input
                                        placeholder="Year (e.g. 2021)"
                                        value={newItem.year}
                                        onChange={(e) => setNewItem((n) => ({ ...n, year: e.target.value }))}
                                    />
                                    <Input
                                        placeholder="Title (e.g. The proposal)"
                                        value={newItem.title}
                                        onChange={(e) => setNewItem((n) => ({ ...n, title: e.target.value }))}
                                    />
                                    <Input
                                        placeholder="Short description (optional)"
                                        value={newItem.body}
                                        onChange={(e) => setNewItem((n) => ({ ...n, body: e.target.value }))}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="sm:col-span-3"
                                        onClick={addTimelineItem}
                                        disabled={!newItem.year || !newItem.title}
                                    >
                                        <Plus className="mr-1.5 size-4" />
                                        Add moment
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Video section */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Video className="size-4" />
                                Video
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Field label="YouTube or Vimeo URL" error={form.errors.video_url}>
                                <Input
                                    value={form.data.video_url}
                                    onChange={(e) => form.setData('video_url', e.target.value)}
                                    placeholder="https://www.youtube.com/watch?v=…"
                                    disabled={!writable}
                                />
                            </Field>
                            <p className="mt-1.5 text-xs text-muted-foreground">
                                A full-width video section will appear on your page when a URL is added.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Music */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Music className="size-4" />
                                Background music
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <p className="text-sm text-muted-foreground">
                                Add your favourite song and it plays softly when a guest opens the
                                invitation. Guests can mute it any time.
                            </p>

                            {musicUrl ? (
                                <div className="flex flex-col gap-3 rounded-lg border p-3">
                                    <audio controls src={musicUrl} className="w-full" />
                                    <Field label="Song title (shown to guests)" error={form.errors.music_title}>
                                        <Input
                                            value={form.data.music_title}
                                            onChange={(e) => form.setData('music_title', e.target.value)}
                                            placeholder="Our song"
                                            disabled={!writable}
                                        />
                                    </Field>
                                    {writable && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="w-fit text-destructive hover:text-destructive"
                                            onClick={removeMusic}
                                        >
                                            <Trash2 className="mr-1.5 size-4" />
                                            Remove song
                                        </Button>
                                    )}
                                </div>
                            ) : (
                                writable && (
                                    <AudioUploadButton uploading={!!uploading.music} onFile={uploadMusic} />
                                )
                            )}
                        </CardContent>
                    </Card>

                    {/* Gallery */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Image className="size-4" />
                                Gallery
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            {writable && photos.length > 0 && (
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <p className="text-xs text-muted-foreground">
                                        {selecting
                                            ? `${selectedPhotos.size} selected`
                                            : 'Drag to reorder · hover a photo to caption or remove it.'}
                                    </p>
                                    <div className="flex items-center gap-2">
                                        {selecting && (
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                disabled={selectedPhotos.size === 0}
                                                onClick={deleteSelectedPhotos}
                                            >
                                                <Trash2 className="mr-1.5 size-3.5" />
                                                Delete selected
                                            </Button>
                                        )}
                                        <Button
                                            type="button"
                                            variant={selecting ? 'default' : 'outline'}
                                            size="sm"
                                            onClick={() => (selecting ? exitSelecting() : setSelecting(true))}
                                        >
                                            <CheckCircle2 className="mr-1.5 size-3.5" />
                                            {selecting ? 'Done' : 'Select'}
                                        </Button>
                                    </div>
                                </div>
                            )}
                            <DndContext
                                sensors={sensors}
                                collisionDetection={closestCenter}
                                onDragEnd={handleDragEnd}
                            >
                                <SortableContext
                                    items={photos.map((p) => p.id)}
                                    strategy={rectSortingStrategy}
                                >
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                        {photos.map((photo) => (
                                            <SortablePhoto
                                                key={photo.id}
                                                photo={photo}
                                                onDelete={deletePhoto}
                                                onEdit={openCaptionEdit}
                                                selecting={selecting}
                                                selected={selectedPhotos.has(photo.id)}
                                                onToggleSelect={toggleSelectPhoto}
                                                disabled={!writable}
                                            />
                                        ))}
                                    </div>
                                </SortableContext>
                            </DndContext>

                            {writable && (
                                <GalleryUploadButton
                                    uploading={!!uploading.gallery}
                                    onFile={uploadGalleryPhoto}
                                />
                            )}
                        </CardContent>
                    </Card>

                    {/* Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <Field label="Venue name" error={form.errors.venue_name}>
                                <Input
                                    value={form.data.venue_name}
                                    onChange={(e) => form.setData('venue_name', e.target.value)}
                                    disabled={!writable}
                                />
                            </Field>
                            <Field label="Venue address" error={form.errors.venue_address}>
                                <Input
                                    value={form.data.venue_address}
                                    onChange={(e) => form.setData('venue_address', e.target.value)}
                                    disabled={!writable}
                                />
                            </Field>
                            <Field label="Ceremony time" error={form.errors.ceremony_time}>
                                <Input
                                    value={form.data.ceremony_time}
                                    onChange={(e) => form.setData('ceremony_time', e.target.value)}
                                    placeholder="4:00 PM"
                                    disabled={!writable}
                                />
                            </Field>
                            <Field label="Dress code" error={form.errors.dress_code}>
                                <Input
                                    value={form.data.dress_code}
                                    onChange={(e) => form.setData('dress_code', e.target.value)}
                                    placeholder="Garden formal"
                                    disabled={!writable}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    {writable && (
                        <div className="flex justify-end">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Loader2 className="mr-1.5 size-4 animate-spin" />}
                                Save website
                            </Button>
                        </div>
                    )}
                </form>
            </div>

            {/* Caption editor */}
            {captionEdit && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-6"
                    onClick={() => setCaptionEdit(null)}
                >
                    <div
                        className="w-full max-w-sm rounded-xl bg-background p-5 shadow-xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="text-sm font-semibold">Edit caption</h3>
                            <button type="button" onClick={() => setCaptionEdit(null)} aria-label="Close">
                                <X className="size-4 text-muted-foreground" />
                            </button>
                        </div>
                        <img
                            src={captionEdit.url}
                            alt=""
                            className="mb-3 aspect-video w-full rounded-lg object-cover"
                        />
                        <Input
                            value={captionText}
                            onChange={(e) => setCaptionText(e.target.value)}
                            placeholder="Add a caption…"
                            autoFocus
                            onKeyDown={(e) => e.key === 'Enter' && saveCaption()}
                        />
                        <div className="mt-4 flex justify-end gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={() => setCaptionEdit(null)}>
                                Cancel
                            </Button>
                            <Button type="button" size="sm" onClick={saveCaption}>
                                Save caption
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

// ── helpers ──────────────────────────────────────────────────────────────────

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

function ImageUploadButton({
    disabled,
    uploading,
    onFile,
}: {
    disabled: boolean;
    uploading: boolean;
    onFile: (f: File) => void;
}) {
    const ref = useRef<HTMLInputElement>(null);
    return (
        <>
            <input
                ref={ref}
                type="file"
                accept="image/*"
                className="sr-only"
                onChange={(e) => {
                    const f = e.target.files?.[0];
                    if (f) onFile(f);
                    e.target.value = '';
                }}
            />
            <Button
                type="button"
                variant="outline"
                disabled={disabled || uploading}
                onClick={() => ref.current?.click()}
                className="w-fit"
            >
                {uploading ? (
                    <Loader2 className="mr-1.5 size-4 animate-spin" />
                ) : (
                    <Upload className="mr-1.5 size-4" />
                )}
                {uploading ? 'Uploading…' : 'Upload image'}
            </Button>
        </>
    );
}

function AudioUploadButton({
    uploading,
    onFile,
}: {
    uploading: boolean;
    onFile: (f: File) => void;
}) {
    const ref = useRef<HTMLInputElement>(null);
    return (
        <>
            <input
                ref={ref}
                type="file"
                accept="audio/*"
                className="sr-only"
                onChange={(e) => {
                    const f = e.target.files?.[0];
                    if (f) onFile(f);
                    e.target.value = '';
                }}
            />
            <Button
                type="button"
                variant="outline"
                disabled={uploading}
                onClick={() => ref.current?.click()}
                className="w-fit"
            >
                {uploading ? (
                    <Loader2 className="mr-1.5 size-4 animate-spin" />
                ) : (
                    <Upload className="mr-1.5 size-4" />
                )}
                {uploading ? 'Uploading…' : 'Upload song'}
            </Button>
        </>
    );
}

function GalleryUploadButton({
    uploading,
    onFile,
}: {
    uploading: boolean;
    onFile: (f: File) => void;
}) {
    const ref = useRef<HTMLInputElement>(null);
    return (
        <>
            <input
                ref={ref}
                type="file"
                accept="image/*"
                multiple
                className="sr-only"
                onChange={(e) => {
                    const files = Array.from(e.target.files ?? []);
                    files.forEach(onFile);
                    e.target.value = '';
                }}
            />
            <Button
                type="button"
                variant="outline"
                disabled={uploading}
                onClick={() => ref.current?.click()}
                className="w-fit"
            >
                {uploading ? (
                    <Loader2 className="mr-1.5 size-4 animate-spin" />
                ) : (
                    <Plus className="mr-1.5 size-4" />
                )}
                {uploading ? 'Uploading…' : 'Add photos'}
            </Button>
        </>
    );
}

function SortablePhoto({
    photo,
    onDelete,
    onEdit,
    selecting,
    selected,
    onToggleSelect,
    disabled,
}: {
    photo: Photo;
    onDelete: (id: number) => void;
    onEdit: (photo: Photo) => void;
    selecting: boolean;
    selected: boolean;
    onToggleSelect: (id: number) => void;
    disabled: boolean;
}) {
    // Dragging is disabled while selecting so taps register as selection.
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: photo.id,
        disabled: disabled || selecting,
    });

    const style: React.CSSProperties = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`group relative aspect-square overflow-hidden rounded-lg bg-muted ${
                selected ? 'ring-2 ring-[#8a651c] ring-offset-2' : ''
            }`}
        >
            {selecting ? (
                <button
                    type="button"
                    onClick={() => onToggleSelect(photo.id)}
                    className="size-full"
                    aria-label="Select photo"
                >
                    <img src={photo.url} alt={photo.caption ?? ''} className="size-full object-cover" />
                </button>
            ) : (
                <img src={photo.url} alt={photo.caption ?? ''} className="size-full object-cover" />
            )}

            {photo.caption && !selecting && (
                <div className="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-1.5 text-[11px] text-white">
                    {photo.caption}
                </div>
            )}

            {selecting && (
                <div
                    className={`absolute left-1 top-1 flex size-6 items-center justify-center rounded-full border-2 ${
                        selected ? 'border-[#8a651c] bg-[#8a651c] text-white' : 'border-white bg-black/30 text-transparent'
                    }`}
                >
                    <CheckCircle2 className="size-4" />
                </div>
            )}

            {!disabled && !selecting && (
                <>
                    <button
                        type="button"
                        {...attributes}
                        {...listeners}
                        className="absolute left-1 top-1 cursor-grab rounded bg-black/50 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100 active:cursor-grabbing"
                        aria-label="Drag to reorder"
                    >
                        <GripVertical className="size-3" />
                    </button>
                    <div className="absolute right-1 top-1 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                        <button
                            type="button"
                            onClick={() => onEdit(photo)}
                            className="rounded bg-black/50 p-1 text-white"
                            aria-label="Edit caption"
                        >
                            <Pencil className="size-3" />
                        </button>
                        <button
                            type="button"
                            onClick={() => onDelete(photo.id)}
                            className="rounded bg-black/50 p-1 text-white"
                            aria-label="Delete photo"
                        >
                            <Trash2 className="size-3" />
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}

WebsiteIndex.layout = {
    breadcrumbs: [{ title: 'Website', href: '/website' }],
};

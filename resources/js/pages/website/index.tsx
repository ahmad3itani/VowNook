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
    ExternalLink,
    GripVertical,
    Globe,
    Image,
    Loader2,
    Music,
    Plus,
    Trash2,
    Upload,
    Video,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';

type TimelineItem = { year: string; title: string; body: string };
type Photo = { id: number; url: string; caption: string | null; sort_order: number };

type TemplateId = 'classic' | 'modern' | 'botanical' | 'blush' | 'royal' | 'dolce' | 'destination' | 'vibrant';

type Website = {
    is_published: boolean;
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

type PageProps = { website: Website; public_url: string };

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

export default function WebsiteIndex({ website, public_url }: PageProps) {
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

    // Timeline editor state
    const [newItem, setNewItem] = useState<TimelineItem>({ year: '', title: '', body: '' });

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
                            checked={form.data.is_published}
                            onCheckedChange={(v) => form.setData('is_published', v === true)}
                            disabled={!writable}
                        />
                        <div>
                            <Label htmlFor="is_published" className="flex items-center gap-2">
                                <Globe className="size-4" />
                                Publish website
                            </Label>
                            <p className="text-sm text-muted-foreground">
                                When off, visitors see a simple page with an RSVP link only.
                            </p>
                        </div>
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
    disabled,
}: {
    photo: Photo;
    onDelete: (id: number) => void;
    disabled: boolean;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: photo.id,
    });

    const style: React.CSSProperties = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div ref={setNodeRef} style={style} className="group relative aspect-square overflow-hidden rounded-lg bg-muted">
            <img src={photo.url} alt={photo.caption ?? ''} className="size-full object-cover" />
            {!disabled && (
                <>
                    <button
                        type="button"
                        {...attributes}
                        {...listeners}
                        className="absolute left-1 top-1 rounded bg-black/50 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <GripVertical className="size-3" />
                    </button>
                    <button
                        type="button"
                        onClick={() => onDelete(photo.id)}
                        className="absolute right-1 top-1 rounded bg-black/50 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <Trash2 className="size-3" />
                    </button>
                </>
            )}
        </div>
    );
}

WebsiteIndex.layout = {
    breadcrumbs: [{ title: 'Website', href: '/website' }],
};

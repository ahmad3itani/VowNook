import { Head, router, useForm } from '@inertiajs/react';
import { ImagePlus, Images, Pencil, Trash2, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PlanUsage } from '@/components/plan-usage';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { usePermissions } from '@/hooks/use-permissions';

type Photo = {
    id: number;
    caption: string | null;
    original_name: string;
    size: number;
    url: string;
};

type Stats = { total: number; size: number };

type PageProps = {
    photos: Photo[];
    stats: Stats;
    plan: { used: number; limit: number | null };
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${value.toFixed(1)} ${units[unit]}`;
}

export default function GalleryIndex({ photos, stats, plan }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('gallery');

    const fileInput = useRef<HTMLInputElement>(null);
    const [lightbox, setLightbox] = useState<Photo | null>(null);
    const [editing, setEditing] = useState<Photo | null>(null);

    const upload = useForm<{ photo: File | null; caption: string }>({
        photo: null,
        caption: '',
    });

    const captionForm = useForm<{ caption: string }>({ caption: '' });

    function onFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];

        if (!file) {
            return;
        }

        upload.setData('photo', file);
        upload.post('/gallery', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.success('Photo uploaded.');
                upload.reset();

                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
            onError: () => toast.error(upload.errors.photo ?? 'Upload failed.'),
        });
    }

    function openEdit(photo: Photo) {
        captionForm.clearErrors();
        captionForm.setData('caption', photo.caption ?? '');
        setEditing(photo);
    }

    function submitCaption(e: React.FormEvent) {
        e.preventDefault();

        if (!editing) {
            return;
        }

        captionForm.put(`/gallery/${editing.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Caption saved.');
                setEditing(null);
            },
        });
    }

    function destroy(photo: Photo) {
        if (!confirm('Delete this photo?')) {
            return;
        }

        router.delete(`/gallery/${photo.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Photo deleted.'),
        });
    }

    return (
        <>
            <Head title="Gallery" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Gallery"
                        description="Keep your favourite photos and shots in one private place."
                    />
                    {writable && (
                        <>
                            <input
                                ref={fileInput}
                                type="file"
                                accept="image/*"
                                className="hidden"
                                onChange={onFileChange}
                            />
                            <Button
                                onClick={() => fileInput.current?.click()}
                                disabled={upload.processing}
                                data-test="upload-photo"
                            >
                                {upload.processing ? <Spinner /> : <Upload className="size-4" />}
                                Upload photo
                            </Button>
                        </>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <StatCard label="Photos" value={String(stats.total)} />
                    <StatCard label="Storage used" value={formatBytes(stats.size)} />
                </div>

                {plan.limit !== null && (
                    <PlanUsage used={plan.used} limit={plan.limit} noun="photos" />
                )}

                {photos.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center text-sm">
                            <Images className="size-8 opacity-40" />
                            {writable
                                ? 'No photos yet. Upload your first shot.'
                                : 'No photos have been added yet.'}
                            {writable && (
                                <Button
                                    variant="outline"
                                    className="mt-2"
                                    onClick={() => fileInput.current?.click()}
                                >
                                    <ImagePlus className="size-4" />
                                    Add a photo
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {photos.map((photo) => (
                            <div
                                key={photo.id}
                                className="group bg-muted relative aspect-square overflow-hidden rounded-xl"
                            >
                                <button
                                    type="button"
                                    onClick={() => setLightbox(photo)}
                                    className="size-full"
                                >
                                    <img
                                        src={photo.url}
                                        alt={photo.caption ?? photo.original_name}
                                        className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                </button>

                                {photo.caption && (
                                    <div className="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-2 text-xs text-white">
                                        {photo.caption}
                                    </div>
                                )}

                                {writable && (
                                    <div className="absolute top-2 right-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <Button
                                            variant="secondary"
                                            size="icon"
                                            className="size-7"
                                            onClick={() => openEdit(photo)}
                                            aria-label="Edit caption"
                                        >
                                            <Pencil className="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="secondary"
                                            size="icon"
                                            className="size-7"
                                            onClick={() => destroy(photo)}
                                            aria-label="Delete photo"
                                        >
                                            <Trash2 className="size-3.5" />
                                        </Button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Lightbox */}
            {lightbox && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-6"
                    onClick={() => setLightbox(null)}
                >
                    <button
                        type="button"
                        className="absolute top-4 right-4 text-white/80 hover:text-white"
                        aria-label="Close"
                    >
                        <X className="size-7" />
                    </button>
                    <img
                        src={lightbox.url}
                        alt={lightbox.caption ?? lightbox.original_name}
                        className="max-h-full max-w-full rounded-lg object-contain"
                        onClick={(e) => e.stopPropagation()}
                    />
                </div>
            )}

            <Sheet open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>Edit caption</SheetTitle>
                    </SheetHeader>

                    <form onSubmit={submitCaption} className="flex flex-1 flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label htmlFor="caption">Caption</Label>
                            <Input
                                id="caption"
                                value={captionForm.data.caption}
                                onChange={(e) => captionForm.setData('caption', e.target.value)}
                                autoFocus
                            />
                            <InputError message={captionForm.errors.caption} />
                        </div>

                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={captionForm.processing}>
                                {captionForm.processing && <Spinner />}
                                Save caption
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

function StatCard({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-muted-foreground text-sm">{label}</div>
                <div className="mt-1 text-2xl font-semibold tabular-nums">{value}</div>
            </CardContent>
        </Card>
    );
}

GalleryIndex.layout = {
    breadcrumbs: [{ title: 'Gallery', href: '/gallery' }],
};

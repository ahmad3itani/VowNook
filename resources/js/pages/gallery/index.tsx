import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    PointerSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { arrayMove, rectSortingStrategy, SortableContext, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    CheckCircle2,
    GripVertical,
    ImagePlus,
    Images,
    Loader2,
    Pencil,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PlanUsage } from '@/components/plan-usage';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
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

export default function GalleryIndex({ photos: photosProp, stats, plan }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('gallery');

    const fileInput = useRef<HTMLInputElement>(null);
    const [photos, setPhotos] = useState<Photo[]>(photosProp);
    const [lightbox, setLightbox] = useState<Photo | null>(null);
    const [editing, setEditing] = useState<Photo | null>(null);
    const [uploading, setUploading] = useState(false);
    const [selectMode, setSelectMode] = useState(false);
    const [selected, setSelected] = useState<Set<number>>(new Set());

    // Keep local order/state in sync whenever the server sends fresh props
    // (after an upload, delete or caption edit). Drag reordering updates the
    // list optimistically and persists with preserveState so it isn't clobbered.
    useEffect(() => setPhotos(photosProp), [photosProp]);

    const captionForm = useForm<{ caption: string }>({ caption: '' });

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(TouchSensor, { activationConstraint: { delay: 180, tolerance: 6 } }),
    );

    function onFilesChosen(e: React.ChangeEvent<HTMLInputElement>) {
        const files = Array.from(e.target.files ?? []);

        if (files.length === 0) {
            return;
        }

        const fd = new FormData();
        files.forEach((f) => fd.append('photos[]', f));

        setUploading(true);
        router.post('/gallery', fd as unknown as Record<string, string>, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => toast.success(files.length > 1 ? `${files.length} photos uploaded.` : 'Photo uploaded.'),
            onError: (errors) => toast.error(errors.photos ?? errors['photos.0'] ?? 'Upload failed.'),
            onFinish: () => {
                setUploading(false);
                if (fileInput.current) {
                    fileInput.current.value = '';
                }
            },
        });
    }

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const oldIdx = photos.findIndex((p) => p.id === active.id);
        const newIdx = photos.findIndex((p) => p.id === over.id);
        const reordered = arrayMove(photos, oldIdx, newIdx);
        setPhotos(reordered);

        router.post(
            '/gallery/reorder',
            { items: reordered.map((p, i) => ({ id: p.id, sort_order: i })) } as unknown as Record<string, string>,
            { preserveScroll: true, preserveState: true },
        );
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

    function toggleSelect(id: number) {
        setSelected((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function exitSelectMode() {
        setSelectMode(false);
        setSelected(new Set());
    }

    function deleteSelected() {
        if (selected.size === 0) {
            return;
        }

        if (!confirm(`Delete ${selected.size} selected photo${selected.size > 1 ? 's' : ''}?`)) {
            return;
        }

        router.post(
            '/gallery/bulk-delete',
            { ids: Array.from(selected) } as unknown as Record<string, string>,
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Photos deleted.');
                    exitSelectMode();
                },
            },
        );
    }

    return (
        <>
            <Head title="Gallery" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Gallery"
                        description="Keep your favourite photos in one place — upload in bulk, drag to arrange, caption and curate."
                    />
                    {writable && (
                        <div className="flex flex-wrap items-center gap-2">
                            <input
                                ref={fileInput}
                                type="file"
                                accept="image/*"
                                multiple
                                className="hidden"
                                onChange={onFilesChosen}
                            />
                            {photos.length > 0 && (
                                <Button
                                    variant={selectMode ? 'default' : 'outline'}
                                    onClick={() => (selectMode ? exitSelectMode() : setSelectMode(true))}
                                >
                                    <CheckCircle2 className="size-4" />
                                    {selectMode ? 'Done' : 'Select'}
                                </Button>
                            )}
                            <Button
                                onClick={() => fileInput.current?.click()}
                                disabled={uploading}
                                data-test="upload-photo"
                            >
                                {uploading ? <Spinner /> : <Upload className="size-4" />}
                                Upload photos
                            </Button>
                        </div>
                    )}
                </div>

                {/* Selection action bar */}
                {selectMode && (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-muted/40 px-4 py-3">
                        <span className="text-sm font-medium">
                            {selected.size} selected
                        </span>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    setSelected(
                                        selected.size === photos.length
                                            ? new Set()
                                            : new Set(photos.map((p) => p.id)),
                                    )
                                }
                            >
                                {selected.size === photos.length ? 'Clear all' : 'Select all'}
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                disabled={selected.size === 0}
                                onClick={deleteSelected}
                            >
                                <Trash2 className="size-4" />
                                Delete selected
                            </Button>
                        </div>
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                    <StatCard label="Photos" value={String(stats.total)} />
                    <StatCard label="Storage used" value={formatBytes(stats.size)} />
                </div>

                {plan.limit !== null && <PlanUsage used={plan.used} limit={plan.limit} noun="photos" />}

                {photos.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-sm text-muted-foreground">
                            <Images className="size-8 opacity-40" />
                            {writable ? 'No photos yet. Upload your first shots.' : 'No photos have been added yet.'}
                            {writable && (
                                <Button variant="outline" className="mt-2" onClick={() => fileInput.current?.click()}>
                                    <ImagePlus className="size-4" />
                                    Add photos
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        {writable && !selectMode && photos.length > 1 && (
                            <p className="-mt-2 text-xs text-muted-foreground">Drag any photo to rearrange your gallery.</p>
                        )}
                        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                            <SortableContext items={photos.map((p) => p.id)} strategy={rectSortingStrategy}>
                                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                    {photos.map((photo) => (
                                        <SortablePhoto
                                            key={photo.id}
                                            photo={photo}
                                            writable={writable}
                                            selectMode={selectMode}
                                            selected={selected.has(photo.id)}
                                            onOpen={() => setLightbox(photo)}
                                            onToggleSelect={() => toggleSelect(photo.id)}
                                            onEdit={() => openEdit(photo)}
                                            onDelete={() => destroy(photo)}
                                        />
                                    ))}
                                </div>
                            </SortableContext>
                        </DndContext>
                    </>
                )}
            </div>

            {/* Lightbox */}
            {lightbox && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-6"
                    onClick={() => setLightbox(null)}
                >
                    <button type="button" className="absolute top-4 right-4 text-white/80 hover:text-white" aria-label="Close">
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

function SortablePhoto({
    photo,
    writable,
    selectMode,
    selected,
    onOpen,
    onToggleSelect,
    onEdit,
    onDelete,
}: {
    photo: Photo;
    writable: boolean;
    selectMode: boolean;
    selected: boolean;
    onOpen: () => void;
    onToggleSelect: () => void;
    onEdit: () => void;
    onDelete: () => void;
}) {
    // Dragging is disabled while selecting so taps register as selection.
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: photo.id,
        disabled: !writable || selectMode,
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
            className={`group relative aspect-square overflow-hidden rounded-xl bg-muted ${
                selected ? 'ring-2 ring-[#8a651c] ring-offset-2' : ''
            }`}
        >
            <button
                type="button"
                onClick={() => (selectMode ? onToggleSelect() : onOpen())}
                className="size-full"
                aria-label={selectMode ? 'Select photo' : 'View photo'}
            >
                <img
                    src={photo.url}
                    alt={photo.caption ?? photo.original_name}
                    className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                />
            </button>

            {photo.caption && !selectMode && (
                <div className="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-2 text-xs text-white">
                    {photo.caption}
                </div>
            )}

            {/* Selection checkbox */}
            {selectMode && (
                <div
                    className={`absolute left-2 top-2 flex size-6 items-center justify-center rounded-full border-2 ${
                        selected ? 'border-[#8a651c] bg-[#8a651c] text-white' : 'border-white bg-black/30 text-transparent'
                    }`}
                >
                    <CheckCircle2 className="size-4" />
                </div>
            )}

            {/* Drag handle */}
            {writable && !selectMode && (
                <button
                    type="button"
                    {...attributes}
                    {...listeners}
                    className="absolute left-2 top-2 cursor-grab rounded bg-black/50 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100 active:cursor-grabbing"
                    aria-label="Drag to reorder"
                >
                    <GripVertical className="size-3.5" />
                </button>
            )}

            {/* Edit + delete */}
            {writable && !selectMode && (
                <div className="absolute right-2 top-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                    <Button variant="secondary" size="icon" className="size-7" onClick={onEdit} aria-label="Edit caption">
                        <Pencil className="size-3.5" />
                    </Button>
                    <Button variant="secondary" size="icon" className="size-7" onClick={onDelete} aria-label="Delete photo">
                        <Trash2 className="size-3.5" />
                    </Button>
                </div>
            )}
        </div>
    );
}

function StatCard({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardContent className="px-5">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className="mt-1 text-2xl font-semibold tabular-nums">{value}</div>
            </CardContent>
        </Card>
    );
}

GalleryIndex.layout = {
    breadcrumbs: [{ title: 'Gallery', href: '/gallery' }],
};

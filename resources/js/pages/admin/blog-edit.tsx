import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, ImagePlus, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Option = { value: string; label: string };
type Post = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    category: string;
    author_name: string;
    meta_title: string | null;
    meta_description: string | null;
    status: string;
    published_at: string | null;
    cover_url: string | null;
    cover_alt: string | null;
    public_url: string | null;
};

type PageProps = { post: Post | null; options: { categories: Option[] } };

type FormData = {
    title: string;
    excerpt: string;
    cover_alt: string;
    body: string;
    category: string;
    author_name: string;
    meta_title: string;
    meta_description: string;
    status: string;
    published_at: string;
};

export default function AdminBlogEdit({ post, options }: PageProps) {
    const isEdit = post !== null;
    const coverInput = useRef<HTMLInputElement>(null);
    const imageInput = useRef<HTMLInputElement>(null);
    const bodyRef = useRef<HTMLTextAreaElement>(null);

    const form = useForm<FormData>({
        title: post?.title ?? '',
        excerpt: post?.excerpt ?? '',
        cover_alt: post?.cover_alt ?? '',
        body: post?.body ?? '',
        category: post?.category ?? options.categories[0]?.value ?? 'planning_tips',
        author_name: post?.author_name ?? 'VowNook',
        meta_title: post?.meta_title ?? '',
        meta_description: post?.meta_description ?? '',
        status: post?.status ?? 'draft',
        published_at: post?.published_at ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit) {
            form.put(`/admin/blog/${post!.slug}`, { onSuccess: () => toast.success('Post saved.') });
        } else {
            form.post('/admin/blog', { onSuccess: () => toast.success('Post created.') });
        }
    }

    function uploadCover(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file || !post) return;
        router.post(`/admin/blog/${post.slug}/cover`, { cover: file }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => toast.success('Cover updated.'),
            onError: () => toast.error('Upload failed.'),
        });
    }

    function destroy() {
        if (!post || !confirm('Delete this post permanently?')) return;
        router.delete(`/admin/blog/${post.slug}`, { onSuccess: () => toast.success('Post deleted.') });
    }

    async function insertBodyImage(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file) return;

        const alt = window.prompt('Describe this image for SEO & accessibility (alt text):')?.trim();
        if (!alt) {
            toast.error('Alt text is required so the image is SEO-friendly.');
            return;
        }

        const fd = new FormData();
        fd.append('image', file);
        const xsrf = decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');

        try {
            const res = await fetch('/admin/blog/image', {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': xsrf, Accept: 'application/json' },
                body: fd,
            });
            if (!res.ok) throw new Error();
            const { url } = await res.json();

            // Insert markdown at the cursor.
            const ta = bodyRef.current;
            const md = `\n\n![${alt}](${url})\n\n`;
            const start = ta?.selectionStart ?? form.data.body.length;
            const next = form.data.body.slice(0, start) + md + form.data.body.slice(start);
            form.setData('body', next);
            toast.success('Image inserted.');
        } catch {
            toast.error('Image upload failed.');
        }
    }

    return (
        <>
            <Head title={isEdit ? post!.title : 'New post'} />

            <form onSubmit={submit} className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link href="/admin/blog" className="mb-2 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                            <ArrowLeft className="size-4" /> All posts
                        </Link>
                        <Heading title={isEdit ? 'Edit post' : 'New post'} description="Markdown supported in the body." />
                    </div>
                    <div className="flex items-center gap-2">
                        {post?.public_url && (
                            <a href={post.public_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-2 text-sm font-medium hover:bg-muted">
                                View live <ExternalLink className="size-4" />
                            </a>
                        )}
                        {isEdit && (
                            <Button type="button" variant="ghost" size="icon" onClick={destroy} aria-label="Delete">
                                <Trash2 className="size-4" />
                            </Button>
                        )}
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />} Save
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main */}
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        <Card>
                            <CardContent className="flex flex-col gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="title">Title</Label>
                                    <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                                    <InputError message={form.errors.title} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="excerpt">Excerpt</Label>
                                    <Textarea id="excerpt" rows={2} value={form.data.excerpt} onChange={(e) => form.setData('excerpt', e.target.value)} placeholder="One or two sentences shown on cards and search results." />
                                    <InputError message={form.errors.excerpt} />
                                </div>
                                <div className="grid gap-2">
                                    <div className="flex items-center justify-between">
                                        <Label htmlFor="body">Body (markdown)</Label>
                                        <input ref={imageInput} type="file" accept="image/*" className="hidden" onChange={insertBodyImage} />
                                        <Button type="button" variant="outline" size="sm" onClick={() => imageInput.current?.click()}>
                                            <ImagePlus className="size-4" /> Insert image
                                        </Button>
                                    </div>
                                    <Textarea ref={bodyRef} id="body" rows={20} className="font-mono text-sm" value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} />
                                    <InputError message={form.errors.body} />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="flex flex-col gap-4">
                        <Card>
                            <CardContent className="flex flex-col gap-4">
                                <div className="grid gap-2">
                                    <Label>Status</Label>
                                    <Select value={form.data.status} onValueChange={(v) => form.setData('status', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">Draft</SelectItem>
                                            <SelectItem value="published">Published</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="published_at">Publish date</Label>
                                    <Input id="published_at" type="date" value={form.data.published_at} onChange={(e) => form.setData('published_at', e.target.value)} />
                                    <p className="text-xs text-muted-foreground">Leave blank to publish now. A future date schedules it.</p>
                                </div>
                                <div className="grid gap-2">
                                    <Label>Category</Label>
                                    <Select value={form.data.category} onValueChange={(v) => form.setData('category', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {options.categories.map((c) => <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="author">Author</Label>
                                    <Input id="author" value={form.data.author_name} onChange={(e) => form.setData('author_name', e.target.value)} />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Cover */}
                        <Card>
                            <CardContent className="flex flex-col gap-3">
                                <Label>Cover image</Label>
                                {post?.cover_url && <img src={post.cover_url} alt={form.data.cover_alt || ''} className="aspect-[16/9] w-full rounded-md object-cover" />}
                                {isEdit ? (
                                    <>
                                        <input ref={coverInput} type="file" accept="image/*" className="hidden" onChange={uploadCover} />
                                        <Button type="button" variant="outline" size="sm" onClick={() => coverInput.current?.click()}>
                                            <ImagePlus className="size-4" /> {post?.cover_url ? 'Replace cover' : 'Upload cover'}
                                        </Button>
                                        <div className="grid gap-2">
                                            <Label htmlFor="cover_alt" className="text-xs">Cover alt text (SEO)</Label>
                                            <Input id="cover_alt" value={form.data.cover_alt} onChange={(e) => form.setData('cover_alt', e.target.value)} placeholder="Describe the cover image" />
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-xs text-muted-foreground">Save the post first, then add a cover image.</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* SEO */}
                        <Card>
                            <CardContent className="flex flex-col gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="meta_title">Meta title (optional)</Label>
                                    <Input id="meta_title" value={form.data.meta_title} onChange={(e) => form.setData('meta_title', e.target.value)} placeholder="Defaults to the title" />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="meta_description">Meta description (optional)</Label>
                                    <Textarea id="meta_description" rows={3} value={form.data.meta_description} onChange={(e) => form.setData('meta_description', e.target.value)} placeholder="Defaults to the excerpt" />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </>
    );
}

AdminBlogEdit.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Blog', href: '/admin/blog' },
    ],
};

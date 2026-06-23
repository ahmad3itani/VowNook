import { Head, Link, router } from '@inertiajs/react';
import { Plus, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

type Post = {
    id: number;
    title: string;
    slug: string;
    category: string;
    status: string;
    published_label: string | null;
};

type PageProps = {
    posts: Post[];
    autopilot: { ai_ready: boolean; enabled: boolean };
};

export default function AdminBlogIndex({ posts, autopilot }: PageProps) {
    const [generating, setGenerating] = useState(false);

    function generate() {
        router.post(
            '/admin/blog/autopilot',
            {},
            {
                preserveScroll: true,
                onStart: () => setGenerating(true),
                onFinish: () => setGenerating(false),
                onSuccess: () =>
                    toast.success(
                        'Writing a new article — it’ll appear here in a moment. Refresh to see it.',
                    ),
            },
        );
    }

    return (
        <>
            <Head title="Blog" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Blog" description="Write and publish articles for the public blog." />
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={generate}
                            disabled={!autopilot.ai_ready || generating}
                            title={autopilot.ai_ready ? 'Write & publish one article with AI' : 'Set an AI key to enable'}
                        >
                            {generating ? <Spinner /> : <Sparkles className="size-4" />}
                            Generate with AI
                        </Button>
                        <Button asChild>
                            <Link href="/admin/blog/create"><Plus className="size-4" /> New post</Link>
                        </Button>
                    </div>
                </div>

                {autopilot.enabled && (
                    <p className="-mt-2 text-xs text-muted-foreground">
                        Autopilot is <span className="font-medium text-[#775a19]">on</span> — one SEO article publishes automatically each week.
                    </p>
                )}

                <Card>
                    <CardContent className="p-0">
                        {posts.length === 0 ? (
                            <p className="py-12 text-center text-sm text-muted-foreground">No posts yet. Write your first article.</p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead className="border-b text-left text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Title</th>
                                        <th className="px-4 py-3 font-medium">Category</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                        <th className="px-4 py-3 font-medium">Published</th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {posts.map((p) => (
                                        <tr key={p.id} className="border-b last:border-0">
                                            <td className="px-4 py-3">
                                                <Link href={`/admin/blog/${p.slug}/edit`} className="font-medium hover:underline">{p.title}</Link>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{p.category}</td>
                                            <td className="px-4 py-3">
                                                <Badge variant={p.status === 'published' ? 'default' : 'outline'} className="capitalize">{p.status}</Badge>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">{p.published_label ?? '—'}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Link href={`/admin/blog/${p.slug}/edit`} className="text-sm text-[#775a19] hover:underline">Edit</Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminBlogIndex.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Blog', href: '/admin/blog' },
    ],
};

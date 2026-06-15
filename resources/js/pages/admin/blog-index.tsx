import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Post = {
    id: number;
    title: string;
    slug: string;
    category: string;
    status: string;
    published_label: string | null;
};

type PageProps = { posts: Post[] };

export default function AdminBlogIndex({ posts }: PageProps) {
    return (
        <>
            <Head title="Blog" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title="Blog" description="Write and publish articles for the public blog." />
                    <Button asChild>
                        <Link href="/admin/blog/create"><Plus className="size-4" /> New post</Link>
                    </Button>
                </div>

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

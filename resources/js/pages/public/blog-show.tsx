import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock } from 'lucide-react';
import { SiteHeader } from '@/components/public/site-header';

type Card = {
    title: string;
    slug: string;
    excerpt: string;
    cover_url: string | null;
    category: { label: string; slug: string };
    published_label: string | null;
    reading_minutes: number;
};

type Post = {
    title: string;
    slug: string;
    body_html: string;
    excerpt: string | null;
    cover_url: string | null;
    cover_alt: string | null;
    category: { label: string; slug: string };
    author_name: string;
    published_at: string | null;
    published_label: string | null;
    reading_minutes: number;
};

type PageProps = { post: Post; related: Card[] };

const PROSE =
    'max-w-none ' +
    '[&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:font-serif [&_h2]:text-2xl [&_h2]:text-foreground ' +
    '[&_h3]:mt-7 [&_h3]:mb-2 [&_h3]:font-serif [&_h3]:text-xl [&_h3]:text-foreground ' +
    '[&_p]:mb-5 [&_p]:leading-relaxed [&_p]:text-[15px] [&_p]:text-foreground/90 ' +
    '[&_ul]:mb-5 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-5 [&_ol]:list-decimal [&_ol]:pl-6 ' +
    '[&_li]:mb-1.5 [&_li]:leading-relaxed ' +
    '[&_a]:font-medium [&_a]:text-[#8a651c] [&_a]:underline [&_a]:underline-offset-2 ' +
    '[&_strong]:font-semibold [&_strong]:text-foreground ' +
    '[&_blockquote]:my-6 [&_blockquote]:border-l-2 [&_blockquote]:border-[#8a651c] [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-muted-foreground ' +
    '[&_img]:my-7 [&_img]:h-auto [&_img]:w-full [&_img]:rounded-xl [&_figure]:my-7 [&_figcaption]:mt-2 [&_figcaption]:text-center [&_figcaption]:text-sm [&_figcaption]:text-muted-foreground';

export default function BlogShow({ post, related }: PageProps) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title={post.title} />
            <SiteHeader />

            <article className="mx-auto max-w-3xl px-4 py-10 md:px-6">
                <Link href="/blog" className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
                    <ArrowLeft className="size-4" /> All articles
                </Link>

                <header className="mt-6">
                    <Link href={`/blog/category/${post.category.slug}`} className="text-xs font-semibold tracking-wide text-[#8a651c] uppercase hover:underline">
                        {post.category.label}
                    </Link>
                    <h1 className="mt-3 font-serif text-3xl leading-tight md:text-4xl">{post.title}</h1>
                    <div className="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                        <span>By {post.author_name}</span>
                        <span>·</span>
                        {post.published_label && <span>{post.published_label}</span>}
                        <span>·</span>
                        <span className="flex items-center gap-1"><Clock className="size-3.5" /> {post.reading_minutes} min read</span>
                    </div>
                </header>

                {post.cover_url && (
                    <img src={post.cover_url} alt={post.cover_alt || post.title} loading="eager" className="mt-8 aspect-[16/9] w-full rounded-2xl object-cover" />
                )}

                {/* Trusted, markdown-rendered, server-sanitized HTML. */}
                <div className={`mt-10 ${PROSE}`} dangerouslySetInnerHTML={{ __html: post.body_html }} />
            </article>

            {/* CTA */}
            <section className="border-t border-border bg-card">
                <div className="mx-auto max-w-3xl px-4 py-12 text-center md:px-6">
                    <h2 className="font-serif text-2xl">Plan it all in one calm place</h2>
                    <p className="mt-2 text-muted-foreground">Free guest list, budget, timeline and seating — plus trusted Ontario vendors.</p>
                    <div className="mt-5 flex justify-center gap-3">
                        <Link href="/register" className="rounded-full bg-foreground px-6 py-2.5 text-sm font-semibold text-background transition-colors hover:bg-[#8a651c] hover:text-white">
                            Start free
                        </Link>
                        <Link href="/marketplace" className="rounded-full border border-border px-6 py-2.5 text-sm font-semibold transition-colors hover:border-[#8a651c]">
                            Browse vendors
                        </Link>
                    </div>
                </div>
            </section>

            {/* Related */}
            {related.length > 0 && (
                <section className="mx-auto max-w-7xl px-4 py-14 md:px-6">
                    <h2 className="mb-6 font-serif text-2xl">Keep reading</h2>
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {related.map((r) => (
                            <Link key={r.slug} href={`/blog/${r.slug}`} className="group flex flex-col gap-1.5 rounded-xl border border-border bg-card p-4 transition-shadow hover:shadow-atelier">
                                <span className="text-xs font-medium tracking-wide text-[#8a651c] uppercase">{r.category.label}</span>
                                <span className="font-serif text-base leading-snug group-hover:text-[#8a651c]">{r.title}</span>
                                <span className="text-xs text-muted-foreground">{r.published_label} · {r.reading_minutes} min</span>
                            </Link>
                        ))}
                    </div>
                </section>
            )}
        </div>
    );
}

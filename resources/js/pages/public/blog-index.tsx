import { Head, Link } from '@inertiajs/react';
import { Clock } from 'lucide-react';
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

type PageProps = {
    posts: Card[];
    categories: { label: string; slug: string }[];
    active_category: string | null;
};

function PostCard({ post }: { post: Card }) {
    return (
        <Link
            href={`/blog/${post.slug}`}
            className="group flex flex-col overflow-hidden rounded-2xl border border-border bg-card transition-shadow hover:shadow-atelier-lg"
        >
            <div className="aspect-[16/10] overflow-hidden bg-muted">
                {post.cover_url ? (
                    <img
                        src={post.cover_url}
                        alt={post.title}
                        className="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center bg-gradient-to-br from-[#fed488]/30 to-[#8a651c]/10 font-serif text-3xl text-[#8a651c]/40">
                        ✦
                    </div>
                )}
            </div>
            <div className="flex flex-1 flex-col gap-2 p-5">
                <span className="text-xs font-medium tracking-wide text-[#8a651c] uppercase">{post.category.label}</span>
                <h2 className="font-serif text-lg leading-snug group-hover:text-[#8a651c]">{post.title}</h2>
                <p className="line-clamp-2 text-sm text-muted-foreground">{post.excerpt}</p>
                <div className="mt-auto flex items-center gap-2 pt-2 text-xs text-muted-foreground">
                    <span>{post.published_label}</span>
                    <span>·</span>
                    <span className="flex items-center gap-1"><Clock className="size-3" /> {post.reading_minutes} min read</span>
                </div>
            </div>
        </Link>
    );
}

export default function BlogIndex({ posts, categories, active_category }: PageProps) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title="Wedding Planning Blog" />
            <SiteHeader />

            <header className="mx-auto max-w-7xl px-4 pt-14 pb-8 text-center md:px-6">
                <p className="text-xs font-semibold tracking-[0.2em] text-[#8a651c] uppercase">The Journal</p>
                <h1 className="mt-3 font-serif text-4xl md:text-5xl">Wedding planning, the calm way</h1>
                <p className="mx-auto mt-4 max-w-2xl text-muted-foreground">
                    Honest, practical advice for planning a wedding in Ontario — real budgets, timelines, venue
                    questions, and how to choose vendors you can trust.
                </p>
            </header>

            {/* Category filter */}
            <nav className="mx-auto mb-10 flex max-w-7xl flex-wrap items-center justify-center gap-2 px-4 md:px-6">
                <Link
                    href="/blog"
                    className={`rounded-full border px-4 py-1.5 text-sm transition-colors ${
                        !active_category ? 'border-[#8a651c] bg-[#8a651c] text-white' : 'border-border text-muted-foreground hover:border-[#8a651c]/50'
                    }`}
                >
                    All
                </Link>
                {categories.map((c) => (
                    <Link
                        key={c.slug}
                        href={`/blog/category/${c.slug}`}
                        className={`rounded-full border px-4 py-1.5 text-sm transition-colors ${
                            active_category === c.slug ? 'border-[#8a651c] bg-[#8a651c] text-white' : 'border-border text-muted-foreground hover:border-[#8a651c]/50'
                        }`}
                    >
                        {c.label}
                    </Link>
                ))}
            </nav>

            <main className="mx-auto max-w-7xl px-4 pb-20 md:px-6">
                {posts.length === 0 ? (
                    <p className="py-16 text-center text-muted-foreground">No articles here yet — check back soon.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.map((post) => (
                            <PostCard key={post.slug} post={post} />
                        ))}
                    </div>
                )}
            </main>

            {/* CTA */}
            <section className="border-t border-border bg-card">
                <div className="mx-auto max-w-3xl px-4 py-14 text-center md:px-6">
                    <h2 className="font-serif text-3xl">Ready to start planning?</h2>
                    <p className="mt-3 text-muted-foreground">
                        Build your guest list, budget and timeline for free — then find trusted Ontario vendors.
                    </p>
                    <div className="mt-6 flex justify-center gap-3">
                        <Link href="/register" className="rounded-full bg-foreground px-6 py-2.5 text-sm font-semibold text-background transition-colors hover:bg-[#8a651c] hover:text-white">
                            Start free
                        </Link>
                        <Link href="/marketplace" className="rounded-full border border-border px-6 py-2.5 text-sm font-semibold transition-colors hover:border-[#8a651c]">
                            Browse vendors
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    );
}

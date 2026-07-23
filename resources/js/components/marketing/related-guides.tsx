import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';

/**
 * "Related reading" block for the programmatic local pages. Links the
 * transactional ranking pages to the informational blog guides so Google reads
 * them as one topic cluster, and so a couple researching costs has an obvious
 * next step. Renders nothing when there are no guides.
 */
export type Guide = { title: string; url: string; excerpt: string };

export function RelatedGuides({ guides }: { guides: Guide[] }) {
    if (!guides.length) {
        return null;
    }

    return (
        <section className="border-t border-[#0f1c17]/10 px-5 py-14 md:px-12 md:py-16">
            <div className="mx-auto max-w-[1480px]">
                <p className="eyebrow mb-6 text-[#1b4638]">Related reading</p>
                <div className="grid gap-px overflow-hidden border border-[#0f1c17]/10 bg-[#0f1c17]/10 sm:grid-cols-2 lg:grid-cols-3">
                    {guides.map((g) => (
                        <Link
                            key={g.url}
                            href={g.url}
                            className="group flex flex-col bg-[#f1f0ea] p-6 transition-colors hover:bg-white"
                        >
                            <h3 className="font-['Newsreader'] text-xl leading-snug font-medium text-[#0f1c17]">
                                {g.title}
                            </h3>
                            <p className="mt-2 line-clamp-3 text-sm leading-relaxed text-[#4b5850]">
                                {g.excerpt}
                            </p>
                            <span className="mt-4 inline-flex items-center gap-1.5 text-[11px] font-semibold tracking-[0.18em] text-[#1b4638] uppercase">
                                Read the guide
                                <ArrowUpRight className="size-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                            </span>
                        </Link>
                    ))}
                </div>
            </div>
        </section>
    );
}

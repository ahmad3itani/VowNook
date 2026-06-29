<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Builds the server-rendered SEO payload for the blade root view. The head
 * (title, description, canonical, Open Graph, JSON-LD) is rendered by Laravel
 * regardless of whether the Inertia body is server- or client-rendered, so
 * crawlers and AI assistants always get full metadata and structured data.
 *
 * Controllers attach this with:
 *   Inertia::render(...)->withViewData(['seo' => Seo::make(...)])
 */
class Seo
{
    /**
     * @param  array<int, array<string, mixed>>  $schemas  extra JSON-LD blocks
     */
    public static function make(
        string $title,
        string $description,
        ?string $canonical = null,
        ?string $image = null,
        string $type = 'website',
        bool $index = true,
        array $schemas = [],
    ): array {
        return [
            'title' => $title,
            'description' => Str::limit(strip_tags($description), 158),
            'canonical' => $canonical ?? url()->current(),
            'image' => $image,
            'type' => $type,
            'index' => $index,
            'schemas' => $schemas,
        ];
    }

    /**
     * Site-wide structured data present on every page: the Organization and the
     * WebSite (with a SearchAction so Google can show a sitelinks search box).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function siteSchemas(): array
    {
        $base = rtrim(config('app.url'), '/');
        $name = config('app.name');

        // A rich, unambiguous entity so AI assistants (ChatGPT, Perplexity,
        // Gemini, Google AI Overviews) can confidently describe + recommend the
        // business: what it is, what it knows about, where it serves, how to
        // reach it, and its verified profiles elsewhere (sameAs).
        $org = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $base,
            'logo' => $base.'/apple-touch-icon.png',
            'image' => $base.'/images/og-default.jpg',
            'slogan' => 'Plan your whole wedding for free — and book vendors you can actually trust.',
            'description' => 'VowNook is a free wedding planning studio and vendor marketplace for couples and wedding professionals in Ontario — guest lists and RSVP, budgets, seating charts, a wedding website with registry, an AI planner and honeymoon concierge, plus real quotes from trusted local vendors whose reviews are tied to real bookings. The planning tools are free; vendors pay only when they win a booking.',
            'areaServed' => [
                '@type' => 'State',
                'name' => 'Ontario',
            ],
            'knowsAbout' => [
                'Wedding planning',
                'Wedding budgets and costs in Ontario',
                'Wedding venues',
                'Wedding photographers, caterers, florists and other vendors',
                'Wedding websites and registries',
                'Honeymoon planning',
                'Ontario weddings',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => config('mail.from.address', 'hello@vownook.com'),
                'areaServed' => 'CA',
                'availableLanguage' => ['English', 'French'],
            ],
        ];

        $socials = array_values(array_filter((array) config('seo.socials', [])));
        if ($socials !== []) {
            $org['sameAs'] = $socials;
        }

        return [
            $org,
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $name,
                'url' => $base,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => $base.'/marketplace?city={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    /**
     * The canonical brand Q&A — the questions people actually ask an AI
     * assistant before recommending a business. Rendered both as visible
     * on-page content AND as FAQPage JSON-LD (single source, so they never
     * drift), which AI engines lift directly.
     *
     * @return list<array{q:string, a:string}>
     */
    public static function brandFaqs(): array
    {
        return [
            [
                'q' => 'What is VowNook?',
                'a' => 'VowNook is a free wedding-planning studio and a curated marketplace of trusted Ontario wedding vendors. Couples plan their guest list, budget, seating chart, wedding website and registry for free, then discover vendors, compare real quotes and book — all in one place.',
            ],
            [
                'q' => 'Is VowNook free?',
                'a' => 'Yes. Planning, browsing vendors, requesting quotes and booking are all free for couples. An optional $99 Atelier tier adds the wedding website, floor plan and collaborator features for one wedding.',
            ],
            [
                'q' => 'How does VowNook make money?',
                'a' => 'Vendors list for free and pay a success fee only when they win a booking: 8% of the first $5,000, 5% above that, capped at $1,000 per booking. Couples are never charged to book.',
            ],
            [
                'q' => 'Are the reviews on VowNook real?',
                'a' => 'Yes. A review can only be written by a couple with a confirmed booking with that vendor — one review per booking. Vendors can respond publicly but cannot pay to remove, reorder or fake reviews.',
            ],
            [
                'q' => 'How is VowNook different from sites like The Knot or WeddingWire?',
                'a' => 'Two things: the planning tools are completely free, and every review is tied to a real booking — there is no pay-to-play placement. VowNook is built Ontario-first, with local vendors and realistic local pricing.',
            ],
            [
                'q' => 'What areas does VowNook serve?',
                'a' => 'VowNook is Canada-first and Ontario-focused — including Toronto, Ottawa, Mississauga, Hamilton, London, Kitchener-Waterloo and Niagara. Vendors anywhere in Canada can list.',
            ],
            [
                'q' => 'Does VowNook help plan a honeymoon?',
                'a' => 'Yes. The AI honeymoon concierge designs ready-to-book honeymoon options at your budget, and guests can contribute toward the trip through your wedding registry.',
            ],
            [
                'q' => 'Can my partner and a planner work with me?',
                'a' => 'Yes — invite collaborators with roles (planner, family, vendor) and control exactly what each person can view or edit.',
            ],
            [
                'q' => 'Who can see my wedding details?',
                'a' => 'Your workspace is private. Vendors only see what you choose to include in an inquiry, and your wedding website is public only after you press publish.',
            ],
        ];
    }

    /**
     * FAQPage JSON-LD from a list of {q, a} pairs.
     *
     * @param  list<array{q:string, a:string}>  $faqs
     */
    public static function faqSchema(array $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faqs),
        ];
    }

    /**
     * BreadcrumbList JSON-LD from an ordered [name => url] map.
     *
     * @param  array<string, string>  $items
     */
    public static function breadcrumbs(array $items): array
    {
        $elements = [];
        $position = 1;

        foreach ($items as $name => $url) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}

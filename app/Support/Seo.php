<?php

namespace App\Support;

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
            'title'       => $title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($description), 158),
            'canonical'   => $canonical ?? url()->current(),
            'image'       => $image,
            'type'        => $type,
            'index'       => $index,
            'schemas'     => $schemas,
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

        return [
            [
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => $name,
                'url'      => $base,
                'logo'     => $base.'/apple-touch-icon.png',
                'areaServed' => [
                    '@type' => 'State',
                    'name'  => 'Ontario',
                ],
            ],
            [
                '@context'      => 'https://schema.org',
                '@type'         => 'WebSite',
                'name'          => $name,
                'url'           => $base,
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => $base.'/marketplace?city={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
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
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $name,
                'item'     => $url,
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }
}

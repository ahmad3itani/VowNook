{{-- Couples' wedding workspace is always light (dark is "bad luck" for weddings);
     vendors and planners may use dark mode. --}}
@php($__user = $page['props']['auth']['user'] ?? null)
@php($__forceLight = $__user && (($__user['account_type'] ?? 'couple') === 'couple'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => !$__forceLight && ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Apply the saved/ system theme immediately to avoid a flash, unless the
             account is locked to light. --}}
        <script>
            (function() {
                window.__forceLight = {{ $__forceLight ? 'true' : 'false' }};
                const appearance = '{{ $appearance ?? "system" }}';

                if (!window.__forceLight) {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (appearance === 'dark' || (appearance === 'system' && prefersDark)) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #fff8f3;
            }

            html.dark {
                background-color: #18130d;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <meta name="theme-color" content="#faf6ef">

        {{-- Brand fonts (Fraunces display + Playfair Display + DM Sans) --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:wght@400;500;700&family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&display=swap" rel="stylesheet">

        @fonts

        {{-- Server-rendered SEO: reaches crawlers/AI even while the body hydrates client-side. --}}
        @php($seoTitle = ($seo['title'] ?? null) ? $seo['title'].' — '.config('app.name') : config('app.name').' — Wedding planning studio & vendor marketplace')
        @php($seoDesc = $seo['description'] ?? 'Plan every detail of your wedding for free and discover trusted Ontario vendors — compare real quotes and book, all in one place.')
        @php($seoCanonical = $seo['canonical'] ?? url()->current())
        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDesc }}">
        <link rel="canonical" href="{{ $seoCanonical }}">
        @unless($seo['index'] ?? true)
            <meta name="robots" content="noindex, follow">
        @endunless

        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDesc }}">
        <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
        <meta property="og:url" content="{{ $seoCanonical }}">
        <meta property="og:image" content="{{ $seo['image'] ?? url('/images/og-default.jpg') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $seo['image'] ?? url('/images/og-default.jpg') }}">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDesc }}">

        @foreach(array_merge(\App\Support\Seo::siteSchemas(), $seo['schemas'] ?? []) as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endforeach

        {{-- Analytics + Search Console (env-driven; consent-gated; prod-only) --}}
        @include('partials.analytics')

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        {{-- SEO <head> is server-rendered above by blade; the body is server-rendered
             via the @inertia directive (which sets the page + dispatches to the SSR
             server) and hydrated on the client. --}}
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

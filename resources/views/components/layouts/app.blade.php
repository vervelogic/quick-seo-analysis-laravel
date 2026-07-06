@props([
    'title' => config('app.name'),
    'meta' => [],
    'structuredData' => [],
])

@php
    $isHomeRoute = request()->routeIs('home');
    $homeUrl = route('home');
    $homeSocialImage = asset('images/social/home-share-card.svg');
    $supportEmail = 'hello@quickseoanalysis.com';

    $homeSeoMeta = $isHomeRoute ? [
        'title' => 'SEO Checker With Free Audit Report-Quick SEO Analysis',
        'description' => 'Analyze your website with best SEO Optimizer. This tool or software generates free audit report along with SEO tips and reviews  to improve rankings on SERPs',
        'keywords' => 'Seo Audit, Seo Checker, Seo Tools, Seo Optimizer, Seo Analyzer, Quick Seo Analysis, Free Audit Report,Quick SEO Analysis, Website SEO Checker',
        'robots' => 'index,archive,follow',
        'canonical' => $homeUrl,
        'language' => 'en-US',
        'author' => 'quickseoanalysis',
        'application_name' => 'quickseoanalysis',
        'theme_color' => '#020617',
        'favicon' => asset('favicon.png'),
        'open_graph' => [
            'title' => 'SEO Checker With Free Audit Report-Quick SEO Analysis',
            'description' => 'Analyze your website with best SEO Optimizer. This tool or software generates free audit report along with SEO tips and reviews  to improve rankings on SERPs',
            'url' => $homeUrl,
            'type' => 'website',
            'image' => $homeSocialImage,
            'image_alt' => 'Quick SEO Analysis homepage preview card',
            'site_name' => 'Quick SEO Analysis',
            'locale' => 'en_US',
        ],
        'twitter' => [
            'card' => 'summary_large_image',
            'title' => 'SEO Checker With Free Audit Report-Quick SEO Analysis',
            'description' => 'Analyze your website with best SEO Optimizer. This tool or software generates free audit report along with SEO tips and reviews  to improve rankings on SERPs',
            'image' => $homeSocialImage,
            'image_alt' => 'Quick SEO Analysis homepage preview card',
        ],
        'extra_meta' => [
            ['name' => 'url', 'content' => $homeUrl],
            ['name' => 'title', 'content' => 'SEO Checker With Free Audit Report-Quick SEO Analysis'],
        ],
        'alternates' => [
            ['hreflang' => 'en', 'href' => $homeUrl],
            ['hreflang' => 'x-default', 'href' => $homeUrl],
        ],
    ] : [];

    $homeStructuredData = $isHomeRoute ? [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Quick SEO Analysis',
            'url' => $homeUrl,
            'logo' => asset('favicon.png'),
            'email' => $supportEmail,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $supportEmail,
                'url' => route('contact'),
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Quick SEO Analysis',
            'url' => $homeUrl,
            'description' => 'Analyze your website with best SEO Optimizer. This tool or software generates free audit report along with SEO tips and reviews  to improve rankings on SERPs',
            'inLanguage' => 'en-US',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('home').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Quick SEO Analysis',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $homeUrl,
            'description' => 'Analyze your website with best SEO Optimizer. This tool or software generates free audit report along with SEO tips and reviews  to improve rankings on SERPs',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'Do I need an account to run a scan?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'No. You can run a public scan and review the preview before deciding to log in for PDF access.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is the difference between the two audit paths?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Current Visibility shows what the page communicates today. Keyword Focus reviews whether the page supports the keywords you are already targeting.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Does QSA track rankings or search volume?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Not in these audits. QSA focuses on page signals, intent alignment, visibility readiness, and what should be improved next.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Can I share the report with clients or stakeholders?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Yes. Logged-in users can download a PDF report for review and follow-up conversations.',
                    ],
                ],
            ],
        ],
    ] : [];

    $mergedMeta = array_replace_recursive($homeSeoMeta, $meta);
    $mergedStructuredData = array_merge($homeStructuredData, $structuredData);

    $resolvedTitle = $mergedMeta['title'] ?? $title ?? config('app.name');
    $resolvedDescription = $mergedMeta['description'] ?? null;
    $resolvedKeywords = $mergedMeta['keywords'] ?? null;
    $resolvedRobots = $mergedMeta['robots'] ?? null;
    $resolvedCanonical = $mergedMeta['canonical'] ?? null;
    $resolvedCharset = $mergedMeta['charset'] ?? 'utf-8';
    $resolvedViewport = $mergedMeta['viewport'] ?? 'width=device-width, initial-scale=1';
    $resolvedLanguage = $mergedMeta['language'] ?? str_replace('_', '-', app()->getLocale());
    $resolvedAuthor = $mergedMeta['author'] ?? null;
    $resolvedApplicationName = $mergedMeta['application_name'] ?? config('app.name');
    $resolvedThemeColor = $mergedMeta['theme_color'] ?? null;
    $resolvedFavicon = $mergedMeta['favicon'] ?? asset('favicon.png');
    $resolvedManifest = $mergedMeta['manifest'] ?? null;
    $resolvedOg = array_merge([
        'title' => $resolvedTitle,
        'description' => $resolvedDescription,
        'url' => $resolvedCanonical,
        'type' => 'website',
        'image' => null,
        'image_alt' => null,
        'site_name' => config('app.name'),
        'locale' => 'en_US',
    ], $mergedMeta['open_graph'] ?? []);
    $resolvedTwitter = array_merge([
        'card' => 'summary_large_image',
        'title' => $resolvedOg['title'] ?? $resolvedTitle,
        'description' => $resolvedOg['description'] ?? $resolvedDescription,
        'image' => $resolvedOg['image'] ?? null,
        'image_alt' => $resolvedOg['image_alt'] ?? null,
    ], $mergedMeta['twitter'] ?? []);
    $resolvedAlternates = $mergedMeta['alternates'] ?? [];
    $resolvedExtraMeta = $mergedMeta['extra_meta'] ?? [];
@endphp

<!DOCTYPE html>
<html lang="{{ $resolvedLanguage }}">
<head>
    <meta charset="{{ $resolvedCharset }}">
    <meta name="viewport" content="{{ $resolvedViewport }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $resolvedTitle }}</title>
    @if ($resolvedDescription)
        <meta name="description" content="{{ $resolvedDescription }}">
    @endif
    @if ($resolvedKeywords)
        <meta name="keywords" content="{{ $resolvedKeywords }}">
    @endif
    @if ($resolvedRobots)
        <meta name="robots" content="{{ $resolvedRobots }}">
    @endif
    @if ($resolvedApplicationName)
        <meta name="application-name" content="{{ $resolvedApplicationName }}">
    @endif
    @if ($resolvedAuthor)
        <meta name="author" content="{{ $resolvedAuthor }}">
    @endif
    <meta name="language" content="{{ $resolvedLanguage }}">
    @if ($resolvedThemeColor)
        <meta name="theme-color" content="{{ $resolvedThemeColor }}">
    @endif
    @foreach ($resolvedExtraMeta as $extraMeta)
        @if (! empty($extraMeta['name']) && array_key_exists('content', $extraMeta))
            <meta name="{{ $extraMeta['name'] }}" content="{{ $extraMeta['content'] }}">
        @elseif (! empty($extraMeta['property']) && array_key_exists('content', $extraMeta))
            <meta property="{{ $extraMeta['property'] }}" content="{{ $extraMeta['content'] }}">
        @elseif (! empty($extraMeta['http_equiv']) && array_key_exists('content', $extraMeta))
            <meta http-equiv="{{ $extraMeta['http_equiv'] }}" content="{{ $extraMeta['content'] }}">
        @endif
    @endforeach
    @if ($resolvedCanonical)
        <link rel="canonical" href="{{ $resolvedCanonical }}">
    @endif
    <link rel="icon" type="image/png" href="{{ $resolvedFavicon }}">
    @if ($resolvedManifest)
        <link rel="manifest" href="{{ $resolvedManifest }}">
    @endif
    @foreach ($resolvedAlternates as $alternate)
        @if (! empty($alternate['href']) && ! empty($alternate['hreflang']))
            <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}">
        @endif
    @endforeach
    @if ($resolvedOg['title'])
        <meta property="og:title" content="{{ $resolvedOg['title'] }}">
    @endif
    @if ($resolvedOg['description'])
        <meta property="og:description" content="{{ $resolvedOg['description'] }}">
    @endif
    @if ($resolvedOg['url'])
        <meta property="og:url" content="{{ $resolvedOg['url'] }}">
    @endif
    @if ($resolvedOg['type'])
        <meta property="og:type" content="{{ $resolvedOg['type'] }}">
    @endif
    @if ($resolvedOg['image'])
        <meta property="og:image" content="{{ $resolvedOg['image'] }}">
    @endif
    @if ($resolvedOg['image_alt'])
        <meta property="og:image:alt" content="{{ $resolvedOg['image_alt'] }}">
    @endif
    @if ($resolvedOg['site_name'])
        <meta property="og:site_name" content="{{ $resolvedOg['site_name'] }}">
    @endif
    @if ($resolvedOg['locale'])
        <meta property="og:locale" content="{{ $resolvedOg['locale'] }}">
    @endif
    @if ($resolvedTwitter['card'])
        <meta name="twitter:card" content="{{ $resolvedTwitter['card'] }}">
    @endif
    @if ($resolvedTwitter['title'])
        <meta name="twitter:title" content="{{ $resolvedTwitter['title'] }}">
    @endif
    @if ($resolvedTwitter['description'])
        <meta name="twitter:description" content="{{ $resolvedTwitter['description'] }}">
    @endif
    @if ($resolvedTwitter['image'])
        <meta name="twitter:image" content="{{ $resolvedTwitter['image'] }}">
    @endif
    @if ($resolvedTwitter['image_alt'])
        <meta name="twitter:image:alt" content="{{ $resolvedTwitter['image_alt'] }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }

            html,
            body {
                background: #ffffff !important;
                color: #020617 !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print,
            body > header,
            body > footer {
                display: none !important;
            }

            main {
                display: block !important;
            }

            section,
            article,
            aside,
            table,
            .rounded-xl,
            .rounded-lg {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .lg\:sticky {
                position: static !important;
            }

            a[href]::after {
                content: "" !important;
            }
        }
    </style>
    @foreach ($mergedStructuredData as $schema)
        @if ($schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
        @endif
    @endforeach
</head>
<body class="bg-white text-slate-950 antialiased">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-lg bg-blue-600 text-sm font-black text-white">Q</span>
                <span class="text-base font-bold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 sm:flex">
                <a class="hover:text-slate-950" href="{{ route('home') }}#benefits">Benefits</a>
                <a class="hover:text-slate-950" href="{{ route('home') }}#checks">Checks</a>
                <a class="hover:text-slate-950" href="{{ route('blog.index') }}">Blog</a>
                <a class="hover:text-slate-950" href="{{ route('about') }}">About</a>
                <a class="hover:text-slate-950" href="{{ route('contact') }}">Contact</a>
                <a class="hover:text-slate-950" href="{{ auth()->check() ? route('dashboard.index') : route('login') }}">{{ auth()->check() ? 'Dashboard' : 'Login' }}</a>
                <a class="rounded-lg bg-slate-950 px-4 py-2 text-white hover:bg-slate-800" href="{{ route('home') }}#scan">Free report</a>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    @if (request()->routeIs('report.show') && auth()->check() && request()->route('scan'))
        <a href="{{ route('report.pdf', ['scan' => request()->route('scan')->uuid]) }}" class="no-print fixed bottom-5 right-5 z-50 inline-flex items-center justify-center rounded-full bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-xl shadow-blue-950/20 ring-1 ring-blue-500 hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
            Download PDF
        </a>
    @endif

    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-7xl px-5 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ config('app.name') }}</p>
                    <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">Search and AI visibility intelligence for teams that need faster answers, clearer priorities, and stronger crawl signals.</p>
                    <p class="mt-2 text-sm text-slate-600">Questions? <a href="mailto:{{ $supportEmail }}" class="font-medium text-blue-700 hover:text-blue-800">{{ $supportEmail }}</a></p>
                </div>
                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm text-slate-500">
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="{{ route('blog.index') }}">Blog</a>
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="{{ route('about') }}">About</a>
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="{{ route('services') }}">Services</a>
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="{{ route('contact') }}">Contact</a>
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="{{ route('robots') }}">robots.txt</a>
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="{{ route('sitemap') }}">sitemap.xml</a>
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="{{ auth()->check() ? route('dashboard.index') : route('login') }}">{{ auth()->check() ? 'Dashboard' : 'Client Login' }}</a>
                    <a class="font-medium text-slate-700 hover:text-blue-700" href="/admin">Admin</a>
                </div>
            </div>
            <p class="mt-6 text-sm text-slate-500">&copy; {{ date('Y') }} {{ config('app.name') }}. Built for fast SEO discovery.</p>
        </div>
    </footer>
</body>
</html>

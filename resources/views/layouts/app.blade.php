@php
    $seo = $seo ?? [];
    $siteName = site_settings('seo.site_name') ?: 'Kaniva Bangladesh';
    $title = $seo['title'] ?? (site_settings('seo.default_title') ?: 'Kaniva Bangladesh — Premium Cat & Dog Food');
    $description = $seo['description'] ?? (site_settings('seo.default_description') ?: 'Buy Kaniva cat food and dog food online in Bangladesh. Grain free, lab tested, nationwide delivery with cash on delivery, bKash and Nagad.');
    $ogImage = $seo['og_image'] ?? asset('images/hero-kaniva.jpg');
    $canonical = $seo['canonical'] ?? null;
    $cartCount = app(\App\Support\Cart::class)->count();
@endphp
<!DOCTYPE html>
<html lang="{{ lang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    @if (!empty($seo['keywords']))<meta name="keywords" content="{{ $seo['keywords'] }}">@endif
    <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
    @if ($canonical)<link rel="canonical" href="{{ $canonical }}">@endif
    <meta name="author" content="{{ $siteName }}">
    <meta name="theme-color" content="#8B2E1C">

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $ogImage }}">
    @if ($canonical)<meta property="og:url" content="{{ $canonical }}">@endif
    <meta property="og:locale" content="{{ lang() === 'bn' ? 'bn_BD' : 'en_US' }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap">
    @stack('preload')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @foreach ($seo['jsonld'] ?? [] as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-[60] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground">Skip to content</a>
    <div class="flex min-h-screen flex-col">
        @include('partials.header', ['cartCount' => $cartCount])
        <main id="main" class="flex-1">
            @yield('content')
        </main>
        @include('partials.footer')
    </div>
    @include('partials.whatsapp')
    @include('partials.toasts')
</body>
</html>

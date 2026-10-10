@php
    $links = [
        ['admin.dashboard', 'Dashboard', 'layout-dashboard', 'admin.dashboard'],
        ['admin.orders.index', 'Orders', 'receipt', 'admin.orders.*'],
        ['admin.products.index', 'Products', 'package', 'admin.products.*'],
        ['admin.categories.index', 'Categories', 'folder', 'admin.categories.*'],
        ['admin.coupons.index', 'Coupons', 'ticket', 'admin.coupons.*'],
        ['admin.blog.index', 'Blog', 'newspaper', 'admin.blog.*'],
        ['admin.reviews.index', 'Reviews', 'message-square', 'admin.reviews.*'],
        ['admin.homepage', 'Homepage', 'image', 'admin.homepage*'],
        ['admin.settings', 'Settings', 'settings', 'admin.settings*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') — Kaniva Admin</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream/60">
<div class="lg:flex">
    <aside class="hidden w-60 shrink-0 border-r border-border bg-background lg:sticky lg:top-0 lg:block lg:h-screen">
        <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center gap-2 px-5 text-primary">
            {!! icon('paw-print', 'size-6') !!}<span class="font-extrabold">KANIVA ADMIN</span>
        </a>
        <nav class="space-y-1 px-3" aria-label="Admin">
            @foreach ($links as [$route, $label, $ic, $pattern])
                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs($pattern) ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-cream' }}">
                    {!! icon($ic, 'size-4') !!} {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="absolute inset-x-3 bottom-4 space-y-1">
            <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-cream">{!! icon('external-link', 'size-4') !!} View site</a>
            <form method="post" action="{{ route('logout') }}">@csrf
                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-cream">{!! icon('log-out', 'size-4') !!} Sign out</button>
            </form>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        {{-- Mobile top bar --}}
        <div class="sticky top-0 z-30 border-b border-border bg-background lg:hidden">
            <div class="flex h-14 items-center justify-between px-4">
                <span class="flex items-center gap-2 font-extrabold text-primary">{!! icon('paw-print', 'size-5') !!} KANIVA ADMIN</span>
                <div class="flex items-center gap-1">
                    <a href="{{ route('home') }}" class="btn-icon" aria-label="View site">{!! icon('external-link', 'size-4') !!}</a>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn-icon" aria-label="Sign out">{!! icon('log-out', 'size-4') !!}</button></form>
                </div>
            </div>
            <nav class="no-scrollbar flex gap-2 overflow-x-auto px-4 pb-3">
                @foreach ($links as [$route, $label, $ic, $pattern])
                    <a href="{{ route($route) }}" class="chip h-9 {{ request()->routeIs($pattern) ? 'chip-active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <main class="mx-auto max-w-6xl p-4 md:p-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-extrabold md:text-2xl">@yield('title')</h1>
                <div class="flex flex-wrap gap-2">@yield('actions')</div>
            </div>
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-destructive/30 bg-background p-4 text-sm text-destructive" role="alert">
                    <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@include('partials.toasts')
</body>
</html>

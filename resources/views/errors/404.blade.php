@extends('layouts.app', ['seo' => ($seo ?? []) + ['title' => ($message ?? 'Page not found').' — Kaniva Bangladesh', 'robots' => 'noindex']])

@section('content')
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="max-w-md text-center">
        <p class="text-7xl font-extrabold text-primary">404</p>
        <h1 class="mt-4 text-xl font-bold">{{ $message ?? 'Page not found' }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">The page you're looking for doesn't exist or has been moved.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <a href="{{ route('home') }}" class="btn btn-outline h-10">Go home</a>
            <a href="{{ route('shop') }}" class="btn btn-primary h-10">Browse the shop</a>
        </div>
    </div>
</div>
@endsection

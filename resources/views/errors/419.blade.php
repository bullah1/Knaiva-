@extends('layouts.app', ['seo' => ['title' => 'Page expired — Kaniva Bangladesh', 'robots' => 'noindex']])

@section('content')
<div class="flex min-h-[60vh] items-center justify-center px-4 text-center">
    <div>
        <h1 class="text-xl font-bold">This page expired</h1>
        <p class="mt-2 text-sm text-muted-foreground">Please go back, refresh the page and try again.</p>
        <a href="{{ url()->previous() }}" class="btn btn-primary mt-6 h-10">Go back</a>
    </div>
</div>
@endsection

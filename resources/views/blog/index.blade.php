@extends('layouts.app')

@section('content')
<div class="container-page pb-12">
    <x-breadcrumbs :items="[['Home', '/'], ['Pet Tips', null]]" />
    <x-section-title title="Pet Tips" as="h1" subtitle="Care advice written for Bangladeshi pet parents." />
    @if ($posts->isEmpty())
        <p class="py-16 text-center text-sm text-muted-foreground">No articles yet.</p>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($posts as $post)
                @include('blog.card', ['post' => $post, 'heading' => 'h2'])
            @endforeach
        </div>
    @endif
</div>
@endsection

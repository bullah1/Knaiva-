@extends('layouts.app')

@section('content')
<article class="container-page max-w-3xl pb-12">
    <x-breadcrumbs :items="$crumbs" />
    <h1 class="text-3xl font-extrabold">{{ $post->title }}</h1>
    <p class="mt-2 text-sm text-muted-foreground">
        {{ $post->author }}
        @if ($post->published_at) · <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('j M Y') }}</time>@endif
    </p>
    @if ($post->coverUrl())
        <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-2xl object-cover">
    @endif

    <div class="prose-content mt-6">{!! $post->renderedContent() !!}</div>

    <aside class="mt-10 rounded-2xl bg-cream p-6 text-center">
        <p class="text-base font-bold">Looking for food your pet will actually finish?</p>
        <a href="{{ route('shop') }}" class="btn btn-primary mt-4 h-11 px-8">Shop Kaniva</a>
    </aside>

    @if ($related->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-lg font-extrabold">More Pet Tips</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($related as $item)
                    <li><a href="{{ route('blog.show', $item->slug) }}" class="text-primary hover:underline">{{ $item->title }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif
</article>
@endsection

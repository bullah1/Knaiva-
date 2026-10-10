<article class="card-soft flex flex-col overflow-hidden">
    @if ($post->coverUrl())
        <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" loading="lazy" class="h-44 w-full object-cover">
    @else
        <div class="flex h-44 items-center justify-center bg-accent">{!! icon('paw-print', 'size-10 text-primary') !!}</div>
    @endif
    <div class="flex flex-1 flex-col p-4">
        @if ($post->category)
            <p class="text-xs font-semibold uppercase text-muted-foreground">{{ $post->category }}</p>
        @endif
        <{{ $heading ?? 'h2' }} class="mt-1 text-base font-extrabold">
            <a href="{{ route('blog.show', $post->slug) }}" class="text-primary hover:underline">{{ $post->title }}</a>
        </{{ $heading ?? 'h2' }}>
        <p class="mt-2 line-clamp-3 text-sm text-muted-foreground">{{ $post->excerpt }}</p>
        @if ($post->published_at)
            <time datetime="{{ $post->published_at->toDateString() }}" class="mt-2 text-xs text-muted-foreground">{{ $post->published_at->format('j M Y') }}</time>
        @endif
        <a href="{{ route('blog.show', $post->slug) }}" class="btn btn-outline mt-4 h-10 self-start">{{ __('ui.readMore') }}<span class="sr-only">: {{ $post->title }}</span></a>
    </div>
</article>

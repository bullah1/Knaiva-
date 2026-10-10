@extends('admin.layout')
@section('title', 'Blog')
@section('actions')
    <a href="{{ route('admin.blog.create') }}" class="btn btn-primary h-10">{!! icon('plus') !!} New post</a>
@endsection

@section('content')
<div class="card-soft divide-y divide-border">
    @forelse ($posts as $post)
        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
            <div>
                <a href="{{ route('admin.blog.edit', $post) }}" class="font-bold text-primary hover:underline">{{ $post->title }}</a>
                <p class="text-xs text-muted-foreground">/blog/{{ $post->slug }} · {{ $post->category ?: 'Uncategorised' }} · {{ $post->published_at?->format('j M Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold {{ $post->is_published ? 'text-success' : 'text-muted-foreground' }}">{{ $post->is_published ? 'Published' : 'Draft' }}</span>
                @if ($post->is_published)<a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="btn-icon" aria-label="View">{!! icon('eye', 'size-4') !!}</a>@endif
                <a href="{{ route('admin.blog.edit', $post) }}" class="btn-icon" aria-label="Edit">{!! icon('pencil', 'size-4') !!}</a>
            </div>
        </div>
    @empty
        <p class="p-6 text-center text-muted-foreground">No posts yet.</p>
    @endforelse
</div>
<div class="mt-4">{{ $posts->links() }}</div>
@endsection

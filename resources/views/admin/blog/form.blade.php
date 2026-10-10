@extends('admin.layout')
@section('title', $post->exists ? 'Edit post' : 'New post')
@section('actions')
    <a href="{{ route('admin.blog.index') }}" class="btn btn-ghost h-10">{!! icon('arrow-left') !!} All posts</a>
@endsection

@section('content')
@php $v = fn ($k, $d = null) => old($k, $post->{$k} ?? $d); @endphp
<form method="post" enctype="multipart/form-data" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}" class="grid gap-6 lg:grid-cols-[1fr_300px]">
    @csrf
    @if ($post->exists) @method('PUT') @endif
    <div class="card-soft grid gap-4 p-5">
        <div><label class="label">Title *</label><input name="title" value="{{ $v('title') }}" required class="input"></div>
        <div><label class="label">Excerpt</label><textarea name="excerpt" rows="2" class="textarea">{{ $v('excerpt') }}</textarea></div>
        <div>
            <label class="label">Content</label>
            <textarea name="content" rows="16" class="textarea font-mono text-sm">{{ $v('content') }}</textarea>
            <p class="mt-1 text-xs text-muted-foreground">Start a line with <code>## </code> for a heading, <code>### </code> for a sub-heading and <code>- </code> for a bullet. Blank lines separate paragraphs.</p>
        </div>
        <h2 class="pt-2 font-extrabold">SEO</h2>
        <div><label class="label">SEO title</label><input name="seo_title" value="{{ $v('seo_title') }}" class="input"></div>
        <div><label class="label">SEO description</label><textarea name="seo_description" rows="2" class="textarea">{{ $v('seo_description') }}</textarea></div>
        <div><label class="label">SEO keywords</label><input name="seo_keywords" value="{{ $v('seo_keywords') }}" class="input"></div>
    </div>
    <div class="space-y-6">
        <div class="card-soft space-y-3 p-5">
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->is_published)) class="size-4 accent-[var(--color-primary)]"> Published</label>
            <div><label class="label">Slug</label><input name="slug" value="{{ $v('slug') }}" placeholder="auto from title" class="input"></div>
            <div><label class="label">Category</label><input name="category" value="{{ $v('category') }}" placeholder="Pet Tips" class="input"></div>
            <div><label class="label">Author</label><input name="author" value="{{ $v('author') }}" class="input"></div>
            <div><label class="label">Publish date</label><input name="published_at" type="date" value="{{ old('published_at', $post->published_at?->format('Y-m-d')) }}" class="input"></div>
            <button class="btn btn-primary h-11 w-full">Save post</button>
        </div>
        <div class="card-soft space-y-3 p-5">
            <h2 class="font-extrabold">Cover image</h2>
            @if ($post->coverUrl())
                <img src="{{ $post->coverUrl() }}" alt="" class="w-full rounded-lg object-cover">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_cover" value="1"> Remove cover</label>
            @endif
            <input type="file" name="cover" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-cream file:px-3 file:py-2 file:font-semibold file:text-primary">
        </div>
    </div>
</form>
@if ($post->exists)
    <form method="post" action="{{ route('admin.blog.destroy', $post) }}" class="mt-8" onsubmit="return confirm('Delete this post?')">@csrf @method('DELETE')
        <button class="btn btn-danger h-10">{!! icon('trash-2') !!} Delete post</button>
    </form>
@endif
@endsection

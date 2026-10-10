@extends('admin.layout')
@section('title', $category->exists ? 'Edit category' : 'New category')
@section('actions')
    <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost h-10">{!! icon('arrow-left') !!} All categories</a>
@endsection

@section('content')
<form method="post" enctype="multipart/form-data" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card-soft grid max-w-3xl gap-4 p-5 md:grid-cols-2">
    @csrf
    @if ($category->exists) @method('PUT') @endif
    <div><label class="label">Name (English) *</label><input name="name" value="{{ old('name', $category->name) }}" required class="input"></div>
    <div><label class="label">Name (বাংলা)</label><input name="name_bn" value="{{ old('name_bn', $category->name_bn) }}" class="input"></div>
    <div><label class="label">Slug</label><input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="auto from name" class="input"></div>
    <div><label class="label">Sort order</label><input name="sort_order" type="number" value="{{ old('sort_order', $category->sort_order) }}" class="input"></div>
    <div class="md:col-span-2"><label class="label">Intro text (English) <span class="font-normal text-muted-foreground">— shown under the category heading, good for SEO</span></label><textarea name="description" rows="2" class="textarea">{{ old('description', $category->description) }}</textarea></div>
    <div class="md:col-span-2"><label class="label">Intro text (বাংলা)</label><textarea name="description_bn" rows="2" class="textarea">{{ old('description_bn', $category->description_bn) }}</textarea></div>
    <div class="md:col-span-2"><label class="label">SEO title</label><input name="seo_title" value="{{ old('seo_title', $category->seo_title) }}" class="input"></div>
    <div class="md:col-span-2"><label class="label">SEO description</label><textarea name="seo_description" rows="2" class="textarea">{{ old('seo_description', $category->seo_description) }}</textarea></div>
    <div class="md:col-span-2"><label class="label">SEO keywords</label><input name="seo_keywords" value="{{ old('seo_keywords', $category->seo_keywords) }}" class="input"></div>
    <div class="md:col-span-2">
        <label class="label">Image</label>
        @if ($category->image_url)
            <div class="mb-2 flex items-center gap-3"><img src="{{ upload_url($category->image_url) }}" alt="" class="size-16 rounded-full object-cover">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1"> Remove</label></div>
        @endif
        <input type="file" name="image" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-cream file:px-3 file:py-2 file:font-semibold file:text-primary">
    </div>
    <label class="flex items-center gap-2 text-sm font-semibold md:col-span-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="size-4 accent-[var(--color-primary)]"> Active</label>
    <button class="btn btn-primary h-11 md:col-span-2">Save category</button>
</form>
@endsection

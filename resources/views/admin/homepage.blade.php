@extends('admin.layout')
@section('title', 'Homepage content')

@section('content')
@php
    $file = 'block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-cream file:px-3 file:py-2 file:font-semibold file:text-primary';
@endphp

{{-- Hero banners --}}
<section class="mb-10">
    <h2 class="mb-1 text-lg font-extrabold">Hero banner</h2>
    <p class="mb-3 text-sm text-muted-foreground">The first active banner (lowest sort order) is shown at the top of the homepage.</p>
    <div class="space-y-4">
        @foreach ($banners->push(new \App\Models\HeroBanner(['is_active' => true, 'cta_label' => 'Shop Now', 'cta_link' => '/shop'])) as $b)
            <form method="post" enctype="multipart/form-data" action="{{ $b->exists ? route('admin.homepage.update', ['banner', $b->id]) : route('admin.homepage.store', 'banner') }}" class="card-soft grid gap-3 p-4 md:grid-cols-2 {{ $b->exists ? '' : 'border-dashed' }}">
                @csrf @if ($b->exists) @method('PUT') @endif
                @unless ($b->exists)<p class="font-bold text-primary md:col-span-2">Add a banner</p>@endunless
                <div><label class="label">Title (EN) *</label><input name="title" value="{{ $b->title }}" required class="input"></div>
                <div><label class="label">Title (বাংলা)</label><input name="title_bn" value="{{ $b->title_bn }}" class="input"></div>
                <div><label class="label">Subtitle (EN)</label><textarea name="subtitle" rows="2" class="textarea">{{ $b->subtitle }}</textarea></div>
                <div><label class="label">Subtitle (বাংলা)</label><textarea name="subtitle_bn" rows="2" class="textarea">{{ $b->subtitle_bn }}</textarea></div>
                <div><label class="label">Button label</label><input name="cta_label" value="{{ $b->cta_label }}" class="input"></div>
                <div><label class="label">Button link</label><input name="cta_link" value="{{ $b->cta_link }}" class="input"></div>
                <div>
                    <label class="label">Image (1600×1000 recommended)</label>
                    @if ($b->image_url)<div class="mb-2 flex items-center gap-3"><img src="{{ upload_url($b->image_url) }}" class="h-14 rounded-lg" alt=""><label class="text-sm"><input type="checkbox" name="remove_image" value="1"> Remove</label></div>@endif
                    <input type="file" name="image" accept="image/*" class="{{ $file }}">
                </div>
                <div class="flex items-end gap-3">
                    <div class="w-24"><label class="label">Order</label><input name="sort_order" type="number" value="{{ $b->sort_order ?? 0 }}" class="input"></div>
                    <label class="mb-3 flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_active" value="1" @checked($b->is_active) class="size-4 accent-[var(--color-primary)]"> Active</label>
                </div>
                <div class="flex gap-2 md:col-span-2">
                    <button class="btn btn-primary h-10">{{ $b->exists ? 'Save' : 'Add banner' }}</button>
                    @if ($b->exists)<button form="del-banner-{{ $b->id }}" class="btn btn-danger h-10">Delete</button>@endif
                </div>
            </form>
            @if ($b->exists)<form id="del-banner-{{ $b->id }}" method="post" action="{{ route('admin.homepage.destroy', ['banner', $b->id]) }}" onsubmit="return confirm('Delete banner?')">@csrf @method('DELETE')</form>@endif
        @endforeach
    </div>
</section>

{{-- Trust points --}}
<section class="mb-10">
    <h2 class="mb-3 text-lg font-extrabold">Why choose us</h2>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($trustPoints->push(new \App\Models\TrustPoint(['is_active' => true, 'icon' => 'paw'])) as $t)
            <form method="post" action="{{ $t->exists ? route('admin.homepage.update', ['trust', $t->id]) : route('admin.homepage.store', 'trust') }}" class="card-soft grid gap-3 p-4 sm:grid-cols-2 {{ $t->exists ? '' : 'border-dashed' }}">
                @csrf @if ($t->exists) @method('PUT') @endif
                @unless ($t->exists)<p class="font-bold text-primary sm:col-span-2">Add a point</p>@endunless
                <div><label class="label">Title (EN) *</label><input name="title" value="{{ $t->title }}" required class="input"></div>
                <div><label class="label">Title (বাংলা)</label><input name="title_bn" value="{{ $t->title_bn }}" class="input"></div>
                <div><label class="label">Text (EN)</label><input name="description" value="{{ $t->description }}" class="input"></div>
                <div><label class="label">Text (বাংলা)</label><input name="description_bn" value="{{ $t->description_bn }}" class="input"></div>
                <div>
                    <label class="label">Icon</label>
                    <select name="icon" class="input">@foreach (\App\Models\TrustPoint::ICONS as $key => $label)<option value="{{ $key }}" @selected($t->icon === $key)>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="flex items-end gap-3">
                    <div class="w-20"><label class="label">Order</label><input name="sort_order" type="number" value="{{ $t->sort_order ?? 0 }}" class="input"></div>
                    <label class="mb-3 flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_active" value="1" @checked($t->is_active) class="size-4 accent-[var(--color-primary)]"> Active</label>
                </div>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn btn-primary h-10">{{ $t->exists ? 'Save' : 'Add' }}</button>
                    @if ($t->exists)<button form="del-trust-{{ $t->id }}" class="btn btn-danger h-10">Delete</button>@endif
                </div>
            </form>
            @if ($t->exists)<form id="del-trust-{{ $t->id }}" method="post" action="{{ route('admin.homepage.destroy', ['trust', $t->id]) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')</form>@endif
        @endforeach
    </div>
</section>

{{-- Certificates --}}
<section>
    <h2 class="mb-3 text-lg font-extrabold">Certificates</h2>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($certificates->push(new \App\Models\Certificate(['is_active' => true])) as $c)
            <form method="post" enctype="multipart/form-data" action="{{ $c->exists ? route('admin.homepage.update', ['certificate', $c->id]) : route('admin.homepage.store', 'certificate') }}" class="card-soft grid gap-3 p-4 {{ $c->exists ? '' : 'border-dashed' }}">
                @csrf @if ($c->exists) @method('PUT') @endif
                @unless ($c->exists)<p class="font-bold text-primary">Add a certificate</p>@endunless
                <div><label class="label">Title *</label><input name="title" value="{{ $c->title }}" required class="input"></div>
                <div>
                    <label class="label">Logo (optional)</label>
                    @if ($c->image_url)<div class="mb-2 flex items-center gap-3"><img src="{{ upload_url($c->image_url) }}" class="h-10" alt=""><label class="text-sm"><input type="checkbox" name="remove_image" value="1"> Remove</label></div>@endif
                    <input type="file" name="image" accept="image/*" class="{{ $file }}">
                </div>
                <div class="flex items-end gap-3">
                    <div class="w-20"><label class="label">Order</label><input name="sort_order" type="number" value="{{ $c->sort_order ?? 0 }}" class="input"></div>
                    <label class="mb-3 flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="size-4 accent-[var(--color-primary)]"> Active</label>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-primary h-10">{{ $c->exists ? 'Save' : 'Add' }}</button>
                    @if ($c->exists)<button form="del-cert-{{ $c->id }}" class="btn btn-danger h-10">Delete</button>@endif
                </div>
            </form>
            @if ($c->exists)<form id="del-cert-{{ $c->id }}" method="post" action="{{ route('admin.homepage.destroy', ['certificate', $c->id]) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')</form>@endif
        @endforeach
    </div>
</section>
@endsection

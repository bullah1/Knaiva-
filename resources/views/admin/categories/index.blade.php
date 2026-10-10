@extends('admin.layout')
@section('title', 'Categories')
@section('actions')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary h-10">{!! icon('plus') !!} Add category</a>
@endsection

@section('content')
<div class="card-soft divide-y divide-border">
    @forelse ($categories as $c)
        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center overflow-hidden rounded-full bg-cream">
                    @if ($c->image_url)<img src="{{ upload_url($c->image_url) }}" alt="" class="size-12 object-cover">@else{!! icon('paw-print', 'size-5 text-primary') !!}@endif
                </span>
                <div>
                    <p class="font-bold">{{ $c->name }} <span class="font-normal text-muted-foreground">{{ $c->name_bn }}</span></p>
                    <p class="text-xs text-muted-foreground">/category/{{ $c->slug }} · {{ $c->products_count }} products · order {{ $c->sort_order }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold {{ $c->is_active ? 'text-success' : 'text-muted-foreground' }}">{{ $c->is_active ? 'Active' : 'Hidden' }}</span>
                <a href="{{ route('admin.categories.edit', $c) }}" class="btn-icon" aria-label="Edit">{!! icon('pencil', 'size-4') !!}</a>
                <form method="post" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Delete {{ $c->name }}?')">@csrf @method('DELETE')
                    <button class="btn-icon text-destructive" aria-label="Delete">{!! icon('trash-2', 'size-4') !!}</button>
                </form>
            </div>
        </div>
    @empty
        <p class="p-6 text-center text-muted-foreground">No categories yet.</p>
    @endforelse
</div>
@endsection

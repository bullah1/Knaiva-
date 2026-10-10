@extends('admin.layout')
@section('title', 'Reviews')

@section('content')
<form method="post" action="{{ route('admin.reviews.store') }}" class="card-soft grid gap-3 p-5 md:grid-cols-4" x-data>
    @csrf
    <h2 class="font-extrabold md:col-span-4">Add a customer review</h2>
    <div class="md:col-span-2">
        <label class="label">Product</label>
        <select name="product_id" class="input"><option value="">General (no product)</option>
            @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
        </select>
    </div>
    <div><label class="label">Customer name *</label><input name="customer_name" required class="input"></div>
    <div><label class="label">Rating *</label><select name="rating" class="input">@for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} ★</option>@endfor</select></div>
    <div class="md:col-span-4"><label class="label">Review</label><textarea name="body" rows="2" class="textarea"></textarea></div>
    <label class="flex items-center gap-2 text-sm font-semibold md:col-span-3"><input type="checkbox" name="is_featured" value="1" class="size-4 accent-[var(--color-primary)]"> Feature on homepage</label>
    <button class="btn btn-primary h-10">Add review</button>
</form>

<div class="mt-6 space-y-3">
    @forelse ($reviews as $r)
        <div class="card-soft flex flex-wrap items-start justify-between gap-3 p-4 {{ $r->is_approved ? '' : 'border-amber-300' }}">
            <div class="max-w-2xl">
                <p class="flex items-center gap-2 font-bold"><x-stars :rating="$r->rating" /> {{ $r->customer_name }}</p>
                <p class="mt-1 text-sm">{{ $r->body }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ $r->product?->name ?? 'General' }} · {{ $r->created_at->format('j M Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @include('admin.partials.toggle', ['action' => route('admin.reviews.update', $r), 'method' => 'PATCH', 'field' => 'is_approved', 'on' => $r->is_approved, 'label' => 'Approved'])
                @include('admin.partials.toggle', ['action' => route('admin.reviews.update', $r), 'method' => 'PATCH', 'field' => 'is_featured', 'on' => $r->is_featured, 'label' => 'Featured'])
                <form method="post" action="{{ route('admin.reviews.destroy', $r) }}" onsubmit="return confirm('Delete this review?')">@csrf @method('DELETE')
                    <button class="btn-icon text-destructive" aria-label="Delete">{!! icon('trash-2', 'size-4') !!}</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-sm text-muted-foreground">No reviews yet.</p>
    @endforelse
</div>
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection

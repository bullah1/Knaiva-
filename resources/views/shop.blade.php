@extends('layouts.app')

@section('content')
@php
    $params = array_filter(['q' => $q ?: null, 'tag' => $tag, 'sort' => $sort !== 'popular' ? $sort : null]);
    $link = function (array $change) use ($params, $listUrl) {
        $base = $change['_base'] ?? $listUrl;
        unset($change['_base']);
        $query = array_filter(array_merge($params, $change), fn ($v) => $v !== null && $v !== '');
        return $base.($query ? '?'.http_build_query($query) : '');
    };
    $catName = $activeCategory ? pick($activeCategory->name, $activeCategory->name_bn) : null;
@endphp
<div class="container-page pb-12">
    <x-breadcrumbs :items="$crumbs" />
    <x-section-title :title="$catName ?? 'Shop All Products'" as="h1" :subtitle="$activeCategory ? pick($activeCategory->description, $activeCategory->description_bn) : 'Cat food, treats, litter and care products — delivered across Bangladesh.'" />

    <form action="{{ $listUrl }}" method="get" class="mx-auto mb-6 flex max-w-xl gap-2" role="search">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search by name, flavour or SKU" class="input h-10" aria-label="Search products">
        <button type="submit" class="btn btn-primary h-10">Search</button>
    </form>

    {{-- Categories --}}
    <nav class="no-scrollbar -mx-4 mb-3 flex gap-2 overflow-x-auto px-4 pb-1" aria-label="Categories">
        <a href="{{ $link(['_base' => route('shop'), 'tag' => null]) }}" class="chip {{ !$activeCategory ? 'chip-active' : '' }}">All</a>
        @foreach ($categories as $category)
            <a href="{{ $link(['_base' => $category->url(), 'tag' => null]) }}" class="chip {{ $activeCategory?->id === $category->id ? 'chip-active' : '' }}">{{ pick($category->name, $category->name_bn) }}</a>
        @endforeach
    </nav>

    {{-- Filters relevant to this category --}}
    @if ($filters->isNotEmpty())
        <div class="no-scrollbar -mx-4 mb-4 flex items-center gap-2 overflow-x-auto px-4 pb-1">
            <span class="shrink-0 text-xs font-bold uppercase text-muted-foreground">Filter:</span>
            @foreach ($filters as $key => [$tagValue, $label])
                <a href="{{ $link(['tag' => $tag === $key ? null : $key]) }}" rel="nofollow" class="chip h-9 text-xs {{ $tag === $key ? 'chip-active' : '' }}">{{ $label }}@if ($tag === $key) <span class="ml-1">✕</span>@endif</a>
            @endforeach
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">{{ $total }} {{ Str::plural('product', $total) }}</p>
        <label class="flex items-center gap-2 text-sm">
            <span class="text-muted-foreground">Sort:</span>
            <select class="h-10 rounded-xl border border-border bg-background px-3 text-sm font-semibold" onchange="window.location = this.value" aria-label="Sort products">
                @foreach (\App\Http\Controllers\ShopController::SORTS as $key => $label)
                    <option value="{{ $link(['sort' => $key === 'popular' ? null : $key]) }}" @selected($sort === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </div>

    @if ($products->isEmpty())
        <div class="py-16 text-center">
            <p class="text-sm text-muted-foreground">No products matched your search.</p>
            <a href="{{ $listUrl }}" class="btn btn-outline mt-4 h-10">Clear filters</a>
        </div>
    @else
        <h2 class="sr-only">{{ $catName ?? 'All products' }}</h2>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        @if ($hasMore)
            <div class="mt-8 text-center">
                <a href="{{ $link(['page' => $nextPage]) }}" rel="nofollow" class="btn btn-outline h-11 px-8">Load More</a>
            </div>
        @endif
    @endif
</div>
@endsection

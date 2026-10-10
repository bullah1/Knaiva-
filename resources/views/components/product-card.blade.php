@props(['product'])
@php
    $first = $product->firstVariant();
    $min = $product->minPrice();
    $available = $product->inStock();
    $discount = $product->discountPercent();
    $mrp = $first?->mrp;
    $url = $product->url();
    $sizes = $product->variants->count();
@endphp
<article class="card-soft flex flex-col overflow-hidden">
    <a href="{{ $url }}" class="relative block bg-white">
        <img src="{{ $product->mainImage() }}" alt="{{ $product->name }} pet food pack" loading="lazy" width="912" height="912" class="aspect-square w-full bg-white object-contain">
        @if ($discount > 0)
            <span class="absolute left-2 top-2 rounded-full bg-primary px-2 py-1 text-[11px] font-bold text-primary-foreground">-{{ $discount }}%</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col gap-1.5 p-3">
        <h3 class="text-sm font-bold md:text-base">
            <a href="{{ $url }}" class="line-clamp-2 text-primary hover:underline">{{ pick($product->name, $product->name_bn) }}</a>
        </h3>
        <p class="text-xs text-muted-foreground">{{ $first?->weight_label }}@if ($sizes > 1) · +{{ $sizes - 1 }} {{ Str::plural('size', $sizes - 1) }} @endif</p>
        @if ($product->review_count > 0)
            <div class="flex items-center gap-1 text-xs text-muted-foreground">
                <x-stars :rating="$product->rating" />
                <span>({{ $product->review_count }})</span>
            </div>
        @endif
        <div class="flex items-baseline gap-2">
            <span class="text-base font-extrabold">@if ($sizes > 1)<span class="text-xs font-semibold text-muted-foreground">From </span>@endif{{ bdt($min) }}</span>
            @if ($mrp && $mrp > $min)
                <span class="text-xs text-muted-foreground line-through">{{ bdt($mrp) }}</span>
            @endif
        </div>
        <p class="text-xs font-semibold {{ $available ? 'text-success' : 'text-destructive' }}">{{ $available ? __('ui.inStock') : __('ui.outOfStock') }}</p>
        <div class="mt-auto grid grid-cols-2 gap-2 pt-2">
            <a href="{{ $url }}" class="btn btn-outline h-10 px-2 text-xs sm:text-sm">{{ __('ui.details') }}</a>
            <form method="post" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="variant_id" value="{{ $first?->id }}">
                <button type="submit" class="btn btn-primary h-10 w-full px-2 text-xs sm:text-sm" @disabled(!$available || !$first)>{{ __('ui.addToCart') }}</button>
            </form>
        </div>
    </div>
</article>

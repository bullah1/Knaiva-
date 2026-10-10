@extends('layouts.app')

@section('content')
@php
    $variants = $product->variants;
    $first = $variants->first();
    $features = array_values(array_filter($product->features ?? []));
    $variantJson = $variants->map(fn ($v) => ['id' => $v->id, 'label' => $v->weight_label, 'price' => (float) $v->price, 'mrp' => $v->mrp ? (float) $v->mrp : null, 'stock' => $v->stock])->values();
    $tabs = array_filter([
        'description' => ['Description', pick($product->description, $product->description_bn)],
        'ingredients' => ['Ingredients', $product->ingredients],
        'nutrition' => ['Nutrition', $product->nutrition],
        'feeding' => ['Feeding Guide', $product->feeding_guide],
    ], fn ($t) => filled($t[1]));
@endphp
<div class="container-page pb-28 md:pb-12" x-data="productPage(@js($variantJson))">
    <x-breadcrumbs :items="$crumbs" />

    <div class="grid gap-8 md:grid-cols-2">
        <div>
            <div class="card-soft overflow-hidden bg-white">
                @foreach ($images as $i => $image)
                    <img src="{{ $image }}" alt="{{ $product->name }} pack shot{{ $i ? ' '.($i + 1) : '' }}" width="912" height="912"
                         class="aspect-square w-full object-contain" @if($i) loading="lazy" x-cloak @endif x-show="image === {{ $i }}">
                @endforeach
            </div>
            @if (count($images) > 1)
                <div class="mt-3 flex gap-2">
                    @foreach ($images as $i => $image)
                        <button type="button" @click="image = {{ $i }}" class="size-16 overflow-hidden rounded-lg border" :class="image === {{ $i }} ? 'border-primary' : 'border-border'" aria-label="Show image {{ $i + 1 }}">
                            <img src="{{ $image }}" alt="" loading="lazy" class="size-full bg-white object-contain">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h1 class="text-2xl font-extrabold md:text-3xl">{{ pick($product->name, $product->name_bn) }}</h1>
            <p class="mt-1 text-xs text-muted-foreground">SKU: {{ $product->sku ?: '—' }} · {{ $product->brand }}</p>
            @if ($product->review_count > 0)
                <a href="#reviews" class="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                    <x-stars :rating="$product->rating" size="size-4" />
                    <span>{{ number_format($product->rating, 1) }} ({{ $product->review_count }} reviews)</span>
                </a>
            @endif
            @if ($product->short_description)
                <p class="mt-3 text-sm text-muted-foreground">{{ $product->short_description }}</p>
            @endif

            @if ($first)
                <div class="mt-4 flex items-baseline gap-3">
                    <span class="text-3xl font-extrabold text-primary" x-text="bdt(variant.price)">{{ bdt($first->price) }}</span>
                    <template x-if="savePercent > 0">
                        <span class="flex items-baseline gap-3">
                            <span class="text-base text-muted-foreground line-through" x-text="bdt(variant.mrp)"></span>
                            <span class="rounded-full bg-accent px-2 py-1 text-xs font-bold text-accent-foreground" x-text="'Save ' + savePercent + '%'"></span>
                        </span>
                    </template>
                </div>
                <p class="mt-1 text-sm font-semibold" :class="available ? 'text-success' : 'text-destructive'"
                   x-text="available ? @js(__('ui.inStock')) : @js(__('ui.outOfStock'))">{{ $first->stock > 0 ? __('ui.inStock') : __('ui.outOfStock') }}</p>

                <div class="mt-5 {{ $variants->count() < 2 ? 'hidden' : '' }}">
                    <p class="text-sm font-bold">Size</p>
                    <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label="Size">
                        @foreach ($variants as $v)
                            <button type="button" role="radio" @click="variantId = {{ $v->id }}" :aria-checked="variantId === {{ $v->id }}"
                                    class="h-10 rounded-lg border px-4 text-sm font-semibold"
                                    :class="variantId === {{ $v->id }} ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background'">{{ $v->weight_label }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!empty($product->quick_info))
                <div class="mt-5 rounded-2xl bg-cream p-4">
                    <p class="text-sm font-extrabold text-primary">এক নজরে</p>
                    <dl class="mt-2 grid gap-1.5 text-sm">
                        @foreach ($product->quick_info as [$label, $value])
                            <div class="flex gap-2"><dt class="shrink-0 font-bold">{{ $label }}:</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if ($features)
                <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                    @foreach ($features as $feature)
                        <li class="flex items-center gap-2 text-sm">{!! icon('check', 'size-4 text-primary') !!} {{ $feature }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($first)
                <form method="post" action="{{ route('cart.add') }}" class="mt-6 space-y-3" id="add-to-cart">
                    @csrf
                    <input type="hidden" name="variant_id" :value="variantId" value="{{ $first->id }}">
                    <input type="hidden" name="quantity" :value="qty" value="1">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 items-center rounded-lg border border-border">
                            <button type="button" aria-label="Decrease quantity" class="px-3 text-primary" @click="qty = Math.max(1, qty - 1)">{!! icon('minus', 'size-4') !!}</button>
                            <span class="w-8 text-center text-sm font-bold" x-text="qty">1</span>
                            <button type="button" aria-label="Increase quantity" class="px-3 text-primary" @click="qty = Math.min(50, qty + 1)">{!! icon('plus', 'size-4') !!}</button>
                        </div>
                        <button type="submit" class="btn btn-primary h-11 flex-1" :disabled="!available" @disabled($first->stock <= 0)>{{ __('ui.addToCart') }}</button>
                    </div>
                    <button type="submit" name="buy_now" value="1" class="btn btn-outline h-11 w-full" :disabled="!available" @disabled($first->stock <= 0)>Buy Now</button>
                </form>
            @endif
        </div>
    </div>

    @if ($tabs)
        <div class="mt-10" x-data="{ tab: @js(array_key_first($tabs)) }">
            <div class="flex flex-wrap gap-1 rounded-xl bg-cream p-1" role="tablist">
                @foreach ($tabs as $key => [$label])
                    <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                            class="rounded-lg px-4 py-2 text-sm font-semibold"
                            :class="tab === '{{ $key }}' ? 'bg-background text-primary shadow-sm' : 'text-muted-foreground'">{{ $label }}</button>
                @endforeach
            </div>
            @foreach ($tabs as $key => [$label, $body])
                <section class="prose-content pt-4 text-sm" role="tabpanel" x-show="tab === '{{ $key }}'" @unless($loop->first) x-cloak @endunless>
                    <h2 class="sr-only">{{ $label }}</h2>
                    {!! nl2br(e($body)) !!}
                </section>
            @endforeach
        </div>
    @endif

    <section class="section" id="reviews">
        <x-section-title title="Customer Reviews" />
        <div class="grid gap-4 md:grid-cols-3">
            @forelse ($reviews as $review)
                <figure class="card-soft p-5">
                    <x-stars :rating="$review->rating" />
                    <blockquote class="mt-3 text-sm">{{ $review->body }}</blockquote>
                    <figcaption class="mt-3 text-sm font-bold text-primary">{{ $review->customer_name }}</figcaption>
                </figure>
            @empty
                <p class="text-sm text-muted-foreground md:col-span-3">No reviews yet for this product. Share yours by messaging us on WhatsApp.</p>
            @endforelse
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section-cream -mx-4 px-4 md:-mx-8 md:px-8">
            <x-section-title title="Related Products" />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($related as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($first)
        {{-- Sticky mobile add to cart --}}
        <div class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-3 border-t border-border bg-background p-3 md:hidden">
            <div>
                <p class="text-xs text-muted-foreground" x-text="variant.label">{{ $first->weight_label }}</p>
                <p class="text-lg font-extrabold text-primary" x-text="bdt(variant.price)">{{ bdt($first->price) }}</p>
            </div>
            <button type="submit" form="add-to-cart" class="btn btn-primary h-11 flex-1" :disabled="!available" @disabled($first->stock <= 0)>{{ __('ui.addToCart') }}</button>
        </div>
    @endif
</div>
@endsection

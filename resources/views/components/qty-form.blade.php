@props(['variantId', 'quantity'])
<div class="flex h-10 items-center rounded-lg border border-border">
    <form method="post" action="{{ route('cart.update') }}">
        @csrf
        <input type="hidden" name="variant_id" value="{{ $variantId }}">
        <input type="hidden" name="quantity" value="{{ $quantity - 1 }}">
        <button type="submit" aria-label="Decrease quantity" class="flex h-10 items-center px-3 text-primary">{!! icon('minus', 'size-4') !!}</button>
    </form>
    <span class="w-8 text-center text-sm font-bold">{{ $quantity }}</span>
    <form method="post" action="{{ route('cart.update') }}">
        @csrf
        <input type="hidden" name="variant_id" value="{{ $variantId }}">
        <input type="hidden" name="quantity" value="{{ $quantity + 1 }}">
        <button type="submit" aria-label="Increase quantity" class="flex h-10 items-center px-3 text-primary">{!! icon('plus', 'size-4') !!}</button>
    </form>
</div>

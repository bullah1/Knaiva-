@extends('layouts.app')

@section('content')
@if ($lines->isEmpty())
    <div class="container-page py-20 text-center">
        {!! icon('shopping-bag', 'mx-auto size-14 text-primary') !!}
        <h1 class="mt-4 text-2xl font-extrabold">{{ __('ui.emptyCart') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">Add some Kaniva food and your pet will thank you.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary mt-6 h-11 px-8">{{ __('ui.continueShopping') }}</a>
    </div>
@else
<div class="container-page pb-12">
    <x-breadcrumbs :items="[['Home', '/'], ['Cart', null]]" />
    <x-section-title :title="__('ui.cart')" as="h1" />

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-3">
            @foreach ($lines as $line)
                <div class="card-soft flex gap-3 p-3">
                    <img src="{{ $line->image }}" alt="{{ $line->product->name }}" loading="lazy" width="96" height="96" class="size-24 rounded-lg object-cover">
                    <div class="flex flex-1 flex-col">
                        <a href="{{ $line->product->url() }}" class="text-sm font-bold text-primary hover:underline">{{ pick($line->product->name, $line->product->name_bn) }}</a>
                        <p class="text-xs text-muted-foreground">{{ $line->variant->weight_label }}</p>
                        <p class="mt-1 text-sm font-extrabold">{{ bdt($line->price) }}</p>
                        @if ($line->variant->stock < $line->quantity)
                            <p class="text-xs font-semibold text-destructive">Only {{ $line->variant->stock }} left in stock</p>
                        @endif
                        <div class="mt-auto flex items-center justify-between gap-2">
                            <x-qty-form :variant-id="$line->variant->id" :quantity="$line->quantity" />
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-extrabold">{{ bdt($line->line_total) }}</span>
                                <form method="post" action="{{ route('cart.remove') }}">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $line->variant->id }}">
                                    <button type="submit" aria-label="Remove item" class="text-muted-foreground hover:text-destructive">{!! icon('trash-2', 'size-4') !!}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <aside class="card-soft h-fit p-5">
            @if ($threshold > 0)
                <div class="mb-5">
                    <p class="text-sm font-semibold">{{ $remaining > 0 ? 'Add '.bdt($remaining).' more to get free delivery.' : 'You have free delivery!' }}</p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-accent" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) min($subtotal / $threshold * 100, 100) }}">
                        <div class="h-full rounded-full bg-primary" style="width: {{ min($subtotal / $threshold * 100, 100) }}%"></div>
                    </div>
                </div>
            @endif

            @if ($coupon)
                <form method="post" action="{{ route('cart.coupon') }}" class="flex items-center justify-between rounded-xl bg-cream px-3 py-2 text-sm">
                    @csrf
                    <input type="hidden" name="remove" value="1">
                    <span class="flex items-center gap-2 font-bold text-success">{!! icon('ticket', 'size-4') !!} {{ $coupon->code }}</span>
                    <button type="submit" class="text-xs font-semibold text-muted-foreground underline">Remove</button>
                </form>
            @else
                <form method="post" action="{{ route('cart.coupon') }}" class="flex gap-2">
                    @csrf
                    <input name="code" value="{{ old('code') }}" placeholder="Coupon code" class="input h-10" aria-label="Coupon code">
                    <button type="submit" class="btn btn-outline h-10">Apply</button>
                </form>
            @endif

            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between"><dt>{{ __('ui.subtotal') }}</dt><dd class="font-bold">{{ bdt($subtotal) }}</dd></div>
                @if ($discount > 0)
                    <div class="flex justify-between text-success"><dt>{{ __('ui.discount') }} ({{ $coupon->code }})</dt><dd class="font-bold">-{{ bdt($discount) }}</dd></div>
                @endif
                <div class="flex justify-between text-muted-foreground"><dt>{{ __('ui.delivery') }}</dt><dd>Calculated at checkout</dd></div>
                <div class="flex justify-between border-t border-border pt-3 text-base"><dt class="font-extrabold">{{ __('ui.total') }}</dt><dd class="font-extrabold text-primary">{{ bdt($subtotal - $discount) }}</dd></div>
            </dl>

            <a href="{{ route('checkout') }}" class="btn btn-primary mt-5 h-12 w-full text-base">{{ __('ui.checkout') }}</a>
            <a href="{{ route('shop') }}" class="btn btn-ghost mt-2 h-10 w-full">{{ __('ui.continueShopping') }}</a>
        </aside>
    </div>
</div>
@endif
@endsection

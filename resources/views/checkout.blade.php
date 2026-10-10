@extends('layouts.app')

@section('content')
@php
    $cfg = [
        'zone' => old('delivery_zone', 'inside_dhaka'),
        'payment' => old('payment_method', array_key_first($paymentOptions) ?? 'cod'),
        'subtotal' => $totals['subtotal'],
        'discount' => $totals['discount'],
        'inside' => (float) $delivery['inside_dhaka'],
        'outside' => (float) $delivery['outside_dhaka'],
        'threshold' => (float) $delivery['free_delivery_threshold'],
    ];
@endphp
<div class="container-page pb-12">
    <x-breadcrumbs :items="[['Home', '/'], ['Cart', '/cart'], ['Checkout', null]]" />
    <x-section-title title="Checkout" as="h1" />

    @if ($errors->any())
        <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive" role="alert">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('checkout.store') }}" class="grid gap-6 lg:grid-cols-[1.6fr_1fr]" x-data="checkout(@js($cfg))" x-on:submit="$refs.submit.disabled = true; $refs.submit.textContent = 'Placing order…'">
        @csrf
        <div class="space-y-6">
            <section class="card-soft p-5">
                <h2 class="text-lg font-extrabold">Customer Information</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="customer_name">Full Name</label>
                        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required maxlength="120" autocomplete="name" class="input">
                    </div>
                    <div>
                        <label class="label" for="phone">Phone Number</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" class="input">
                    </div>
                    <div>
                        <label class="label" for="area">Area</label>
                        <input id="area" name="area" value="{{ old('area') }}" maxlength="120" class="input">
                    </div>
                    <div>
                        <label class="label" for="city">City / District</label>
                        <input id="city" name="city" value="{{ old('city') }}" maxlength="120" autocomplete="address-level2" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="address">Full Address</label>
                        <textarea id="address" name="address" required maxlength="500" autocomplete="street-address" class="textarea">{{ old('address') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="notes">Order note (optional)</label>
                        <textarea id="notes" name="notes" maxlength="500" class="textarea">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="card-soft p-5">
                <h2 class="text-lg font-extrabold">Delivery</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (['inside_dhaka' => ['Inside Dhaka', $delivery['inside_dhaka']], 'outside_dhaka' => ['Outside Dhaka', $delivery['outside_dhaka']]] as $key => [$label, $charge])
                        <label class="flex h-12 cursor-pointer items-center justify-between rounded-lg border px-4 text-sm font-semibold" :class="zone === '{{ $key }}' ? 'border-primary bg-cream' : 'border-border'">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="delivery_zone" value="{{ $key }}" x-model="zone" class="accent-[var(--color-primary)]" @checked($cfg['zone'] === $key)> {{ $label }}
                            </span>
                            <span>{{ bdt($charge) }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="card-soft p-5">
                <h2 class="text-lg font-extrabold">Payment</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($paymentOptions as $key => $label)
                        <label class="flex h-12 cursor-pointer items-center gap-2 rounded-lg border px-4 text-sm font-semibold" :class="payment === '{{ $key }}' ? 'border-primary bg-cream' : 'border-border'">
                            <input type="radio" name="payment_method" value="{{ $key }}" x-model="payment" class="accent-[var(--color-primary)]" @checked($cfg['payment'] === $key)> {{ $label }}
                        </label>
                    @endforeach
                </div>
                @if (!empty($payments['bkash_number']))
                    <p class="mt-3 text-sm text-muted-foreground" x-show="payment === 'bkash'" x-cloak>Send payment to bKash <strong>{{ $payments['bkash_number'] }}</strong> and share the transaction ID on WhatsApp.</p>
                @endif
                @if (!empty($payments['nagad_number']))
                    <p class="mt-3 text-sm text-muted-foreground" x-show="payment === 'nagad'" x-cloak>Send payment to Nagad <strong>{{ $payments['nagad_number'] }}</strong> and share the transaction ID on WhatsApp.</p>
                @endif
            </section>
        </div>

        <aside class="card-soft h-fit p-5 lg:sticky lg:top-20">
            <h2 class="text-lg font-extrabold">Order Summary</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($lines as $line)
                    <li class="flex justify-between gap-3">
                        <span>{{ $line->product->name }} <span class="text-muted-foreground">({{ $line->variant->weight_label }}) × {{ $line->quantity }}</span></span>
                        <span class="font-bold">{{ bdt($line->line_total) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-2 border-t border-border pt-4 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-bold">{{ bdt($totals['subtotal']) }}</dd></div>
                @if ($totals['discount'] > 0)
                    <div class="flex justify-between text-success"><dt>Discount ({{ $totals['coupon']->code }})</dt><dd class="font-bold">-{{ bdt($totals['discount']) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt>Delivery charge</dt><dd class="font-bold" x-text="deliveryCharge === 0 ? 'Free' : bdt(deliveryCharge)">{{ $totals['free_delivery'] ? 'Free' : bdt($totals['delivery_charge']) }}</dd></div>
                <div class="flex justify-between border-t border-border pt-3 text-base"><dt class="font-extrabold">Total</dt><dd class="font-extrabold text-primary" x-text="bdt(total)">{{ bdt($totals['total']) }}</dd></div>
            </dl>
            <button type="submit" x-ref="submit" class="btn btn-primary mt-5 h-12 w-full text-base">{{ __('ui.placeOrder') }}</button>
            <p class="mt-3 text-center text-xs text-muted-foreground">By placing this order you agree to our <a href="{{ route('terms') }}" class="underline">terms</a>.</p>
        </aside>
    </form>
</div>
@endsection

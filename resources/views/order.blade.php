@extends('layouts.app')

@section('content')
<div class="container-page max-w-3xl py-10">
    <div class="card-soft p-6 text-center">
        {!! icon('circle-check', 'mx-auto size-14 text-success') !!}
        <h1 class="mt-4 text-2xl font-extrabold">Thank you, {{ $order->customer_name }}!</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Your order <span class="font-bold text-primary">{{ $order->order_number }}</span> was placed on {{ $order->created_at->timezone('Asia/Dhaka')->format('j M Y') }}. We will call you shortly to confirm.
        </p>
    </div>

    <div class="card-soft mt-6 p-6">
        <h2 class="text-lg font-extrabold">Order Summary</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-3">
                    <span>{{ $item->product_name }} <span class="text-muted-foreground">({{ $item->variant_label }}) × {{ $item->quantity }}</span></span>
                    <span class="font-bold">{{ bdt($item->line_total) }}</span>
                </li>
            @endforeach
        </ul>
        <dl class="mt-4 space-y-2 border-t border-border pt-4 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ bdt($order->subtotal) }}</dd></div>
            @if ($order->discount > 0)
                <div class="flex justify-between text-success"><dt>Discount</dt><dd>-{{ bdt($order->discount) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt>Delivery ({{ $order->zoneLabel() }})</dt><dd>{{ $order->delivery_charge == 0 ? 'Free' : bdt($order->delivery_charge) }}</dd></div>
            <div class="flex justify-between border-t border-border pt-3 text-base font-extrabold"><dt>Total</dt><dd class="text-primary">{{ bdt($order->total) }}</dd></div>
        </dl>
        <div class="mt-4 grid gap-1 text-sm text-muted-foreground">
            <p>Payment: {{ $order->paymentLabel() }}</p>
            <p>Delivery to: {{ $order->fullAddress() }}</p>
            <p>Estimated delivery: {{ $order->delivery_zone === 'inside_dhaka' ? '1–2 working days' : '2–4 working days' }}</p>
            <p>Status: <span class="font-semibold capitalize text-foreground">{{ $order->status }}</span></p>
        </div>
        @php $payments = site_settings('payments'); @endphp
        @if (in_array($order->payment_method, ['bkash', 'nagad']) && !empty($payments[$order->payment_method.'_number']))
            <p class="mt-4 rounded-xl bg-cream p-3 text-sm">Send {{ bdt($order->total) }} to {{ $order->paymentLabel() }} <strong>{{ $payments[$order->payment_method.'_number'] }}</strong> with reference <strong>{{ $order->order_number }}</strong>, then share the transaction ID on WhatsApp.</p>
        @endif
    </div>

    <div class="mt-6 flex flex-wrap justify-center gap-3">
        @if ($waHref)
            <a href="{{ $waHref }}" target="_blank" rel="noopener" class="btn btn-primary h-11">{!! icon('message-circle') !!} Share order on WhatsApp</a>
        @endif
        <a href="{{ route('shop') }}" class="btn btn-outline h-11">{{ __('ui.continueShopping') }}</a>
    </div>
</div>
@endsection

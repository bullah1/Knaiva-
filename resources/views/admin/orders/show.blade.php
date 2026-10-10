@extends('admin.layout')
@section('title', 'Order '.$order->order_number)
@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost h-10">{!! icon('arrow-left') !!} All orders</a>
    <button onclick="window.print()" class="btn btn-outline h-10">Print</button>
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-[1fr_320px]">
    <div class="card-soft p-5">
        <h2 class="font-extrabold">Items</h2>
        <ul class="mt-3 divide-y divide-border text-sm">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-3 py-2">
                    <span>
                        @if ($item->product_slug)<a href="{{ route('products.show', $item->product_slug) }}" target="_blank" class="font-semibold hover:text-primary">{{ $item->product_name }}</a>@else{{ $item->product_name }}@endif
                        <span class="text-muted-foreground">({{ $item->variant_label }}) × {{ $item->quantity }} @ {{ bdt($item->unit_price) }}</span>
                    </span>
                    <span class="font-bold">{{ bdt($item->line_total) }}</span>
                </li>
            @endforeach
        </ul>
        <dl class="mt-3 space-y-1 border-t border-border pt-3 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ bdt($order->subtotal) }}</dd></div>
            @if ($order->discount > 0)<div class="flex justify-between text-success"><dt>Discount ({{ $order->coupon_code }})</dt><dd>-{{ bdt($order->discount) }}</dd></div>@endif
            <div class="flex justify-between"><dt>Delivery ({{ $order->zoneLabel() }})</dt><dd>{{ bdt($order->delivery_charge) }}</dd></div>
            <div class="flex justify-between text-base font-extrabold"><dt>Total</dt><dd class="text-primary">{{ bdt($order->total) }}</dd></div>
        </dl>
    </div>

    <div class="space-y-6">
        <div class="card-soft space-y-2 p-5 text-sm">
            <h2 class="font-extrabold">Customer</h2>
            <p class="font-semibold">{{ $order->customer_name }}</p>
            <p><a href="tel:{{ $order->phone }}" class="text-primary">{{ $order->phone }}</a>
                @if ($wa = whatsapp_link(preg_replace('/^0/', '880', preg_replace('/\D/', '', $order->phone))))
                    · <a href="{{ $wa }}" target="_blank" class="text-success">WhatsApp</a>
                @endif
            </p>
            <p>{{ $order->fullAddress() }}</p>
            <p class="text-muted-foreground">Payment: {{ $order->paymentLabel() }}</p>
            @if ($order->notes)<p class="rounded-lg bg-cream p-2">Note: {{ $order->notes }}</p>@endif
            <p class="text-xs text-muted-foreground">Placed {{ $order->created_at->timezone('Asia/Dhaka')->format('j M Y, g:i a') }}</p>
        </div>
        <form method="post" action="{{ route('admin.orders.status', $order) }}" class="card-soft space-y-3 p-5">
            @csrf @method('PATCH')
            <h2 class="font-extrabold">Status</h2>
            <p>@include('admin.partials.status', ['status' => $order->status])</p>
            <select name="status" class="input capitalize">
                @foreach (\App\Models\Order::STATUSES as $s)<option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>@endforeach
            </select>
            <button class="btn btn-primary h-10 w-full">Update status</button>
            <p class="text-xs text-muted-foreground">Cancelling puts the items back into stock.</p>
        </form>
    </div>
</div>
@endsection

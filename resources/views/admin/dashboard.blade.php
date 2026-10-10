@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-2 gap-3 md:grid-cols-3">
    @foreach ($cards as [$label, $value, $ic])
        <div class="card-soft flex items-center gap-3 p-4">
            <span class="flex size-10 items-center justify-center rounded-xl bg-cream text-primary">{!! icon($ic, 'size-5') !!}</span>
            <div><p class="text-xs text-muted-foreground">{{ $label }}</p><p class="text-xl font-extrabold text-primary">{{ $value }}</p></div>
        </div>
    @endforeach
</div>

@if ($pendingReviews)
    <a href="{{ route('admin.reviews.index') }}" class="mt-4 block rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm font-semibold text-amber-900">{{ $pendingReviews }} review(s) waiting for approval →</a>
@endif

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="card-soft p-4">
        <h2 class="mb-3 font-extrabold">Latest orders</h2>
        <ul class="divide-y divide-border text-sm">
            @forelse ($latest as $order)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary hover:underline">{{ $order->order_number }}</a>
                    <span class="text-muted-foreground">{{ $order->customer_name }}</span>
                    <span class="font-bold">{{ bdt($order->total) }}</span>
                    @include('admin.partials.status', ['status' => $order->status])
                </li>
            @empty
                <li class="py-2 text-muted-foreground">No orders yet.</li>
            @endforelse
        </ul>
    </div>
    <div class="card-soft p-4">
        <h2 class="mb-3 font-extrabold">Low stock alerts</h2>
        @if ($lowStock->isEmpty())
            <p class="text-sm text-muted-foreground">All variants are above 5 units.</p>
        @else
            <ul class="divide-y divide-border text-sm">
                @foreach ($lowStock as $v)
                    <li class="flex justify-between gap-3 py-2">
                        <a href="{{ route('admin.products.edit', $v->product_id) }}" class="hover:text-primary">{{ $v->product?->name }} — {{ $v->weight_label }}</a>
                        <span class="font-bold text-destructive">{{ $v->stock }} left</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

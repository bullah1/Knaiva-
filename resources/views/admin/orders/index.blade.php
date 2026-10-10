@extends('admin.layout')
@section('title', 'Orders')

@section('content')
<div class="no-scrollbar -mx-4 mb-4 flex gap-2 overflow-x-auto px-4">
    <a href="{{ route('admin.orders.index', array_filter(['q' => request('q')])) }}" class="chip h-9 {{ !$status ? 'chip-active' : '' }}">All ({{ $counts->sum() }})</a>
    @foreach (\App\Models\Order::STATUSES as $s)
        <a href="{{ route('admin.orders.index', array_filter(['status' => $s, 'q' => request('q')])) }}" class="chip h-9 capitalize {{ $status === $s ? 'chip-active' : '' }}">{{ $s }} ({{ $counts[$s] ?? 0 }})</a>
    @endforeach
</div>
<form class="mb-4 flex max-w-md gap-2">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Order number, name or phone" class="input h-10">
    <button class="btn btn-outline h-10">Search</button>
</form>

<div class="card-soft overflow-x-auto">
    <table class="w-full min-w-[760px] text-sm">
        <thead class="border-b border-border bg-cream text-left text-xs uppercase text-muted-foreground">
            <tr><th class="p-3">Order</th><th class="p-3">Customer</th><th class="p-3">Items</th><th class="p-3">Total</th><th class="p-3">Payment</th><th class="p-3">Status</th></tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse ($orders as $o)
                <tr>
                    <td class="p-3"><a href="{{ route('admin.orders.show', $o) }}" class="font-bold text-primary hover:underline">{{ $o->order_number }}</a><p class="text-xs text-muted-foreground">{{ $o->created_at->timezone('Asia/Dhaka')->format('j M Y, g:i a') }}</p></td>
                    <td class="p-3">{{ $o->customer_name }}<p class="text-xs text-muted-foreground"><a href="tel:{{ $o->phone }}">{{ $o->phone }}</a> · {{ $o->zoneLabel() }}</p></td>
                    <td class="p-3">{{ $o->items_count }}</td>
                    <td class="p-3 font-bold">{{ bdt($o->total) }}</td>
                    <td class="p-3">{{ $o->paymentLabel() }}</td>
                    <td class="p-3">
                        <form method="post" action="{{ route('admin.orders.status', $o) }}">@csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="h-9 rounded-lg border border-border bg-background px-2 text-xs font-semibold capitalize" aria-label="Status for {{ $o->order_number }}">
                                @foreach (\App\Models\Order::STATUSES as $s)<option value="{{ $s }}" @selected($o->status === $s)>{{ $s }}</option>@endforeach
                            </select>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-muted-foreground">No orders in this view.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection

@extends('admin.layout')
@section('title', 'Coupons')

@section('content')
<form method="post" action="{{ route('admin.coupons.store') }}" class="card-soft grid gap-3 p-5 md:grid-cols-3">
    @csrf
    <h2 class="font-extrabold md:col-span-3">New coupon</h2>
    <div><label class="label">Code *</label><input name="code" value="{{ old('code') }}" required class="input uppercase" placeholder="SAVE10"></div>
    <div>
        <label class="label">Type</label>
        <select name="discount_type" class="input">
            <option value="percent" @selected(old('discount_type') === 'percent')>Percent (%)</option>
            <option value="fixed" @selected(old('discount_type') === 'fixed')>Fixed (৳)</option>
        </select>
    </div>
    <div><label class="label">Value *</label><input name="discount_value" type="number" step="0.01" min="0" value="{{ old('discount_value', 10) }}" required class="input"></div>
    <div><label class="label">Min order (৳)</label><input name="min_order_value" type="number" min="0" value="{{ old('min_order_value', 0) }}" class="input"></div>
    <div><label class="label">Expires on</label><input name="expires_at" type="date" value="{{ old('expires_at') }}" class="input"></div>
    <div><label class="label">Usage limit</label><input name="usage_limit" type="number" min="1" value="{{ old('usage_limit') }}" placeholder="Unlimited" class="input"></div>
    <button class="btn btn-primary h-11 md:col-span-3">Create coupon</button>
</form>

<div class="mt-6 space-y-3">
    @forelse ($coupons as $c)
        <div class="card-soft flex flex-wrap items-center justify-between gap-3 p-4">
            <div>
                <p class="font-extrabold text-primary">{{ $c->code }}</p>
                <p class="text-xs text-muted-foreground">
                    {{ $c->discount_type === 'percent' ? rtrim(rtrim(number_format($c->discount_value, 2), '0'), '.').'%' : bdt($c->discount_value) }}
                    · min {{ bdt($c->min_order_value) }} · used {{ $c->used_count }}{{ $c->usage_limit ? '/'.$c->usage_limit : '' }}
                    @if ($c->expires_at) · expires {{ $c->expires_at->timezone('Asia/Dhaka')->format('j M Y') }} @if ($c->expires_at->isPast())<span class="font-bold text-destructive">(expired)</span>@endif @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                @include('admin.partials.toggle', ['action' => route('admin.coupons.toggle', $c), 'on' => $c->is_active, 'label' => 'Active'])
                <form method="post" action="{{ route('admin.coupons.destroy', $c) }}" onsubmit="return confirm('Delete {{ $c->code }}?')">@csrf @method('DELETE')
                    <button class="btn-icon text-destructive" aria-label="Delete">{!! icon('trash-2', 'size-4') !!}</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-sm text-muted-foreground">No coupons yet.</p>
    @endforelse
</div>
@endsection

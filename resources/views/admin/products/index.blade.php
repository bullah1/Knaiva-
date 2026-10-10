@extends('admin.layout')
@section('title', 'Products')
@section('actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary h-10">{!! icon('plus') !!} Add product</a>
@endsection

@section('content')
<form class="mb-4 flex max-w-md gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or SKU" class="input h-10">
    <button class="btn btn-outline h-10">Search</button>
</form>

<div class="card-soft overflow-x-auto">
    <table class="w-full min-w-[720px] text-sm">
        <thead class="border-b border-border bg-cream text-left text-xs uppercase text-muted-foreground">
            <tr><th class="p-3">Product</th><th class="p-3">Category</th><th class="p-3">Sizes / price</th><th class="p-3">Stock</th><th class="p-3">Status</th><th class="p-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse ($products as $p)
                <tr>
                    <td class="p-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $p->mainImage() }}" alt="" class="size-12 rounded-lg object-cover">
                            <div>
                                <a href="{{ route('admin.products.edit', $p) }}" class="font-bold text-primary hover:underline">{{ $p->name }}</a>
                                <p class="text-xs text-muted-foreground">/{{ $p->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-3">{{ $p->category?->name ?? '—' }}</td>
                    <td class="p-3">{{ $p->variants->count() }} · {{ $p->variants->isNotEmpty() ? bdt($p->minPrice()) : '—' }}</td>
                    <td class="p-3">
                        @php $stock = $p->variants->sum('stock'); @endphp
                        <span class="{{ $stock <= 5 ? 'font-bold text-destructive' : '' }}">{{ $stock }}</span>
                    </td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-1">
                            @include('admin.partials.toggle', ['action' => route('admin.products.toggle', $p), 'field' => 'is_active', 'on' => $p->is_active, 'label' => 'Active'])
                            @include('admin.partials.toggle', ['action' => route('admin.products.toggle', $p), 'field' => 'is_popular', 'on' => $p->is_popular, 'label' => 'Popular'])
                        </div>
                    </td>
                    <td class="p-3 text-right">
                        <div class="flex justify-end gap-1">
                            <a href="{{ $p->url() }}" target="_blank" class="btn-icon" aria-label="View">{!! icon('eye', 'size-4') !!}</a>
                            <a href="{{ route('admin.products.edit', $p) }}" class="btn-icon" aria-label="Edit">{!! icon('pencil', 'size-4') !!}</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-muted-foreground">No products yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection

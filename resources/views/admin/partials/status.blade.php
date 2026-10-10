@php
    $colors = ['pending' => 'bg-amber-100 text-amber-800', 'confirmed' => 'bg-sky-100 text-sky-800', 'processing' => 'bg-indigo-100 text-indigo-800', 'shipped' => 'bg-violet-100 text-violet-800', 'delivered' => 'bg-emerald-100 text-emerald-800', 'cancelled' => 'bg-zinc-200 text-zinc-700'];
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold capitalize {{ $colors[$status] ?? 'bg-zinc-100' }}">{{ $status }}</span>

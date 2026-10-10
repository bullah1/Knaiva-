@extends('pages._policy', ['heading' => 'Frequently Asked Questions'])

@section('page')
<div class="divide-y divide-border border-y border-border">
    @foreach ($faqs as [$q, $a])
        <details class="group py-1" @if($loop->first) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-left text-base font-bold [&::-webkit-details-marker]:hidden">
                {{ $q }}
                <span class="shrink-0 text-primary transition-transform group-open:rotate-45">{!! icon('plus', 'size-5') !!}</span>
            </summary>
            <p class="pb-4 text-sm text-muted-foreground">{{ $a }}</p>
        </details>
    @endforeach
</div>
@endsection

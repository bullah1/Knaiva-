@props(['items'])
<nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 py-4 text-xs text-muted-foreground">
    @foreach ($items as $i => [$label, $url])
        <span class="flex items-center gap-1">
            @if ($url)
                <a href="{{ url($url) }}" class="hover:text-primary">{{ $label }}</a>
            @else
                <span class="font-semibold text-foreground" aria-current="page">{{ $label }}</span>
            @endif
            @if (!$loop->last){!! icon('chevron-right', 'size-3') !!}@endif
        </span>
    @endforeach
</nav>

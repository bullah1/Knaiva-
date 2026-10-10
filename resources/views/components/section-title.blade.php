@props(['title', 'subtitle' => null, 'as' => 'h2', 'major' => true])
<div class="mb-8 text-center">
    <{{ $as }} class="text-2xl md:text-3xl {{ $major ? 'heading-major' : '' }}">{{ $title }}</{{ $as }}>
    <div class="mt-3 flex items-center justify-center gap-3" aria-hidden="true">
        <span class="h-px w-16 border-t border-dotted border-primary/50 md:w-24"></span>
        {!! icon('paw-print', 'size-5 text-primary') !!}
        <span class="h-px w-16 border-t border-dotted border-primary/50 md:w-24"></span>
    </div>
    @if ($subtitle)
        <p class="mx-auto mt-3 max-w-xl text-sm text-muted-foreground">{{ $subtitle }}</p>
    @endif
</div>

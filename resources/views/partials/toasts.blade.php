<div class="pointer-events-none fixed inset-x-0 top-20 z-[60] flex flex-col items-center gap-2 px-4" aria-live="polite">
    @foreach (['success' => 'border-success/30 text-success', 'error' => 'border-destructive/30 text-destructive'] as $type => $classes)
        @if (session($type))
            <div data-toast class="pointer-events-auto flex max-w-md items-center gap-3 rounded-xl border bg-background px-4 py-3 text-sm font-semibold shadow-card {{ $classes }}">
                {!! icon($type === 'success' ? 'circle-check' : 'x', 'size-5 shrink-0') !!}
                <span class="text-foreground">{{ session($type) }}</span>
                @if ($type === 'success' && session('cart_added'))
                    <a href="{{ route('cart') }}" class="ml-1 whitespace-nowrap text-primary underline">View cart</a>
                @endif
            </div>
        @endif
    @endforeach
</div>

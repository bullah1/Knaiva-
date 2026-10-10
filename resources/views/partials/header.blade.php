@php
    $nav = [['home', 'home'], ['shop', 'shop'], ['blog.index', 'blog'], ['about', 'about'], ['contact', 'contact']];
@endphp
<header class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur" x-data="{ search: false, menu: false }" @keydown.escape.window="menu = false; search = false">
    <div class="container-page flex h-16 items-center gap-2">
        <a href="{{ route('home') }}" class="mr-auto flex items-center gap-2 text-primary" aria-label="Kaniva home">
            {!! icon('paw-print', 'size-7') !!}
            <span class="text-lg font-extrabold tracking-tight md:text-xl">KANIVA</span>
        </a>

        <nav class="hidden items-center gap-6 lg:flex" aria-label="Main">
            @foreach ($nav as [$routeName, $key])
                @php $active = $routeName === 'home' ? request()->routeIs('home') : request()->routeIs(str_replace('.index', '', $routeName).'*'); @endphp
                <a href="{{ route($routeName) }}" class="text-sm font-semibold transition-colors hover:text-primary {{ $active ? 'text-primary' : 'text-foreground' }}" @if($active) aria-current="page" @endif>{{ __('ui.'.$key) }}</a>
            @endforeach
        </nav>

        <div class="ml-2 flex items-center gap-1">
            <div class="flex items-center rounded-full border border-border p-0.5 text-xs font-bold">
                <a href="{{ route('lang', 'en') }}" rel="nofollow" class="rounded-full px-2 py-1 {{ lang() === 'en' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">EN</a>
                <a href="{{ route('lang', 'bn') }}" rel="nofollow" class="rounded-full px-2 py-1 {{ lang() === 'bn' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground' }}">বাংলা</a>
            </div>

            <button type="button" class="btn-icon" aria-label="{{ __('ui.search') }}" @click="search = !search; $nextTick(() => search && $refs.q.focus())">
                <span x-show="!search">{!! icon('search') !!}</span>
                <span x-show="search" x-cloak>{!! icon('x') !!}</span>
            </button>

            <a href="{{ route('cart') }}" class="btn-icon" aria-label="{{ __('ui.cart') }}">
                {!! icon('shopping-cart') !!}
                @if ($cartCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground">{{ $cartCount }}</span>
                @endif
            </a>

            <button type="button" class="btn-icon lg:hidden" aria-label="Menu" @click="menu = true">{!! icon('menu') !!}</button>
        </div>
    </div>

    <div x-show="search" x-cloak x-transition.opacity class="border-t border-border bg-cream">
        <form action="{{ route('shop') }}" method="get" class="container-page flex gap-2 py-3" role="search">
            <input x-ref="q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search') }}" class="input h-10" aria-label="{{ __('ui.search') }}">
            <button type="submit" class="btn btn-primary h-10">{{ __('ui.search') }}</button>
        </form>
    </div>

    {{-- Mobile menu --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/40" @click="menu = false" x-transition.opacity></div>
        <div class="absolute inset-y-0 right-0 w-72 bg-background shadow-xl" x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="flex items-center justify-between px-4 pt-4">
                <span class="text-lg font-extrabold text-primary">KANIVA</span>
                <button type="button" class="btn-icon" aria-label="Close menu" @click="menu = false">{!! icon('x') !!}</button>
            </div>
            <nav class="mt-4 flex flex-col px-2" aria-label="Mobile">
                @foreach (array_merge($nav, [['faq', 'faq'], ['cart', 'cart']]) as [$routeName, $key])
                    <a href="{{ route($routeName) }}" class="rounded-lg px-4 py-3 text-base font-semibold text-foreground hover:bg-cream">{{ __('ui.'.$key) }}</a>
                @endforeach
            </nav>
        </div>
    </div>
</header>

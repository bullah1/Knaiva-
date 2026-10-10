@extends('layouts.app')

@push('preload')
    @if ($banner?->image_url)
        <link rel="preload" as="image" href="{{ upload_url($banner->image_url) }}" fetchpriority="high">
    @else
        <link rel="preload" as="image" href="{{ asset('images/hero-kaniva-1600.webp') }}" imagesrcset="{{ asset('images/hero-kaniva-800.webp') }} 800w, {{ asset('images/hero-kaniva-1600.webp') }} 1600w" imagesizes="(min-width: 768px) 50vw, 100vw" fetchpriority="high">
    @endif
@endpush

@section('content')
    {{-- Hero --}}
    <section class="bg-cream">
        <div class="container-page grid items-center gap-6 py-10 md:grid-cols-2 md:py-16">
            <div>
                <h1 class="heading-major text-3xl leading-tight md:text-5xl">
                    {{ $banner ? pick($banner->title, $banner->title_bn) : 'PREMIUM NUTRITION FOR BANGLADESHI PETS' }}
                </h1>
                <p class="mt-4 max-w-md text-base text-muted-foreground">
                    {{ $banner ? pick($banner->subtitle, $banner->subtitle_bn) : 'Grain free, lab tested Kaniva food delivered all over Bangladesh.' }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ url($banner?->cta_link ?: '/shop') }}" class="btn btn-primary h-12 px-8 text-base">{{ $banner?->cta_label && lang() === 'en' ? $banner->cta_label : __('ui.shopNow') }}</a>
                    <a href="{{ route('about') }}" class="btn btn-outline h-12 px-8 text-base">{{ __('ui.about') }}</a>
                </div>
            </div>
            @if ($banner?->image_url)
                <img src="{{ upload_url($banner->image_url) }}" alt="{{ $banner->title }}" width="1600" height="1008" fetchpriority="high" class="w-full rounded-2xl object-cover shadow-card">
            @else
                <img src="{{ asset('images/hero-kaniva-1600.webp') }}" srcset="{{ asset('images/hero-kaniva-800.webp') }} 800w, {{ asset('images/hero-kaniva-1600.webp') }} 1600w" sizes="(min-width: 768px) 50vw, 100vw"
                     alt="Kaniva premium cat food pack with a healthy cat" width="1600" height="1008" fetchpriority="high" class="w-full rounded-2xl object-cover shadow-card">
            @endif
        </div>
    </section>

    {{-- Categories --}}
    @if ($categories->isNotEmpty())
        <section class="section">
            <div class="container-page">
                <x-section-title :title="__('ui.categories')" />
                <div class="flex flex-wrap justify-center gap-6">
                    @foreach ($categories as $category)
                        <a href="{{ $category->url() }}" class="group flex w-28 flex-col items-center gap-2 text-center">
                            <span class="flex size-24 items-center justify-center rounded-full bg-cream shadow-soft transition-transform group-hover:scale-105">
                                @if ($img = $category->imageUrl())
                                    <img src="{{ $img }}" alt="" loading="lazy" width="96" height="96" class="size-24 rounded-full bg-white object-contain p-1">
                                @else
                                    {!! icon('paw-print', 'size-9 text-primary') !!}
                                @endif
                            </span>
                            <span class="text-sm font-bold text-primary">{{ pick($category->name, $category->name_bn) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Popular products --}}
    <section class="section-cream">
        <div class="container-page">
            <x-section-title :title="__('ui.popularProducts')" />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($popular as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <a href="{{ route('shop') }}" class="btn btn-outline h-11 px-8">{{ __('ui.shop') }}</a>
            </div>
        </div>
    </section>

    {{-- Why choose us --}}
    @if ($trustPoints->isNotEmpty())
        <section class="section">
            <div class="container-page">
                <x-section-title :title="__('ui.whyChooseUs')" />
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($trustPoints as $point)
                        <div class="card-soft flex flex-col items-center gap-2 p-5 text-center">
                            {!! icon($point->icon, 'size-8 text-primary') !!}
                            <h3 class="text-sm font-extrabold md:text-base">{{ pick($point->title, $point->title_bn) }}</h3>
                            <p class="text-xs text-muted-foreground">{{ pick($point->description, $point->description_bn) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Certificates --}}
    @if ($certificates->isNotEmpty())
        <section class="section-cream">
            <div class="container-page">
                <x-section-title :title="__('ui.certificates')" />
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($certificates as $certificate)
                        <div class="card-soft flex h-24 items-center justify-center p-4 text-center text-sm font-bold text-primary">
                            @if ($certificate->image_url)
                                <img src="{{ upload_url($certificate->image_url) }}" alt="{{ $certificate->title }}" loading="lazy" class="max-h-16">
                            @else
                                {{ $certificate->title }}
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Reviews --}}
    @if ($reviews->isNotEmpty())
        <section class="section">
            <div class="container-page">
                <x-section-title :title="__('ui.reviews')" />
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ($reviews as $review)
                        <figure class="card-soft p-5">
                            <x-stars :rating="$review->rating" />
                            <blockquote class="mt-3 text-sm">{{ $review->body }}</blockquote>
                            <figcaption class="mt-3 flex items-center gap-2 text-sm font-bold text-primary">
                                @if ($review->image_url)
                                    <img src="{{ upload_url($review->image_url) }}" alt="" loading="lazy" class="size-8 rounded-full object-cover">
                                @endif
                                {{ $review->customer_name }}
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Latest pet tips --}}
    @if ($posts->isNotEmpty())
        <section class="section-cream">
            <div class="container-page">
                <x-section-title :title="__('ui.latestTips')" />
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ($posts as $post)
                        @include('blog.card', ['post' => $post, 'heading' => 'h3'])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

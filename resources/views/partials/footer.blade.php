@php $contact = site_settings('contact'); @endphp
<footer class="mt-12 border-t border-border bg-cream">
    <div class="container-page grid gap-8 py-12 md:grid-cols-4">
        <div>
            <div class="flex items-center gap-2 text-primary">
                {!! icon('paw-print', 'size-6') !!}
                <span class="text-lg font-extrabold">KANIVA BANGLADESH</span>
            </div>
            <p class="mt-3 text-sm text-muted-foreground">Premium, grain free nutrition for Bangladeshi cats and dogs. Nationwide delivery.</p>
            <div class="mt-4 space-y-2 text-sm">
                @if ($contact['address'])
                    <p class="flex items-start gap-2">{!! icon('map-pin', 'mt-0.5 size-4 shrink-0 text-primary') !!} {{ $contact['address'] }}</p>
                @endif
                @if ($contact['phone'])
                    <a href="tel:{{ $contact['phone'] }}" class="flex items-center gap-2 text-primary hover:underline">{!! icon('phone', 'size-4') !!} {{ $contact['phone'] }}</a>
                @endif
                @if ($contact['email'])
                    <a href="mailto:{{ $contact['email'] }}" class="flex items-center gap-2 text-primary hover:underline">{!! icon('mail', 'size-4') !!} {{ $contact['email'] }}</a>
                @endif
            </div>
        </div>

        <div>
            <h2 class="text-sm font-extrabold uppercase">Quick Links</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ([['home', 'Home'], ['shop', 'Shop'], ['blog.index', 'Pet Tips'], ['about', 'About Us'], ['contact', 'Contact'], ['faq', 'FAQ']] as [$r, $label])
                    <li><a href="{{ route($r) }}" class="hover:text-primary">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-extrabold uppercase">Shop</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach (\App\Models\Category::active()->get(['id', 'slug', 'name', 'name_bn']) as $cat)
                    <li><a href="{{ route('category', $cat->slug) }}" class="hover:text-primary">{{ pick($cat->name, $cat->name_bn) }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-extrabold uppercase">Policies</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('privacy') }}" class="hover:text-primary">Privacy Policy</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-primary">Terms &amp; Conditions</a></li>
                <li><a href="{{ route('returns') }}" class="hover:text-primary">Return &amp; Refund Policy</a></li>
            </ul>
            <div class="mt-4 flex gap-3">
                @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $key => $label)
                    @if ($contact[$key])
                        <a href="{{ $contact[$key] }}" aria-label="{{ $label }}" class="text-primary hover:opacity-80" target="_blank" rel="noopener">{!! icon($key) !!}</a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <div class="border-t border-border py-4 text-center text-xs text-muted-foreground">
        © {{ date('Y') }} Kaniva Bangladesh. All rights reserved.
    </div>
</footer>

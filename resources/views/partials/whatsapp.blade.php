@php $wa = whatsapp_link(site_settings('contact.whatsapp'), 'Hi Kaniva, I need help with an order.'); @endphp
@if ($wa && !request()->routeIs('products.show'))
    <a href="{{ $wa }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
       class="fixed bottom-5 right-4 z-40 flex size-13 items-center justify-center rounded-full bg-success text-success-foreground shadow-card transition-transform hover:scale-105">
        {!! icon('message-circle', 'size-6') !!}
    </a>
@endif

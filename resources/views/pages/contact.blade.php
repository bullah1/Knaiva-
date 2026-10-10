@extends('pages._policy', ['heading' => 'Contact Us', 'subtitle' => 'We reply fastest on WhatsApp, 10am–8pm daily.'])

@section('page')
@php
    $contact = site_settings('contact');
    $wa = whatsapp_link($contact['whatsapp'], 'Hi Kaniva, I have a question.');
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    @if ($contact['phone'])
        <a href="tel:{{ $contact['phone'] }}" class="card-soft flex items-center gap-3 p-5">
            {!! icon('phone', 'size-6 text-primary') !!}
            <span><span class="block text-xs text-muted-foreground">Call us</span><span class="font-bold text-primary">{{ $contact['phone'] }}</span></span>
        </a>
    @endif
    @if ($contact['email'])
        <a href="mailto:{{ $contact['email'] }}" class="card-soft flex items-center gap-3 p-5">
            {!! icon('mail', 'size-6 text-primary') !!}
            <span><span class="block text-xs text-muted-foreground">Email</span><span class="font-bold text-primary">{{ $contact['email'] }}</span></span>
        </a>
    @endif
    @if ($contact['address'])
        <div class="card-soft flex items-center gap-3 p-5">
            {!! icon('map-pin', 'size-6 text-primary') !!}
            <span><span class="block text-xs text-muted-foreground">Address</span><span class="font-bold">{{ $contact['address'] }}</span></span>
        </div>
    @endif
    @if ($wa)
        <a href="{{ $wa }}" target="_blank" rel="noopener" class="card-soft flex items-center gap-3 p-5">
            {!! icon('message-circle', 'size-6 text-success') !!}
            <span><span class="block text-xs text-muted-foreground">WhatsApp</span><span class="font-bold text-primary">Chat with support</span></span>
        </a>
    @endif
</div>
@if ($wa)
    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-primary mt-6 h-12 w-full sm:w-auto sm:px-10">Message us on WhatsApp</a>
@endif
@endsection

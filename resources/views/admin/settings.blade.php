@extends('admin.layout')
@section('title', 'Settings')

@section('content')
<form method="post" action="{{ route('admin.settings.update') }}" class="space-y-6">
    @csrf @method('PUT')
    <section class="card-soft grid gap-4 p-5 md:grid-cols-3">
        <h2 class="font-extrabold md:col-span-3">Delivery charges (৳)</h2>
        <div><label class="label">Inside Dhaka</label><input name="delivery[inside_dhaka]" type="number" min="0" value="{{ old('delivery.inside_dhaka', $s['delivery']['inside_dhaka']) }}" class="input"></div>
        <div><label class="label">Outside Dhaka</label><input name="delivery[outside_dhaka]" type="number" min="0" value="{{ old('delivery.outside_dhaka', $s['delivery']['outside_dhaka']) }}" class="input"></div>
        <div><label class="label">Free delivery from <span class="font-normal text-muted-foreground">(0 = never)</span></label><input name="delivery[free_delivery_threshold]" type="number" min="0" value="{{ old('delivery.free_delivery_threshold', $s['delivery']['free_delivery_threshold']) }}" class="input"></div>
    </section>

    <section class="card-soft grid gap-4 p-5 md:grid-cols-2">
        <h2 class="font-extrabold md:col-span-2">Payment methods</h2>
        <div class="flex flex-wrap gap-4 md:col-span-2">
            @foreach (\App\Models\Order::PAYMENT_LABELS as $key => $label)
                <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="payments[{{ $key }}]" value="1" @checked($s['payments'][$key] ?? false) class="size-4 accent-[var(--color-primary)]"> {{ $label }}</label>
            @endforeach
        </div>
        <div><label class="label">bKash number</label><input name="payments[bkash_number]" value="{{ old('payments.bkash_number', $s['payments']['bkash_number']) }}" class="input"></div>
        <div><label class="label">Nagad number</label><input name="payments[nagad_number]" value="{{ old('payments.nagad_number', $s['payments']['nagad_number']) }}" class="input"></div>
    </section>

    <section class="card-soft grid gap-4 p-5 md:grid-cols-2">
        <h2 class="font-extrabold md:col-span-2">Contact</h2>
        <div><label class="label">Phone</label><input name="contact[phone]" value="{{ old('contact.phone', $s['contact']['phone']) }}" placeholder="+8801XXXXXXXXX" class="input"></div>
        <div><label class="label">WhatsApp number <span class="font-normal text-muted-foreground">(with 880)</span></label><input name="contact[whatsapp]" value="{{ old('contact.whatsapp', $s['contact']['whatsapp']) }}" placeholder="8801XXXXXXXXX" class="input"></div>
        <div><label class="label">Email</label><input name="contact[email]" type="email" value="{{ old('contact.email', $s['contact']['email']) }}" class="input"></div>
        <div><label class="label">Address</label><input name="contact[address]" value="{{ old('contact.address', $s['contact']['address']) }}" class="input"></div>
        <div><label class="label">Facebook URL</label><input name="contact[facebook]" type="url" value="{{ old('contact.facebook', $s['contact']['facebook']) }}" class="input"></div>
        <div><label class="label">Instagram URL</label><input name="contact[instagram]" type="url" value="{{ old('contact.instagram', $s['contact']['instagram']) }}" class="input"></div>
        <div><label class="label">YouTube URL</label><input name="contact[youtube]" type="url" value="{{ old('contact.youtube', $s['contact']['youtube']) }}" class="input"></div>
    </section>

    <section class="card-soft grid gap-4 p-5 md:grid-cols-2">
        <h2 class="font-extrabold md:col-span-2">SEO defaults</h2>
        <div><label class="label">Site name</label><input name="seo[site_name]" value="{{ old('seo.site_name', $s['seo']['site_name']) }}" class="input"></div>
        <div><label class="label">Canonical domain</label><input name="seo[canonical_base]" type="url" value="{{ old('seo.canonical_base', $s['seo']['canonical_base']) }}" placeholder="https://kanivacatfood.com" class="input"><p class="mt-1 text-xs text-muted-foreground">Used in canonical tags, sitemap and structured data.</p></div>
        <div class="md:col-span-2"><label class="label">Default title</label><input name="seo[default_title]" value="{{ old('seo.default_title', $s['seo']['default_title']) }}" class="input"></div>
        <div class="md:col-span-2"><label class="label">Default description</label><textarea name="seo[default_description]" rows="2" class="textarea">{{ old('seo.default_description', $s['seo']['default_description']) }}</textarea></div>
    </section>

    <button class="btn btn-primary h-11 px-10">Save settings</button>
</form>

<form method="post" action="{{ route('admin.account.password') }}" class="card-soft mt-10 grid max-w-xl gap-4 p-5">
    @csrf @method('PUT')
    <h2 class="font-extrabold">Change your password</h2>
    <div><label class="label">Current password</label><input name="current_password" type="password" required autocomplete="current-password" class="input"></div>
    <div><label class="label">New password (8+ characters)</label><input name="password" type="password" required autocomplete="new-password" class="input"></div>
    <div><label class="label">Confirm new password</label><input name="password_confirmation" type="password" required autocomplete="new-password" class="input"></div>
    <button class="btn btn-outline h-10">Change password</button>
</form>
@endsection

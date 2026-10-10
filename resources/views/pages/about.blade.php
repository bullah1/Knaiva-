@extends('pages._policy', ['heading' => 'About Kaniva'])

@section('page')
<div class="prose-content">
    <p>Kaniva Bangladesh exists for one reason: pet parents here deserve the same quality of nutrition that is available anywhere else in the world, at a price that makes sense in Bangladesh.</p>
    <h2>What we stand for</h2>
    <p>Every Kaniva recipe starts with real meat, stays grain free where it matters, and is lab tested batch by batch. We publish full ingredient lists and feeding guides so you always know what is in the bowl.</p>
    <h2>Delivery across Bangladesh</h2>
    <p>We deliver inside Dhaka in one to two working days and across the rest of the country in two to four. Pay with cash on delivery, bKash, Nagad or card.</p>
    <h2>Talk to us</h2>
    <p>Not sure which food suits your cat or dog? Message us on WhatsApp and we will help you pick the right recipe and portion size.</p>
</div>
<a href="{{ route('shop') }}" class="btn btn-primary mt-6 h-11 px-8">Shop Kaniva</a>
@endsection

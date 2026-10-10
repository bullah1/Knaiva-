@extends('layouts.app')

@section('content')
<div class="container-page max-w-md py-16">
    <div class="card-soft p-6">
        <h1 class="text-xl font-extrabold">Order {{ $orderNumber }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">Enter the phone number used for this order to see its details.</p>
        <form method="post" action="{{ route('order.verify', $orderNumber) }}" class="mt-5 space-y-3">
            @csrf
            <label class="label" for="phone">Phone Number</label>
            <input id="phone" name="phone" required inputmode="tel" placeholder="01XXXXXXXXX" class="input">
            <button type="submit" class="btn btn-primary h-11 w-full">View order</button>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container-page max-w-3xl pb-12">
    <x-breadcrumbs :items="[['Home', '/'], [$heading, null]]" />
    <x-section-title :title="$heading" as="h1" :subtitle="$subtitle ?? null" />
    @yield('page')
</div>
@endsection

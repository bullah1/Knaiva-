@extends('layouts.app', ['seo' => ['title' => 'No admin access — Kaniva', 'robots' => 'noindex']])

@section('content')
<div class="container-page max-w-md py-16 text-center">
    <h1 class="text-xl font-extrabold text-primary">No admin access</h1>
    <p class="mt-2 text-sm text-muted-foreground">This account is signed in but does not have the admin role yet. Ask the site owner to grant it.</p>
    <form method="post" action="{{ route('logout') }}" class="mt-6">@csrf<button class="btn btn-primary h-11">Sign out</button></form>
</div>
@endsection

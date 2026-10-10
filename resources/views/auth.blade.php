@extends('layouts.app')

@section('content')
<div class="container-page flex max-w-md flex-col justify-center py-16">
    <div class="card-soft p-6">
        <h1 class="text-xl font-extrabold text-primary">Staff Sign In</h1>
        <p class="mt-1 text-sm text-muted-foreground">Kaniva admin panel access only.</p>
        <form method="post" action="{{ route('login') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="input">
                @error('email')<p class="mt-1 text-xs font-semibold text-destructive">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="input">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="accent-[var(--color-primary)]"> Keep me signed in</label>
            <button type="submit" class="btn btn-primary h-11 w-full">Sign In</button>
        </form>
    </div>
</div>
@endsection

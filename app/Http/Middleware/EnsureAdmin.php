<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }
        if (! $user->isAdmin()) {
            return response()->view('admin.no-access', [], 403);
        }

        return $next($request);
    }
}

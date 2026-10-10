<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $lang = $request->session()->get('lang', $request->cookie('kaniva_lang', 'en'));
        app()->setLocale($lang === 'bn' ? 'bn' : 'en');

        return $next($request);
    }
}

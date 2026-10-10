<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class]);
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
        $middleware->encryptCookies(except: ['kaniva_lang']);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// On Vercel only /tmp is writable (see api/index.php).
if (getenv('VERCEL')) {
    $app->useStoragePath('/tmp/kaniva/storage');
}

return $app;

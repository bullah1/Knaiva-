<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (getenv('VERCEL')) {
            if (getenv('VERCEL_URL')) {
                URL::forceScheme('https');
            }
            $this->prepareVercelPreview();
        }
    }

    /** Builds the throw-away SQLite database for the Vercel preview on a cold start. */
    private function prepareVercelPreview(): void
    {
        $db = config('database.connections.sqlite.database');
        if (! $db || file_exists($db)) {
            return;
        }
        $lock = fopen($db.'.lock', 'c');
        flock($lock, LOCK_EX);
        if (! file_exists($db)) {
            touch($db.'.building');
            config(['database.connections.sqlite.database' => $db.'.building']);
            app('db')->purge('sqlite');
            Artisan::call('migrate', ['--seed' => true, '--force' => true]);
            User::updateOrCreate(['email' => 'admin@kaniva.local'], ['name' => 'Admin', 'password' => 'admin12345', 'role' => 'admin']);
            app('db')->purge('sqlite');
            rename($db.'.building', $db);
            config(['database.connections.sqlite.database' => $db]);
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

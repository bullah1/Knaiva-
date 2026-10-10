<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

/*
 * Create or update an admin account:
 *   php artisan kaniva:admin you@example.com
 */
Artisan::command('kaniva:admin {email} {--name=Admin} {--password=}', function (string $email) {
    $password = $this->option('password') ?: $this->secret('Password (8+ characters)');
    if (strlen((string) $password) < 8) {
        $this->error('Password must be at least 8 characters.');

        return 1;
    }
    $user = User::updateOrCreate(['email' => $email], ['name' => $this->option('name'), 'password' => $password, 'role' => 'admin']);
    $this->info("Admin ready: {$user->email} — sign in at ".url('/auth'));
})->purpose('Create or update a Kaniva admin user');

/*
 * Import / update products from a Shopify product CSV:
 *   php artisan kaniva:import database/data/kaniva_products.csv --download-images
 */
Artisan::command('kaniva:import {path} {--download-images : Save product photos into public/uploads instead of linking to the old site}', function (string $path) {
    if (! is_file($path)) {
        $this->error("File not found: {$path}");

        return 1;
    }
    $importer = new \App\Support\ShopifyImporter;
    $count = $importer->import($path, (bool) $this->option('download-images'));
    foreach ($importer->log as $line) {
        $this->line("  - {$line}");
    }
    \Illuminate\Support\Facades\Cache::flush();
    $this->info("Imported {$count} products.");
})->purpose('Import products from a Shopify CSV export');

<?php

/*
 * Vercel entry point (preview only).
 * Vercel's file system is read-only except /tmp, so storage, caches and the
 * SQLite database live in /tmp. The database is rebuilt from the seeder on
 * every cold start, which means orders and admin changes are not permanent.
 */
$tmp = '/tmp/kaniva';
foreach (['storage/framework/views', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/logs', 'bootstrap'] as $dir) {
    if (! is_dir("{$tmp}/{$dir}")) {
        @mkdir("{$tmp}/{$dir}", 0777, true);
    }
}

require __DIR__.'/../public/index.php';

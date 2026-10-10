<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Stores uploads directly under public/uploads so they work on shared hosting
 * without `php artisan storage:link`.
 */
class Uploads
{
    public const RULE = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:4096'];

    public static function store(?UploadedFile $file, string $folder): ?string
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }
        $dir = public_path("uploads/{$folder}");
        File::ensureDirectoryExists($dir);
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';
        $name = Str::limit($name, 60, '').'-'.Str::lower(Str::random(6)).'.'.$file->guessExtension();
        $file->move($dir, $name);

        return "uploads/{$folder}/{$name}";
    }

    /** Delete a previously uploaded file (never touches bundled images). */
    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/') && ! str_contains($path, '..')) {
            File::delete(public_path($path));
        }
    }
}

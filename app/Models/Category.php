<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Category picture, or the first product photo in it. */
    public function imageUrl(): ?string
    {
        if ($this->image_url) {
            return upload_url($this->image_url);
        }
        $product = $this->products()->where('is_active', true)->orderByDesc('is_popular')->first();

        return $product?->mainImage();
    }

    public function url(): string
    {
        return route('category', $this->slug);
    }

    public function scopeActive($query)
    {
        // Storefront only shows categories that have something to buy.
        return $query->where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order');
    }
}

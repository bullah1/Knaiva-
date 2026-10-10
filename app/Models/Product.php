<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'tags' => 'array',
        'quick_info' => 'array',
        'rating' => 'float',
        'review_count' => 'integer',
        'sold_count' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Bundled artwork for the seeded products; admin uploads always win. */
    private const FALLBACK_IMAGES = [
        'kaniva-adult-cat-food-chicken' => 'images/products/adult-cat-chicken.jpg',
        'kaniva-kitten-food-salmon' => 'images/products/kitten-salmon.jpg',
        'kaniva-wet-cat-food-tuna' => 'images/products/wet-tuna.jpg',
        'kaniva-adult-dog-food-lamb' => 'images/products/adult-dog-lamb.jpg',
        'kaniva-multivitamin-supplement' => 'images/products/multivitamin.jpg',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** @return list<string> absolute URLs */
    public function imageUrls(): array
    {
        $images = array_values(array_filter($this->images ?? [], fn ($i) => is_string($i) && $i !== ''));
        if (! $images) {
            $images = [self::FALLBACK_IMAGES[$this->slug] ?? 'images/products/adult-cat-chicken.jpg'];
        }

        return array_map(fn ($path) => str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/')), $images);
    }

    public function mainImage(): string
    {
        return $this->imageUrls()[0];
    }

    public function firstVariant(): ?ProductVariant
    {
        return $this->variants->first();
    }

    public function minPrice(): float
    {
        return (float) ($this->variants->min('price') ?? 0);
    }

    public function inStock(): bool
    {
        return $this->variants->contains(fn ($v) => $v->stock > 0);
    }

    public function discountPercent(): int
    {
        $first = $this->firstVariant();
        $mrp = $first?->mrp ? (float) $first->mrp : null;
        $min = $this->minPrice();

        return $mrp && $mrp > $min ? (int) round(($mrp - $min) / $mrp * 100) : 0;
    }

    public function hasOrders(): bool
    {
        return OrderItem::where('product_id', $this->id)->exists();
    }

    public function url(): string
    {
        return route('products.show', $this->slug);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['mrp' => 'float', 'price' => 'float', 'stock' => 'integer', 'sort_order' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

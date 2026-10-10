<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['unit_price' => 'float', 'line_total' => 'float', 'quantity' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

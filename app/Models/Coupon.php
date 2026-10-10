<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'discount_value' => 'float',
        'min_order_value' => 'float',
        'expires_at' => 'datetime',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function findByCode(?string $code): ?self
    {
        $code = trim((string) $code);

        return $code === '' ? null : static::whereRaw('UPPER(code) = ?', [mb_strtoupper($code)])->first();
    }

    /** Returns null when usable, otherwise a customer-facing reason. */
    public function problemFor(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'Coupon not found';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'This coupon has expired';
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'This coupon has reached its limit';
        }
        if ($subtotal < $this->min_order_value) {
            return 'Minimum order '.bdt($this->min_order_value).' required';
        }

        return null;
    }

    public function discountFor(float $subtotal): float
    {
        if ($subtotal < $this->min_order_value) {
            return 0;
        }
        $amount = $this->discount_type === 'percent' ? $subtotal * $this->discount_value / 100 : $this->discount_value;

        return min(round($amount), $subtotal);
    }
}

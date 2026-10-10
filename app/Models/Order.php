<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_LABELS = [
        'cod' => 'Cash on Delivery',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'card' => 'Card Payment',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'delivery_charge' => 'float',
        'total' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function zoneLabel(): string
    {
        return $this->delivery_zone === 'inside_dhaka' ? 'Inside Dhaka' : 'Outside Dhaka';
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_LABELS[$this->payment_method] ?? $this->payment_method;
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->area, $this->city])->filter()->implode(', ');
    }

    /** KAN-YYYYMMDD-0001, counted per day in Dhaka time. Call inside a transaction. */
    public static function nextNumber(): string
    {
        $day = now('Asia/Dhaka')->format('Ymd');
        $prefix = "KAN-{$day}-";
        $last = static::where('order_number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('order_number')->value('order_number');
        $n = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}

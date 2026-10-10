<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Session cart. Only variant ids and quantities are stored; prices, names and
 * stock are always re-read from the database so they can never be tampered with.
 */
class Cart
{
    private const KEY = 'cart.items';

    private const COUPON = 'cart.coupon';

    public const MAX_QTY = 50;

    /** @return array<int,int> variantId => qty */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(int $variantId, int $qty = 1): void
    {
        $items = $this->raw();
        $items[$variantId] = min(($items[$variantId] ?? 0) + max(1, $qty), self::MAX_QTY);
        session([self::KEY => $items]);
    }

    public function set(int $variantId, int $qty): void
    {
        $items = $this->raw();
        if ($qty <= 0) {
            unset($items[$variantId]);
        } else {
            $items[$variantId] = min($qty, self::MAX_QTY);
        }
        session([self::KEY => $items]);
    }

    public function remove(int $variantId): void
    {
        $this->set($variantId, 0);
    }

    public function clear(): void
    {
        session()->forget([self::KEY, self::COUPON]);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * @return Collection<int, object{variant: ProductVariant, product: \App\Models\Product, quantity: int, price: float, line_total: float, image: string}>
     */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }
        $variants = ProductVariant::with('product')->whereIn('id', array_keys($raw))->get()->keyBy('id');

        // Drop lines whose variant or product disappeared.
        $clean = array_intersect_key($raw, $variants->filter(fn ($v) => $v->product && $v->product->is_active)->all());
        if (count($clean) !== count($raw)) {
            session([self::KEY => $clean]);
        }

        return collect($clean)->map(function ($qty, $id) use ($variants) {
            $v = $variants[$id];

            return (object) [
                'variant' => $v,
                'product' => $v->product,
                'quantity' => (int) $qty,
                'price' => (float) $v->price,
                'line_total' => (float) $v->price * $qty,
                'image' => $v->product->mainImage(),
            ];
        })->values();
    }

    public function subtotal(?Collection $lines = null): float
    {
        return ($lines ?? $this->lines())->sum('line_total');
    }

    public function couponCode(): ?string
    {
        return session(self::COUPON);
    }

    public function setCoupon(?string $code): void
    {
        $code ? session([self::COUPON => $code]) : session()->forget(self::COUPON);
    }

    /** Valid coupon for the current subtotal, or null. */
    public function coupon(float $subtotal): ?Coupon
    {
        $coupon = Coupon::findByCode($this->couponCode());

        return $coupon && $coupon->problemFor($subtotal) === null ? $coupon : null;
    }

    /**
     * Server-side totals, used both for display and when placing the order.
     *
     * @return array{subtotal: float, discount: float, coupon: ?Coupon, delivery_charge: float, free_delivery: bool, total: float}
     */
    public static function totals(float $subtotal, ?Coupon $coupon, string $zone): array
    {
        $delivery = site_settings('delivery');
        $discount = $coupon ? $coupon->discountFor($subtotal) : 0;
        $base = $zone === 'outside_dhaka' ? (float) $delivery['outside_dhaka'] : (float) $delivery['inside_dhaka'];
        $threshold = (float) $delivery['free_delivery_threshold'];
        $free = $threshold > 0 && ($subtotal - $discount) >= $threshold;
        $charge = $free ? 0 : $base;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon' => $coupon,
            'delivery_charge' => $charge,
            'free_delivery' => $free,
            'total' => $subtotal - $discount + $charge,
        ];
    }
}

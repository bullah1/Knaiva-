<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function show()
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }
        $subtotal = $this->cart->subtotal($lines);
        $coupon = $this->cart->coupon($subtotal);
        $zone = old('delivery_zone', 'inside_dhaka');

        return view('checkout', [
            'lines' => $lines,
            'totals' => Cart::totals($subtotal, $coupon, $zone),
            'delivery' => site_settings('delivery'),
            'payments' => site_settings('payments'),
            'paymentOptions' => $this->paymentOptions(),
            'seo' => ['title' => 'Checkout — Kaniva Bangladesh', 'description' => 'Complete your Kaniva pet food order with cash on delivery, bKash, Nagad or card.', 'canonical' => site_origin().'/checkout', 'robots' => 'noindex, follow'],
        ]);
    }

    private function paymentOptions(): array
    {
        $payments = site_settings('payments');

        return array_filter(Order::PAYMENT_LABELS, fn ($label, $key) => ! empty($payments[$key]), ARRAY_FILTER_USE_BOTH);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'area' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'delivery_zone' => ['required', Rule::in(['inside_dhaka', 'outside_dhaka'])],
            'payment_method' => ['required', Rule::in(array_keys($this->paymentOptions()))],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => 'Enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
            'payment_method.in' => 'That payment method is not available right now.',
        ]);
        $data['phone'] = preg_replace('/\s+/', '', $data['phone']);

        $raw = $this->cart->raw();
        if (! $raw) {
            return redirect()->route('cart');
        }

        $order = DB::transaction(function () use ($raw, $data) {
            $variants = ProductVariant::with('product')->whereIn('id', array_keys($raw))->lockForUpdate()->get()->keyBy('id');
            $lines = [];
            foreach ($raw as $variantId => $qty) {
                $v = $variants[$variantId] ?? null;
                if (! $v || ! $v->product?->is_active) {
                    throw ValidationException::withMessages(['cart' => 'A product in your cart is no longer available.']);
                }
                if ($v->stock < $qty) {
                    throw ValidationException::withMessages(['cart' => "{$v->product->name} ({$v->weight_label}) has only {$v->stock} left in stock."]);
                }
                $lines[] = [$v, (int) $qty];
            }

            $subtotal = array_sum(array_map(fn ($l) => $l[0]->price * $l[1], $lines));
            $coupon = Coupon::findByCode($this->cart->couponCode());
            if ($coupon && $coupon->problemFor($subtotal) !== null) {
                $coupon = null;
            }
            $totals = Cart::totals($subtotal, $coupon, $data['delivery_zone']);

            $order = Order::create($data + [
                'order_number' => Order::nextNumber(),
                'coupon_code' => $coupon?->code,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'delivery_charge' => $totals['delivery_charge'],
                'total' => $totals['total'],
                'status' => 'pending',
            ]);

            foreach ($lines as [$v, $qty]) {
                $order->items()->create([
                    'product_id' => $v->product_id,
                    'product_name' => $v->product->name,
                    'product_slug' => $v->product->slug,
                    'variant_label' => $v->weight_label,
                    'image_url' => $v->product->images[0] ?? null,
                    'unit_price' => $v->price,
                    'quantity' => $qty,
                    'line_total' => $v->price * $qty,
                ]);
                $v->decrement('stock', $qty);
                $v->product->increment('sold_count', $qty);
            }
            $coupon?->increment('used_count');

            return $order;
        });

        $this->cart->clear();
        $request->session()->push('my_orders', $order->order_number);

        return redirect()->route('order.show', $order->order_number);
    }
}

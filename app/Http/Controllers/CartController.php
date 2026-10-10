<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index()
    {
        $lines = $this->cart->lines();
        $subtotal = $this->cart->subtotal($lines);
        $coupon = $this->cart->coupon($subtotal);
        $threshold = (float) site_settings('delivery.free_delivery_threshold');

        return view('cart', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $coupon ? $coupon->discountFor($subtotal) : 0,
            'threshold' => $threshold,
            'remaining' => max($threshold - $subtotal, 0),
            'seo' => ['title' => 'Your Cart — Kaniva Bangladesh', 'description' => 'Review your Kaniva pet food order, apply a coupon and check delivery charges before checkout.', 'canonical' => site_origin().'/cart', 'robots' => 'noindex, follow'],
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.Cart::MAX_QTY],
        ]);
        $variant = ProductVariant::with('product')->find($data['variant_id']);
        if (! $variant || ! $variant->product?->is_active) {
            return back()->with('error', 'That product is no longer available.');
        }
        if ($variant->stock <= 0) {
            return back()->with('error', "{$variant->product->name} ({$variant->weight_label}) is out of stock.");
        }
        $this->cart->add($variant->id, $data['quantity'] ?? 1);

        $message = "{$variant->product->name} ({$variant->weight_label}) added to cart";
        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout');
        }

        return back()->with('success', $message)->with('cart_added', true);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['variant_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:0', 'max:'.Cart::MAX_QTY]]);
        $this->cart->set($data['variant_id'], $data['quantity']);

        return redirect()->route('cart');
    }

    public function remove(Request $request)
    {
        $this->cart->remove((int) $request->input('variant_id'));

        return redirect()->route('cart')->with('success', 'Item removed from cart');
    }

    public function coupon(Request $request)
    {
        if ($request->boolean('remove')) {
            $this->cart->setCoupon(null);

            return back()->with('success', 'Coupon removed');
        }
        $code = trim((string) $request->validate(['code' => ['required', 'string', 'max:40']])['code']);
        $subtotal = $this->cart->subtotal();
        $coupon = Coupon::findByCode($code);
        $problem = $coupon ? $coupon->problemFor($subtotal) : 'Coupon not found';
        if ($problem) {
            $this->cart->setCoupon(null);

            return back()->with('error', $problem)->withInput();
        }
        $this->cart->setCoupon($coupon->code);

        return back()->with('success', 'Coupon applied — you saved '.bdt($coupon->discountFor($subtotal)));
    }
}

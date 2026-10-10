<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->first();
        $seo = ['title' => "Order {$orderNumber} — Kaniva Bangladesh", 'description' => 'Your Kaniva order confirmation and delivery details.', 'robots' => 'noindex, nofollow'];

        if (! $order) {
            return response()->view('errors.404', ['message' => 'Order not found', 'seo' => $seo], 404);
        }

        // Order numbers are sequential, so personal details are only shown to the
        // browser that placed the order, or after confirming the phone number.
        if (! in_array($order->order_number, $request->session()->get('my_orders', []), true)) {
            return view('order-verify', ['orderNumber' => $order->order_number, 'seo' => $seo]);
        }

        $summary = implode("\n", array_merge(
            ["Order {$order->order_number}", "Name: {$order->customer_name}"],
            $order->items->map(fn ($i) => "{$i->product_name} ({$i->variant_label}) × {$i->quantity}")->all(),
            ['Total: '.bdt($order->total), 'Address: '.$order->fullAddress()],
        ));

        return view('order', [
            'order' => $order,
            'waHref' => whatsapp_link(site_settings('contact.whatsapp'), $summary),
            'seo' => $seo,
        ]);
    }

    public function verify(Request $request, string $orderNumber)
    {
        $phone = preg_replace('/\D/', '', (string) $request->validate(['phone' => ['required', 'string', 'max:20']])['phone']);
        $order = Order::where('order_number', $orderNumber)->first();
        $stored = $order ? preg_replace('/\D/', '', $order->phone) : null;

        if ($order && $phone !== '' && strlen($phone) >= 10 && str_ends_with($stored, substr($phone, -10))) {
            $request->session()->push('my_orders', $order->order_number);

            return redirect()->route('order.show', $orderNumber);
        }

        return back()->with('error', 'That phone number does not match this order.');
    }
}

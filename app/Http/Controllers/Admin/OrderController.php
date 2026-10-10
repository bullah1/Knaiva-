<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->status, Order::STATUSES, true) ? $request->status : null;
        $orders = Order::withCount('items')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('order_number', 'like', "%{$s}%")->orWhere('customer_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->latest()->paginate(25)->withQueryString();
        $counts = Order::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        return view('admin.orders.index', compact('orders', 'status', 'counts'));
    }

    public function show(Order $order)
    {
        $order->load('items');

        return view('admin.orders.show', compact('order'));
    }

    public function status(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', Rule::in(Order::STATUSES)]]);

        DB::transaction(function () use ($order, $data) {
            // Put stock back when an order is cancelled, and take it again if un-cancelled.
            if ($order->status !== 'cancelled' && $data['status'] === 'cancelled') {
                $this->adjustStock($order, +1);
            } elseif ($order->status === 'cancelled' && $data['status'] !== 'cancelled') {
                $this->adjustStock($order, -1);
            }
            $order->update(['status' => $data['status']]);
        });

        return back()->with('success', "Order {$order->order_number} marked {$data['status']}");
    }

    private function adjustStock(Order $order, int $sign): void
    {
        $greatest = DB::getDriverName() === 'sqlite' ? 'MAX' : 'GREATEST';
        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }
            DB::table('product_variants')
                ->where('product_id', $item->product_id)
                ->where('weight_label', $item->variant_label)
                ->update(['stock' => DB::raw($greatest.'(stock + '.($sign * $item->quantity).', 0)')]);
        }
    }
}

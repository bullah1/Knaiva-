<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $valid = Order::where('status', '!=', 'cancelled');

        return view('admin.dashboard', [
            'cards' => [
                ['Total orders', Order::count(), 'receipt'],
                ['Pending orders', Order::where('status', 'pending')->count(), 'package'],
                ['Revenue', bdt((clone $valid)->sum('total')), 'tag'],
                ['Today', bdt((clone $valid)->where('created_at', '>=', now('Asia/Dhaka')->startOfDay()->utc())->sum('total')), 'award'],
                ['Products', Product::count(), 'folder'],
                ['Blog posts', BlogPost::count(), 'newspaper'],
            ],
            'pendingReviews' => Review::where('is_approved', false)->count(),
            'lowStock' => ProductVariant::with('product:id,name')->where('stock', '<=', 5)->orderBy('stock')->get(),
            'latest' => Order::latest()->take(8)->get(),
        ]);
    }
}

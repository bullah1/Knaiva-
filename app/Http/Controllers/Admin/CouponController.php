<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons', ['coupons' => Coupon::latest()->get()]);
    }

    public function store(Request $request)
    {
        $request->merge(['code' => Str::upper(trim((string) $request->code))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:coupons,code'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01', $request->discount_type === 'percent' ? 'max:100' : 'max:1000000'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);
        $data['min_order_value'] = $data['min_order_value'] ?? 0;
        if (! empty($data['expires_at'])) {
            $data['expires_at'] = \Carbon\Carbon::parse($data['expires_at'], 'Asia/Dhaka')->endOfDay()->utc();
        }
        Coupon::create($data);

        return back()->with('success', 'Coupon created');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('success', 'Coupon updated');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('success', 'Coupon deleted');
    }
}

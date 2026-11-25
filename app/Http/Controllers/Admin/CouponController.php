<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get()->map(fn (Coupon $coupon) => [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'min_amount' => $coupon->min_amount !== null ? (float) $coupon->min_amount : null,
            'max_uses' => $coupon->max_uses,
            'used_count' => (int) $coupon->used_count,
            'expires_at_date' => $coupon->expires_at?->format('Y-m-d'),
            'expires_label' => $coupon->expires_at ? $coupon->expires_at->format('M d, Y') : 'Never',
            'discount_label' => $coupon->type === 'percent'
                ? $coupon->value.'%'
                : '$'.number_format((float) $coupon->value, 2),
            'is_valid' => $coupon->isValid(),
        ])->values()->all();

        return \App\Support\InertiaPage::react('Admin/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $data['code'] = strtoupper($data['code']);
        Coupon::create($data);
        return back()->with('success', 'Coupon created.');
    }
    public function edit(Coupon $coupon)
    {
        return \App\Support\InertiaPage::react('Admin/Coupons/Edit', [
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
                'min_amount' => $coupon->min_amount !== null ? (float) $coupon->min_amount : null,
                'max_uses' => $coupon->max_uses,
                'expires_at' => optional($coupon->expires_at)->format('Y-m-d'),
            ],
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code,' . $coupon->id,
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        $data['code'] = strtoupper($data['code']);
        $coupon->update($data);
        return redirect()->route('admin.coupons')->with('success', 'Coupon updated.');
    }
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return back()->with('success', 'Coupon deleted.');
    }
}

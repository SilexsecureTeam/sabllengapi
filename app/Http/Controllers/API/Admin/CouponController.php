<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
   public function index()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $coupons = Coupon::orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Coupons retrieved successfully.',
            'coupons' => $coupons,
        ]);
    }

    // ✅ Create new coupon
    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'promotion_name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'expires_at' => 'required|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'product_ids' => 'required|array', // new
        'product_ids.*' => 'exists:products,id', // ensure valid IDs
        ]);

        $coupon = Coupon::create(array_merge($validated, [
            'times_used' => 0,
            'is_active' => true,
        ]));

    $coupon->products()->attach($validated['product_ids']);

        return response()->json([
            'message' => 'Coupon created successfully.',
            'coupon' => $coupon->load('products:id,name'),
        ], 201);
    }

    // ✅ Update coupon
    public function update(Request $request, $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'promotion_name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $coupon->id,
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'expires_at' => 'sometimes|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $coupon->update($validated);

        return response()->json([
            'message' => 'Coupon updated successfully.',
            'coupon' => $coupon,
        ]);
    }

    // ✅ Delete coupon
    public function destroy($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted successfully.']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Coupon;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        // Validate input
        $validated = $request->validate([
            'shipping_address' => 'required|string|max:255',
            'delivery_fee' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Retrieve cart
        $cart = $user
            ? Cart::with('items.product')->where('user_id', $user->id)->first()
            : Cart::with('items.product')->where('session_id', $sessionId)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $cart->updateTotal();
        $subtotal = $cart->total;
        $deliveryFee = $validated['delivery_fee'] ?? 0;

        // Initialize coupon and tax
        $discount = 0;
        $taxRate = $validated['tax_rate'] ?? 0;
        $taxAmount = 0;
        $couponCode = $validated['coupon_code'] ?? null;

        // Apply coupon if available
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();

            if ($coupon && $coupon->isValid()) {
                $discount = $coupon->type === 'percent'
                    ? ($coupon->value / 100) * $subtotal
                    : $coupon->value;

                // Prevent over-discount
                $discount = min($discount, $subtotal);

                // Increment coupon usage
                $coupon->increment('times_used');
            } else {
                return response()->json(['message' => 'Invalid or expired coupon'], 422);
            }
        }

        // Calculate tax
        if ($taxRate > 0) {
            $taxAmount = (($subtotal - $discount) * $taxRate) / 100;
        }

        // Calculate final total
        $total = ($subtotal - $discount) + $deliveryFee + $taxAmount;

        // Create order
        $order_reference = 'SAB-' . strtoupper(Str::random(10));

        $order = Order::create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $sessionId,
            'order_reference' => $order_reference,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'coupon_code' => $couponCode,
            'discount_amount' => $discount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'shipping_address' => $validated['shipping_address'],
            'status' => 'pending',
        ]);

        // Attach cart items as order items
        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'customization_id' => $item->customization_id,
                'color' => $item->color,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]);
        }

        // Clear cart
        $cart->items()->delete();
        $cart->update(['total' => 0]);

        return response()->json([
            'message' => 'Checkout successful. Proceed to payment.',
            'order' => $order->load('items.product.images', 'items.customization'),
        ], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Coupon;
use App\Mail\OrderStatusUpdated;
use App\Models\DeliveryFee;
use Illuminate\Support\Facades\Mail;

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

    public function myOrders(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = Order::with('items.product.images', 'items.customization')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found'], 404);
        }

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

    /**
     * Get a single order by reference
     */
    public function getOrder(Request $request, $orderReference)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::with('items.product.images', 'items.customization')
            ->where('user_id', $user->id)
            ->where('order_reference', $orderReference)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'message' => 'Order retrieved successfully',
            'order' => $order,
        ], 200);
    }

    // public function allOrders(Request $request)
    // {
    //     // Ensure only admin users can access this route
    //     if (!Auth::check() || !Auth::user()->admin) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     // Optional filters for admin dashboard (e.g. ?status=paid)
    //     $query = Order::with([
    //         'user:id,name,email',
    //         'items.product.images',
    //         'items.customization'
    //     ]);

    //     // if ($request->has('status')) {
    //     //     $query->where('status', $request->status);
    //     // }

    //     if ($request->has('payment_status')) {
    //         $query->where('payment_status', $request->payment_status);
    //     }

    //     if ($request->has('search')) {
    //         $search = $request->search;
    //         $query->whereHas('user', function ($q) use ($search) {
    //             $q->where('name', 'like', "%$search%")
    //                 ->orWhere('email', 'like', "%$search%");
    //         });
    //     }

    //     $orders = $query->orderBy('created_at', 'desc')->get();

    //     if ($orders->isEmpty()) {
    //         return response()->json(['message' => 'No orders found'], 404);
    //     }

    //     $data = $orders->map(function ($order) {
    //         return [
    //             'id' => $order->id,
    //             'order_number' => $order->order_number ?? 'N/A',
    //             'status' => ucfirst($order->status),
    //             'payment_status' => ucfirst($order->payment_status),
    //             'total' => number_format($order->total, 2),
    //             'delivery_address' => $order->delivery_address,
    //             'created_at' => $order->created_at->toDateTimeString(),
    //             'user' => [
    //                 'id' => $order->user?->id,
    //                 'name' => $order->user?->name,
    //                 'email' => $order->user?->email,
    //             ],
    //             'items' => $order->items->map(function ($item) {
    //                 return [
    //                     'product_name' => $item->product?->name,
    //                     'quantity' => $item->quantity,
    //                     'price' => number_format($item->price, 2),
    //                     'subtotal' => number_format($item->price * $item->quantity, 2),
    //                     'images' => $item->product?->images?->pluck('url')
    //                         ->map(fn($url) => asset('storage/' . $url)),
    //                     'customization' => $item->customization,
    //                 ];
    //             }),
    //         ];
    //     });

    //     return response()->json([
    //         'message' => 'All orders retrieved successfully',
    //         'count' => $data->count(),
    //         'orders' => $data,
    //     ], 200);
    // }

    // list of orders
    public function allOrders(Request $request)
    {
        // Optional filters: status, payment_status, user_id
        $query = Order::with([
            'user:id,name,email',
            'items.product:id,name'
        ])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // if ($request->filled('date_from')) {
        //     $query->whereDate('created_at', '>=', $request->date_from);
        // }

        // if ($request->filled('date_to')) {
        //     $query->whereDate('created_at', '<=', $request->date_to);
        // }

        // Paginate results (default 15 per page)
        $orders = $query->get();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'data' => $orders,
        ]);
    }

    // admin update status
    public function updateOrderStatus(Request $request, $id)
    {
        // Ensure only admin users can perform this action
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'This User is Unauthorized'
            ], 401);
        }

        // Validate incoming request with fixed status stages
        $validated = $request->validate([
            'order_status' => 'required|string|in:Order Placed,Processing,Packed,Shipped,Out for Delivery,Delivered',
        ]);

        // Find order and include user for email notification
        $order = Order::with('user')->find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        // Update the order_status field
        $order->order_status = $validated['order_status'];
        $order->save();

        // Send email notification to the user
        if ($order->user && $order->user->email) {
            Mail::to($order->user->email)->send(new OrderStatusUpdated($order));
        }

        return response()->json([
            'message' => 'Order status updated and user notified successfully',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_reference ?? 'N/A',
                'order_status' => $order->order_status,
                'updated_at' => $order->updated_at->toDateTimeString(),
            ],
        ], 200);
    }

    // admin view each status
    public function viewOrder($orderReference)
    {
        // Ensure only admin users can access this route
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the order by order_reference and include related data
        $order = Order::with(['user:id,name,email', 'items.product'])
            ->where('order_reference', $orderReference)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'message' => 'Order retrieved successfully',
            'order' => $order
        ], 200);
    }
}

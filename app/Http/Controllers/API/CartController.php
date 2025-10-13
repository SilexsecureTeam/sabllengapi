<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'quantity'         => 'required|integer|min:1',
            'price'            => 'required|numeric|min:0',
            'color'            => 'nullable|string|max:50',
            'customization_id' => 'nullable|exists:customizations,id',
        ]);

        // Ensure user is authenticated
        $userId = Auth::id() ?? $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get or create user cart
        $cart = Cart::firstOrCreate([
            'user_id' => $userId,
        ]);

        // Check if item already exists in cart with same attributes
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $validated['product_id'])
            ->where('color', $validated['color'] ?? null)
            ->where('customization_id', $validated['customization_id'] ?? null)
            ->first();

        if ($item) {
            $item->quantity += $validated['quantity'];
            $item->save();
        } else {
            $item = CartItem::create([
                'cart_id'          => $cart->id,
                'product_id'       => $validated['product_id'],
                'quantity'         => $validated['quantity'],
                'price'            => $validated['price'],
                'color'            => $validated['color'] ?? null,
                'customization_id' => $validated['customization_id'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Item added to cart successfully',
            'data' => $item->load([
                'product.category',
                'product.brand',
                'product.images',
                'customization'
            ])
        ], 201);
    }
    /**
     * Merge guest cart into the authenticated user's cart
     */
    public function mergeCart(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // Get guest session_id from session
        $sessionId = $request->session()->get('cart_session_id');
        if (!$sessionId) {
            return response()->json([
                'message' => 'No guest cart found'
            ], 200);
        }

        // Find guest cart
        $guestCart = Cart::where('session_id', $sessionId)->first();
        if (!$guestCart) {
            return response()->json([
                'message' => 'No guest cart found'
            ], 200);
        }

        // Get or create user cart
        $userCart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        // Merge items
        foreach ($guestCart->items as $guestItem) {
            $existingItem = CartItem::where('cart_id', $userCart->id)
                ->where('product_id', $guestItem->product_id)
                ->where('color', $guestItem->color)
                ->where('customization_id', $guestItem->customization_id)
                ->first();

            if ($existingItem) {
                // Combine quantities if same item exists
                $existingItem->quantity += $guestItem->quantity;
                $existingItem->save();
            } else {
                // Move the guest item to the user's cart
                $guestItem->update([
                    'cart_id' => $userCart->id
                ]);
            }
        }

        // Recalculate total and delete the guest cart
        $userCart->updateTotal();
        $guestCart->delete();

        // Clear session cart ID
        $request->session()->forget('cart_session_id');

        return response()->json([
            'message' => 'Guest cart merged successfully',
            'data'    => $userCart->load('items.customization')
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        // dd($request);
        $validated = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'quantity'         => 'required|integer|min:1',
            'price'            => 'required|numeric|min:0',
            'color'            => 'nullable|string|max:50',
            'customization_id' => 'nullable|exists:customizations,id',
        ]);

        $user = Auth::user();

        // ✅ Use header-based session ID (instead of Laravel session)
        $sessionId = $request->header('X-Cart-Session');

        if (!$user && !$sessionId) {
            // Create a new guest session ID
            $sessionId = Str::uuid()->toString();
        }

        // ✅ Create or find the cart
        $cart = Cart::firstOrCreate(
            $user ? ['user_id' => $user->id] : ['session_id' => $sessionId],
            ['total' => 0]
        );

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
            'cart_session_id' => $sessionId,
            'data' => $item->load([
                'product.category',
                'product.brand',
                'product.images',
                'customization'
            ])
        ], 201)
            ->header('X-Cart-Session', $sessionId);
    }
    /**
     * Merge guest cart into the authenticated user's cart
     */
    public function mergeCart(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // ✅ Get session_id from header (like in addToCart)
        $sessionId = $request->header('X-Cart-Session');

        if (!$sessionId) {
            return response()->json(['message' => 'No session stored'], 200);
        }

        $guestCart = Cart::with('items')->where('session_id', $sessionId)->first();

        if (!$guestCart || $guestCart->items->isEmpty()) {
            return response()->json(['message' => 'No guest cart found'], 200);
        }

        // ✅ Get or create user's cart
        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

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

        return response()->json([
            'message' => 'Guest cart merged successfully',
            'data'    => $userCart->load('items.customization')
        ], 200);
    }

    public function getCart(Request $request)
    {
        // dd($request);
        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        // Find cart based on user or guest session
        $cart = $user
            ? Cart::with('items.product.images', 'items.customization')->where('user_id', $user->id)->first()
            : Cart::with('items.product.images', 'items.customization')->where('session_id', $sessionId)->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart is empty',
                'data' => [],
            ], 200);
        }

        // Optionally update total before returning
        $cart->updateTotal();

        return response()->json([
            'message' => 'Cart retrieved successfully',
            'data' => $cart->load('items.product.category', 'items.product.brand', 'items.customization'),
        ], 200);
    }
    // remove item from cart
    public function removeItem(Request $request, $id)
    {
        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        $cart = $user
            ? Cart::where('user_id', $user->id)->first()
            : Cart::where('session_id', $sessionId)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $item = $cart->items()->where('id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found in cart'], 404);
        }

        $item->delete();

        // Recalculate total
        $cart->updateTotal();

        return response()->json([
            'message' => 'Item removed successfully',
            'data' => $cart->load('items.product', 'items.customization')
        ], 200);
    }

    // increase quantity
    public function updateItem(Request $request, $id)
    {
        // dd($id);
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);


        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        $cart = $user
            ? Cart::where('user_id', $user->id)->first()
            : Cart::where('session_id', $sessionId)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $item = $cart->items()->where('id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found in cart'], 404);
        }

        $item->update(['quantity' => $validated['quantity']]);
        $cart->updateTotal();

        return response()->json([
            'message' => 'Cart item updated successfully',
            'data' => $item->load('product', 'customization')
        ], 200);
    }
}

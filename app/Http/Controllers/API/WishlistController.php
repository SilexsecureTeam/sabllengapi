<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        if (!$user && !$sessionId) {
            $sessionId = Str::uuid()->toString();
        }

        $wishlist = Wishlist::with('product')
            ->when($user, fn($q) => $q->where('user_id', $user->id))
            ->when(!$user, fn($q) => $q->where('session_id', $sessionId))
            ->get();

        return response()
            ->json($wishlist)
            ->header('X-Cart-Session', $sessionId);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        if (!$user && !$sessionId) {
            $sessionId = Str::uuid()->toString();
        }

        $wishlist = Wishlist::firstOrCreate(
            $user
                ? ['user_id' => $user->id, 'product_id' => $validated['product_id']]
                : ['session_id' => $sessionId, 'product_id' => $validated['product_id']]
        );

        return response()
            ->json([
                'message' => 'Product added to wishlist successfully',
                'wishlist' => $wishlist,
                'cart_session_id' => $sessionId,
            ], 201)
            ->header('X-Cart-Session', $sessionId);
    }

    public function destroy(Request $request, $productId)
    {
        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        $wishlist = Wishlist::when($user, fn($q) => $q->where('user_id', $user->id))
            ->when(!$user, fn($q) => $q->where('session_id', $sessionId))
            ->where('product_id', $productId)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'message' => 'Item not found in wishlist'
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'message' => 'Product removed from wishlist'
        ]);
    }

    public function moveToCart(Request $request, $productId, CartController $cartController)
    {
        $user = Auth::user();
        $sessionId = $request->header('X-Cart-Session');

        // 🔍 Find the wishlist item (supports guests if needed)
        $wishlistItem = Wishlist::when($user, fn($q) => $q->where('user_id', $user->id))
            ->when(!$user, fn($q) => $q->where('session_id', $sessionId))
            ->where('product_id', $productId)
            ->first();

        if (!$wishlistItem) {
            return response()->json([
                'message' => 'Item not found in wishlist'
            ], 404);
        }

        // ✅ Fetch the product
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        // 🧩 Prepare the same payload used by addToCart()
        $cartRequest = new Request([
            'product_id'       => $product->id,
            'quantity'         => 1,
            'price'            => $product->price,
            'color'            => $request->get('color'),  // optional
            'customization_id' => $request->get('customization_id'), // optional
        ]);

        // ✅ Copy headers (for session handling)
        $cartRequest->headers->set('X-Cart-Session', $sessionId);

        // 🚀 Call existing addToCart() from CartController
        $cartResponse = $cartController->addToCart($cartRequest);

        // 🗑 Remove from wishlist after successful cart addition
        $wishlistItem->delete();

        // 📦 Return a combined JSON response
        return response()->json([
            'message' => 'Product moved to cart successfully and removed from wishlist',
            'cart_session_id' => $cartRequest->header('X-Cart-Session'),
            'cart_item' => $cartResponse->getData()->data ?? null,
        ]);
    }
}

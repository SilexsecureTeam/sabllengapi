<?php

use App\Http\Controllers\API\Admin\AdminDashController;
use App\Http\Controllers\API\Admin\DeliveryFeeController;
use App\Http\Controllers\API\Admin\LocationController;
use App\Http\Controllers\API\Admin\TaxController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CustomizationController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\Import\ImportInventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StaffController;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Calculation\Category;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('importInventory', [ImportInventoryController::class, 'import']);
Route::post('importProduct', [ImportInventoryController::class, 'importProduct']);

// authentication flow
// routes/api.php
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])
    ->name('password.forgot');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])
    ->name('password.reset');

//show category for users
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

// delivery address
Route::get('/states', [DeliveryFeeController::class, 'getStates']);

// Get LGAs by state
Route::get('/lgas/{state}', [DeliveryFeeController::class, 'getLgas']);

// Get places by state and LGA
Route::get('/places/{state}/{lga}', [DeliveryFeeController::class, 'getPlaces']);

// add to cart
Route::post('/cart/add', [CartController::class, 'addToCart']);
Route::get('/cart', [CartController::class, 'getCart']);
Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
Route::patch('/cart/items/{id}', [CartController::class, 'updateItem']);

// wishlist
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist', [WishlistController::class, 'store']);
Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);
Route::post('/wishlist/{productId}/move-to-cart', [WishlistController::class, 'moveToCart']);

//product
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/product/customized', [ProductController::class, 'customizableProducts']);

// authenticated user
Route::middleware('auth:sanctum')->group(function () {

    // add to cart
    Route::post('/customizations', [CustomizationController::class, 'store']);
    Route::post('/cart/merge', [CartController::class, 'mergeCart']);

    // checkout
    Route::post('/checkout', [OrderController::class, 'checkout']);

    Route::get('/user/transactions', [PaymentController::class, 'userTransactions']);

    Route::get('/locations', [LocationController::class, 'nigeriaLocation']);

    Route::post('/delivery-fee', [DeliveryFeeController::class, 'store']);
    Route::patch('/delivery-fee/{id}', [DeliveryFeeController::class, 'update']);
    Route::delete('/delivery-fee/{id}', [DeliveryFeeController::class, 'destroy']);

    Route::get('/orders', [OrderController::class, 'myOrders']);
    // Get a specific order by order_reference
    Route::get('/orders/{orderReference}', [OrderController::class, 'getOrder']);
});
Route::get('/verify-payment/{reference}/{order_reference}', [PaymentController::class, 'PaystackCallback']);

/**ADMIN ROUTES */
// Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
//     Route::get('/coupons', [CouponController::class, 'index']);
//     Route::post('/coupons', [CouponController::class, 'store']);
//     Route::put('/coupons/{id}', [CouponController::class, 'update']);
//     Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);
// });

// admin login
Route::post('/admin/login', [AdminDashController::class, 'login']);
Route::post('/admin/verify-otp', [AdminDashController::class, 'verifyOtp']);
Route::post('/admin/otp/resend', [AdminDashController::class, 'resendOtp']);

Route::get('/taxes', [TaxController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    //categories
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    //products
    Route::post('/products', [ProductController::class, 'store']);
    Route::patch('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('/admin/orders', [AdminDashController::class, 'listOrders']);

    Route::post('/taxes', [TaxController::class, 'store']);
    Route::patch('/taxes/{id}', [TaxController::class, 'update']);
    Route::delete('/taxes/{id}', [TaxController::class, 'destroy']);

    Route::get('/admin/orders', [OrderController::class, 'allOrders']);
    Route::patch('/admin/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);

    Route::get('/delivery-fees', [DeliveryFeeController::class, 'index']);
});

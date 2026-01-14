<?php

use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\API\Admin\AdminDashController;
use App\Http\Controllers\API\Admin\CouponController;
use App\Http\Controllers\API\Admin\DeliveryFeeController;
use App\Http\Controllers\API\Admin\LocationController;
use App\Http\Controllers\API\Admin\RolesController;
use App\Http\Controllers\API\Admin\TaxController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\GoogleAuthController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CustomizationController;
use App\Http\Controllers\API\EposnowSyncLogController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\StockReportController;
use App\Http\Controllers\API\SubCategoryController;
use App\Http\Controllers\API\SupplierController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\WishlistController;
use App\Http\Controllers\EposnowWebhookController;
use App\Http\Controllers\HeroController;
use App\Http\Controllers\Import\ImportInventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TrustedOrganisationController;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Calculation\Category;
use App\Jobs\SyncEposStockJob;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('importInventory', [ImportInventoryController::class, 'import']);
Route::post('importProduct', [ImportInventoryController::class, 'importProduct']);
Route::post('/webhooks/eposnow/sale', [EposnowWebhookController::class, 'handleSale']);

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
Route::get('/subcategories', [SubCategoryController::class, 'index']);

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

// admin login
Route::post('/admin/login', [AdminDashController::class, 'login']);
Route::post('/admin/verify-otp', [AdminDashController::class, 'verifyOtp']);
Route::post('/admin/otp/resend', [AdminDashController::class, 'resendOtp']);

Route::get('/taxes', [TaxController::class, 'index']);

// google sign in
Route::post('auth/google', [GoogleAuthController::class, 'login']);

// Protected routes using sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [GoogleAuthController::class, 'logout']);
    Route::get('user', function (Request $request) {
        return $request->user();
    });
    // other protected routes
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    // Route::get('/staff', [StaffController::class, 'index']);
    // Route::get('/staff/{id}', [StaffController::class, 'show']);
    // Route::patch('/staff/{id}', [StaffController::class, 'update']);
    // Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
    Route::get('/admin/staff', [AdminDashController::class, 'staffList']);

    // Route::middleware('manager')->group(function () {

    // });


    Route::post('/roles', [RolesController::class, 'store']);
    Route::get('/roles', [RolesController::class, 'index']);   // for dropdown
    Route::patch('/roles/{id}', [RolesController::class, 'update']);
    Route::delete('/roles/{id}', [RolesController::class, 'destroy']);
    //categories
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('/subcategories', [SubCategoryController::class, 'store']);
    Route::get('/subcategories/{id}', [SubCategoryController::class, 'show']);
    Route::patch('/subcategories/{id}', [SubcategoryController::class, 'update']);
    Route::delete('/subcategories/{id}', [SubcategoryController::class, 'destroy']);
    //products
    Route::post('/products', [ProductController::class, 'store']);
    Route::patch('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    Route::post('/taxes', [TaxController::class, 'store']);
    Route::patch('/taxes/{id}', [TaxController::class, 'update']);
    Route::delete('/taxes/{id}', [TaxController::class, 'destroy']);

    Route::get('/admin/orders', [OrderController::class, 'allOrders']);
    Route::get('/admin/orders/{orderReference}', [OrderController::class, 'viewOrder']);
    Route::patch('/admin/orders/status/{id}', [OrderController::class, 'updateOrderStatus']);

    Route::get('/delivery-fees', [DeliveryFeeController::class, 'index']);

    Route::get('/admin/users', [AdminDashController::class, 'index']);
    Route::post('/admin/users', [AdminDashController::class, 'store']);
    Route::patch('/admin/users/{id}', [AdminDashController::class, 'update']);
    Route::delete('/admin/users/{id}', [AdminDashController::class, 'destroy']);

    Route::post('/coupons', [CouponController::class, 'store']);
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::patch('/coupons/{id}', [CouponController::class, 'update']);
    Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);

    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::patch('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

    Route::post('/brand', [BrandController::class, 'store']);
    Route::get('/brand', [BrandController::class, 'index']);
    Route::get('/brand/{id}', [BrandController::class, 'show']);
    Route::patch('/brand/{id}', [BrandController::class, 'update']);
    Route::delete('/brand/{id}', [BrandController::class, 'destroy']);

    Route::post('/tags', [TagController::class, 'store']);
    Route::patch('/tags/{id}', [TagController::class, 'update']);
    Route::delete('/tags/{id}', [TagController::class, 'destroy']);

    Route::get('/stockInventory', [StockReportController::class, 'index'])->name('stock.report.index');
    Route::post('/stockInventory', [StockReportController::class, 'store']);
    Route::get('/stockInventory/{id}', [StockReportController::class, 'show']);
    Route::patch('/stockInventory/{id}', [StockReportController::class, 'update']);
    Route::delete('/stockInventory/{id}', [StockReportController::class, 'destroy']);

    // EPOS
    // fetch logs
    Route::get('/epos/logs', [EposnowSyncLogController::class, 'index']);
    // get single log
    Route::get('/epos/logs/{id}', [EposnowSyncLogController::class, 'show']);
    // retry failed sync
    Route::post('/epos/logs/{id}/retry', [EposnowSyncLogController::class, 'retry']);
    // logs by order
    Route::get('/epos/orders/{orderId}/logs', [EposnowSyncLogController::class, 'logsByOrder']);

    // eposnowsync
    Route::get('/eposnow/logs', [AdminDashController::class, 'eposindex'])
        ->name('admin.eposnow.logs.index');

    Route::get('/eposnow/logs/{id}', [AdminDashController::class, 'show'])
        ->name('admin.eposnow.logs.show');

    Route::post('hero-slides', [HeroController::class, 'store']);
    Route::get('hero-slides/{heroSlide}', [HeroController::class, 'show']);
    Route::patch('hero-slides/{heroSlide}', [HeroController::class, 'update']);
    Route::delete('hero-slides/{heroSlide}', [HeroController::class, 'destroy']);
    Route::post('hero-slides/reorder', [HeroController::class, 'reorder']);

    Route::post('/trusted-organizations', [TrustedOrganisationController::class, 'store']);
    Route::patch('/trusted-organizations/{id}', [TrustedOrganisationController::class, 'update']);
    Route::post('/trusted-organizations/{id}/add-logo', [TrustedOrganisationController::class, 'addLogo']);
    Route::delete('/trusted-organizations/{id}/logo/{logoId}', [TrustedOrganisationController::class, 'deleteLogo']);
    Route::delete('/trusted-organizations/{id}', [TrustedOrganisationController::class, 'destroy']);

    Route::get('/admin/teams', [TeamController::class, 'adminIndex']);  // List all teams
    Route::post('/teams', [TeamController::class, 'store']);      // Create team member
    Route::get('/teams/{id}', [TeamController::class, 'show']);   // Get single team member
    Route::patch('/teams/{id}', [TeamController::class, 'update']); // Update team member
    Route::delete('/teams/{id}', [TeamController::class, 'destroy']); // Delete team member

    Route::post('/about-us', [AboutUsController::class, 'store']);
    // Route::post('/about-us/{id}', [AboutUsController::class, 'update']);
    // Route::delete('/about-us/{id}/founder-image', [AboutUsController::class, 'deleteFounderImage']);
    Route::delete('/about-us', [AboutUsController::class, 'destroy']);
});

// Public route (for frontend display)
Route::get('/trusted-organizations', [TrustedOrganisationController::class, 'index']);
Route::get('/teams', [TeamController::class, 'index']);       // List all teams
Route::get('/about-us', [AboutUsController::class, 'index']);

Route::get('hero-slides', [HeroController::class, 'index']);
Route::get('/tags/{id}', [TagController::class, 'show']);
Route::get('/tags', [TagController::class, 'index']);

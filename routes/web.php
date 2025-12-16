<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Storefront\StorefrontController;

/*
|--------------------------------------------------------------------------
| 🌐 STOREFRONT – LIN XÉN
| Domain: linxen.vn (non-www)
|--------------------------------------------------------------------------
*/

Route::domain('linxen.vn')->group(function () {

    // =====================================================
    // 🏠 HOME
    // =====================================================
    Route::get('/', [StorefrontController::class, 'home'])
        ->name('linxen.home');

    // =====================================================
    // 🔍 SEARCH
    // =====================================================
    Route::get('/search', [StorefrontController::class, 'search'])
        ->name('linxen.search');

    // =====================================================
    // 👗 PRODUCT
    // =====================================================
    Route::get('/p/{slug}', [StorefrontController::class, 'product'])
        ->name('linxen.product');

    // =====================================================
    // 📦 COLLECTION / CATEGORY
    // =====================================================
    Route::get('/c/{slug}', [StorefrontController::class, 'collection'])
        ->name('linxen.collection');

    // =====================================================
    // 🛒 CART s
    // =====================================================
    Route::get('/cart', [StorefrontController::class, 'cart'])
        ->name('linxen.cart');

    // ➕ ADD TO CART (AJAX)
    Route::post('/cart/add', [StorefrontController::class, 'addToCart'])
        ->name('linxen.cart.add');

    // 🔄 UPDATE QTY (AJAX)
    Route::post('/cart/update', [StorefrontController::class, 'updateCart'])
        ->name('linxen.cart.update');

    // ❌ REMOVE ITEM (AJAX)
    Route::post('/cart/remove', [StorefrontController::class, 'removeFromCart'])
        ->name('linxen.cart.remove');

    // =====================================================
    // 💳 CHECKOUT
    // =====================================================
    Route::get('/checkout', [StorefrontController::class, 'checkout'])
        ->name('linxen.checkout');

    Route::post('/checkout/place-order', [StorefrontController::class, 'placeOrder'])
        ->name('linxen.checkout.place_order');

    // =====================================================
    // 👤 ACCOUNT
    // =====================================================
    Route::get('/account', [StorefrontController::class, 'account'])
        ->name('linxen.account');

    Route::get('/account/orders', [StorefrontController::class, 'orders'])
        ->name('linxen.account.orders');

    Route::get('/account/orders/{code}', [StorefrontController::class, 'orderDetail'])
        ->name('linxen.account.order_detail');
});

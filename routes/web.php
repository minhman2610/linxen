<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ReelsController;
use App\Http\Controllers\Storefront\Api\LocationProxyController;
use App\Http\Controllers\Api\CheckoutController;

/*
|--------------------------------------------------------------------------
| 🌐 STOREFRONT – LIN XÉN
| Domain: linxen.vn (non-www)
|--------------------------------------------------------------------------
*/
Route::domain('linxen.vn')
    ->middleware(['web'])
    ->group(function () {

        // 🏠 HOME
        Route::get('/', [PageController::class, 'home'])
            ->name('linxen.home');

        // 🔍 SEARCH
        Route::get('/search', [PageController::class, 'search'])
            ->name('linxen.search');

        // 🎞️ REELS
        Route::get('/reels', [ReelsController::class, 'index'])
            ->name('linxen.reels');

        // 👗 PRODUCT DETAIL
        Route::get('/p/{slug}', [PageController::class, 'product'])
            ->name('linxen.product');

        // 📦 COLLECTION
        Route::get('/c/{slug}', [PageController::class, 'collection'])
            ->name('linxen.collection');

        // 🛒 CART
        Route::get('/cart', [PageController::class, 'cart'])
            ->name('linxen.cart');

        Route::post('/cart/add', [PageController::class, 'addToCart'])
            ->name('linxen.cart.add');

        Route::post('/cart/update', [PageController::class, 'updateCart'])
            ->name('linxen.cart.update');

        Route::post('/cart/remove', [PageController::class, 'removeFromCart'])
            ->name('linxen.cart.remove');

        // 💳 CHECKOUT PAGE
        Route::get('/checkout', [PageController::class, 'checkout'])
            ->name('linxen.checkout');

        Route::get('/checkout/place-order', [PageController::class, 'placeOrder'])
            ->name('linxen.checkout.place_order');

        /*
        |--------------------------------------------------------------------------
        | 🔑 CHECKOUT – AJAX & API
        |--------------------------------------------------------------------------
        */

        // 🔍 Check phone (identity-first)
        Route::post('/ajax/check-phone', [CheckoutController::class, 'checkPhone'])
            ->name('checkout.check_phone');

        // 📦 Create order (checkout submit)
        Route::post('/api/storefront/orders', [CheckoutController::class, 'create'])
            ->name('checkout.create');

        /*
        |--------------------------------------------------------------------------
        | 👤 ACCOUNT
        |--------------------------------------------------------------------------
        */
        Route::get('/account', [PageController::class, 'account'])
            ->name('linxen.account');

        Route::get('/account/orders', [PageController::class, 'orders'])
            ->name('linxen.account.orders');

        Route::get('/account/orders/{code}', [PageController::class, 'orderDetail'])
            ->name('linxen.account.order_detail');
    });

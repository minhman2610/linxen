<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ReelsController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Storefront\Account\OrderController;

/*
|--------------------------------------------------------------------------
| 🌐 STOREFRONT – LIN XÉN
| Domain: linxen.vn (non-www)
|--------------------------------------------------------------------------
*/
Route::domain('linxen.vn')
    ->middleware(['web'])
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | 🏠 HOME
        |--------------------------------------------------------------------------
        */
        Route::get('/', [PageController::class, 'home'])
            ->name('linxen.home');

        /*
        |--------------------------------------------------------------------------
        | 🔍 SEARCH
        |--------------------------------------------------------------------------
        */
        Route::get('/search', [PageController::class, 'search'])
            ->name('linxen.search');

        /*
        |--------------------------------------------------------------------------
        | 🎞️ REELS
        |--------------------------------------------------------------------------
        */
        Route::get('/reels', [ReelsController::class, 'index'])
            ->name('linxen.reels');

        /*
        |--------------------------------------------------------------------------
        | 👗 PRODUCT
        |--------------------------------------------------------------------------
        */
        Route::get('/p/{slug}', [PageController::class, 'product'])
            ->name('linxen.product');

        /*
        |--------------------------------------------------------------------------
        | 📦 COLLECTION
        |--------------------------------------------------------------------------
        */
        Route::get('/c/{slug}', [PageController::class, 'collection'])
            ->name('linxen.collection');

        /*
        |--------------------------------------------------------------------------
        | 🛒 CART
        |--------------------------------------------------------------------------
        */
        Route::get('/cart', [PageController::class, 'cart'])
            ->name('linxen.cart');

        Route::post('/cart/add', [PageController::class, 'addToCart'])
            ->name('linxen.cart.add');

        Route::post('/cart/update', [PageController::class, 'updateCart'])
            ->name('linxen.cart.update');

        Route::post('/cart/remove', [PageController::class, 'removeFromCart'])
            ->name('linxen.cart.remove');

        /*
        |--------------------------------------------------------------------------
        | 💳 CHECKOUT (PAGE)
        |--------------------------------------------------------------------------
        */
        Route::get('/checkout', [PageController::class, 'checkout'])
            ->name('linxen.checkout');

        Route::get('/checkout/place-order', [PageController::class, 'placeOrder'])
            ->name('linxen.checkout.place_order');

        /*
        |--------------------------------------------------------------------------
        | 🔑 CHECKOUT – AJAX / API
        |--------------------------------------------------------------------------
        */

        // 🔍 Check phone (ERP – source of truth)
        Route::post('/ajax/check-phone', [CheckoutController::class, 'checkPhone'])
            ->name('checkout.check_phone');

        // 🔐 Register + auto login
        Route::post('/ajax/register-inline', [CheckoutController::class, 'registerInline'])
            ->name('checkout.register_inline');

        // 📦 Create order
        Route::post('/api/storefront/orders', [CheckoutController::class, 'create'])
            ->name('checkout.create');

        /*
|--------------------------------------------------------------------------
| 👤 ACCOUNT
|--------------------------------------------------------------------------
*/
Route::prefix('account')
    ->middleware(['storefront.auth'])
    ->name('linxen.account.')
    ->group(function () {

        // Dashboard tài khoản
        Route::get('/', [PageController::class, 'account'])
            ->name('index');

        // 🧾 Danh sách đơn hàng
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders');

        // 📦 Chi tiết đơn hàng
        Route::get('/orders/{code}', [OrderController::class, 'show'])
            ->name('orders.show');
    });

    });

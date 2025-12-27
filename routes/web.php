<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ReelsController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Storefront\Account\OrderController;
use App\Http\Controllers\Storefront\Account\AccountController;
use App\Http\Controllers\Storefront\Auth\LoginController;
use App\Http\Controllers\Storefront\Auth\RegisterController;
use App\Http\Controllers\Storefront\Auth\LogoutController;

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
        | 🔐 AUTH (LOGIN / REGISTER / LOGOUT)
        |--------------------------------------------------------------------------
        */
        Route::get('/login', [LoginController::class, 'show'])
            ->name('linxen.login');

        Route::post('/login', [LoginController::class, 'login'])
            ->name('linxen.login.submit');

        Route::get('/register', [RegisterController::class, 'show'])
            ->name('linxen.register');

        Route::post('/register', [RegisterController::class, 'register'])
            ->name('linxen.register.submit');

        Route::post('/logout', [LogoutController::class, 'logout'])
            ->name('linxen.logout');

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
        | 💳 CHECKOUT
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
        Route::post('/ajax/check-phone', [CheckoutController::class, 'checkPhone'])
            ->name('checkout.check_phone');

        Route::post('/ajax/register-inline', [CheckoutController::class, 'registerInline'])
            ->name('checkout.register_inline');

        Route::post('/api/storefront/orders', [CheckoutController::class, 'create'])
            ->name('checkout.create');

        /*
|--------------------------------------------------------------------------
| 👤 ACCOUNT
|--------------------------------------------------------------------------
| ❗ Không dùng middleware auth ở route
| 👉 Check login trong controller
*/
Route::prefix('account')
    ->name('linxen.account.')
    ->group(function () {

        // =========================
        // DASHBOARD
        // =========================
        Route::get('/', [PageController::class, 'account'])
            ->name('index');

        // =========================
        // ORDERS
        // =========================
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders');

        Route::get('/orders/{code}', [OrderController::class, 'show'])
            ->name('orders.show');

        // =========================
        // PROFILE
        // =========================
        Route::get('/profile', [AccountController::class, 'profile'])
            ->name('profile');

        Route::post('/profile', [AccountController::class, 'updateProfile'])
            ->name('profile.update');

        // =========================
        // ADDRESSES
        // =========================
        Route::get('/addresses', [AccountController::class, 'addresses'])
            ->name('addresses');

        Route::post('/addresses', [AccountController::class, 'storeAddress'])
            ->name('addresses.store');

        // ⭐ Set default address
        Route::post('/addresses/{id}/default', [AccountController::class, 'setDefaultAddress'])
            ->name('addresses.setDefault');

        // ✏️ Update address
        Route::post('/addresses/{id}/update', [AccountController::class, 'updateAddress'])
            ->name('addresses.update');

        // 🗑 Delete address
        Route::post('/addresses/{id}/delete', [AccountController::class, 'deleteAddress'])
            ->name('addresses.delete');
    });

    });

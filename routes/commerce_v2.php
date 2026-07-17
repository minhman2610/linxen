<?php

use App\Http\Controllers\CommerceV2\AccountController;
use App\Http\Controllers\CommerceV2\AttributionRedirectController;
use App\Http\Controllers\CommerceV2\CartController;
use App\Http\Controllers\CommerceV2\CatalogPageController;
use App\Http\Controllers\CommerceV2\CheckoutController;
use App\Http\Controllers\CommerceV2\DiscoverController;
use App\Http\Controllers\CommerceV2\OrderController;
use App\Http\Controllers\CommerceV2\PdpSizeAdviceController;
use Illuminate\Support\Facades\Route;

$prefix = trim(
    (string) config(
        'commerce_v2.stage_prefix',
        'v2'
    ),
    '/'
);

Route::get('/go/{token}', AttributionRedirectController::class)
    ->where('token', 'att_[A-Za-z0-9]{32,100}')
    ->name('commerce.v2.attribution.go');

Route::prefix($prefix)
    ->name('commerce.v2.')
    ->group(function () {
        Route::get('/', [
            CatalogPageController::class,
            'home',
        ])->name('home');

        Route::get('/shop', [
            CatalogPageController::class,
            'shop',
        ])->name('shop');

        Route::get('/search', [
            CatalogPageController::class,
            'search',
        ])->name('search');

        Route::get('/discover', [
            DiscoverController::class,
            'index',
        ])->name('discover');

        Route::get('/collections/{slug}', [
            CatalogPageController::class,
            'collection',
        ])->where('slug', '[A-Za-z0-9._-]+')
            ->name('collection');

        Route::get('/p/{slug}', [
            CatalogPageController::class,
            'product',
        ])->where('slug', '[A-Za-z0-9._-]+')
            ->name('product');

        /* AI_PATCH_LINXEN_PDP_SALES_EXPERIENCE_V2_START */
        Route::post('/p/{slug}/size-advice', [
            PdpSizeAdviceController::class,
            '__invoke',
        ])->where('slug', '[A-Za-z0-9._-]+')
            ->middleware('throttle:30,1')
            ->name('product.size_advice');
        /* AI_PATCH_LINXEN_PDP_SALES_EXPERIENCE_V2_END */

        Route::get('/cart', [
            CartController::class,
            'index',
        ])->name('cart.index');

        Route::post('/cart/items', [
            CartController::class,
            'store',
        ])->name('cart.items.store');

        Route::patch('/cart/items/{sellableSkuId}', [
            CartController::class,
            'update',
        ])->where('sellableSkuId', 'sku_[0-9]+')
            ->name('cart.items.update');

        Route::delete('/cart/items/{sellableSkuId}', [
            CartController::class,
            'destroy',
        ])->where('sellableSkuId', 'sku_[0-9]+')
            ->name('cart.items.destroy');

        Route::delete('/cart', [
            CartController::class,
            'clear',
        ])->name('cart.clear');

        Route::get('/account', [
            AccountController::class,
            'index',
        ])->name('account.index');

        Route::get('/account/login/{ticket}', [
            AccountController::class,
            'exchange',
        ])->where('ticket', '[A-Za-z0-9_-]{32,160}')
            ->name('account.exchange');

        Route::delete('/account/session', [
            AccountController::class,
            'logout',
        ])->name('account.logout');

        Route::get('/checkout', [
            CheckoutController::class,
            'index',
        ])->name('checkout.index');

        Route::post('/checkout/place-order', [
            CheckoutController::class,
            'placeOrder',
        ])
            ->middleware('throttle:10,1')
            ->name('checkout.place_order');

        Route::get(
            '/checkout/locations/{location}/wards',
            [
                CheckoutController::class,
                'wards',
            ]
        )
            ->whereNumber('location')
            ->name('checkout.wards');

        Route::post('/checkout/quote', [
            CheckoutController::class,
            'createQuote',
        ])->name('checkout.quote.create');

        Route::get('/checkout/confirm', [
            CheckoutController::class,
            'confirm',
        ])->name('checkout.confirm');

        Route::delete('/checkout/quote', [
            CheckoutController::class,
            'requote',
        ])->name('checkout.quote.requote');

        Route::post('/orders', [
            OrderController::class,
            'store',
        ])->name('orders.store');

        Route::get('/orders', [
            OrderController::class,
            'index',
        ])->name('orders.index');

        Route::get('/order-success/{order}', [
            OrderController::class,
            'success',
        ])
            ->where('order', 'ord_[A-Za-z0-9]+')
            ->name('orders.success');

        Route::get('/orders/{order}', [
            OrderController::class,
            'show',
        ])
            ->where('order', 'ord_[A-Za-z0-9]+')
            ->name('orders.show');

        Route::delete('/orders/{order}', [
            OrderController::class,
            'cancel',
        ])
            ->where('order', 'ord_[A-Za-z0-9]+')
            ->name('orders.cancel');
    });

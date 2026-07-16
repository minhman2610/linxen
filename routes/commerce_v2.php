<?php

use App\Http\Controllers\CommerceV2\AccountController;
use App\Http\Controllers\CommerceV2\CartController;
use App\Http\Controllers\CommerceV2\CatalogPageController;
use Illuminate\Support\Facades\Route;

$prefix = trim(
    (string) config(
        'commerce_v2.stage_prefix',
        'v2'
    ),
    '/'
);

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
    });

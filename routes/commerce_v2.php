<?php

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
        ])
            ->where('slug', '[A-Za-z0-9._-]+')
            ->name('collection');

        Route::get('/p/{slug}', [
            CatalogPageController::class,
            'product',
        ])
            ->where('slug', '[A-Za-z0-9._-]+')
            ->name('product');
    });

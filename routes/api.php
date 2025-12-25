<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| 🔌 API – STOREFRONT (LIN XÉN)
|--------------------------------------------------------------------------
| Prefix: /api/storefront
| Mục đích:
| - Checkout tạo đơn → ERP (có auth)
| - Proxy location / ward từ ERP
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\LocationProxyController;

Route::domain('linxen.vn')          // 🔥 QUAN TRỌNG
    ->middleware(['web'])           // 🔥 QUAN TRỌNG
    ->prefix('storefront')
    ->group(function () {

        Route::post('/orders', [CheckoutController::class, 'create'])
            ->name('api.storefront.orders.create');

        Route::get('/locations', [LocationProxyController::class, 'locations'])
            ->name('api.storefront.locations');

        Route::get('/locations/{locationId}/wards', [LocationProxyController::class, 'wards'])
            ->name('api.storefront.wards');
    });


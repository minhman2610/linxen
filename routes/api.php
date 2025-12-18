<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\LocationProxyController;

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

Route::prefix('storefront')->group(function () {

    // 🧾 Tạo đơn hàng từ checkout LIN XÉN
    Route::post('/orders', [CheckoutController::class, 'create'])
        ->name('api.storefront.orders.create');

    // 📍 Danh sách khu vực (proxy từ ERP)
    Route::get('/locations', [LocationProxyController::class, 'locations'])
        ->name('api.storefront.locations');

    // 🏠 Danh sách phường / xã theo khu vực
    Route::get('/locations/{locationId}/wards', [LocationProxyController::class, 'wards'])
        ->name('api.storefront.wards');

});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\LocationProxyController;

Route::prefix('storefront')->group(function () {

    // 🧾 Tạo đơn hàng từ checkout LIN XÉN
    Route::post('/orders', [CheckoutController::class, 'create'])
        ->name('api.storefront.orders.create');

    // 📍 Khu vực (proxy từ ERP)
    Route::get('/locations', [LocationProxyController::class, 'locations'])
        ->name('api.storefront.locations');

    // 🏠 Phường / xã theo khu vực
    Route::get('/locations/{locationId}/wards', [LocationProxyController::class, 'wards'])
        ->name('api.storefront.wards');

});

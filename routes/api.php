<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\LocationProxyController;

Route::prefix('storefront')->group(function () {

    // 🧾 CHECKOUT – CẦN SESSION → GẮN WEB
    Route::middleware('web')->post('/orders', [CheckoutController::class, 'create'])
        ->name('api.storefront.orders.create');

    // 📍 Khu vực (proxy từ ERP) – KHÔNG cần session
    Route::get('/locations', [LocationProxyController::class, 'locations'])
        ->name('api.storefront.locations');

    // 🏠 Phường / xã – KHÔNG cần session
    Route::get('/locations/{locationId}/wards', [LocationProxyController::class, 'wards'])
        ->name('api.storefront.wards');
});

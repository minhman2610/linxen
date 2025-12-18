<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Storefront\CheckoutController;

Route::prefix('storefront')->group(function () {

    // 🧾 Tạo đơn hàng từ checkout LIN XÉN
    Route::post('/orders', [CheckoutController::class, 'create'])
        ->name('api.storefront.orders.create');

});

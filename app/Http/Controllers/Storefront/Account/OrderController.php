<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * =====================================================
     * 🧾 DANH SÁCH ĐƠN HÀNG
     * =====================================================
     */
    public function index(Request $request)
    {
        $customer = session('customer');
        $orders   = [];

        if (!$customer || empty($customer['phone'])) {
            return redirect()->route('linxen.home');
        }

        try {
            $res = Http::withOptions(['verify' => false])
                ->timeout(6)
                ->withHeaders([
                    'X-Storefront-Code' => 'linxen',
                    'Authorization'     => 'Bearer ' . session('login_token'),
                    'Accept'            => 'application/json',
                ])
                ->get(config('services.erp.url') . '/api/storefront/orders', [
                    'phone' => $customer['phone'],
                ]);

            if ($res->ok()) {
                $orders = $res->json('orders') ?? [];
            }

        } catch (\Throwable $e) {
            Log::warning('[ACCOUNT ORDERS FETCH FAILED]', [
                'error' => $e->getMessage(),
            ]);
        }

        return view('storefront.luxe.pages.account.orders.index', [
            'orders'   => $orders,
            'customer' => (object) $customer,
        ]);
    }

    /**
     * =====================================================
     * 📦 CHI TIẾT ĐƠN HÀNG
     * =====================================================
     */
    public function show(string $code)
    {
        $customer = session('customer');

        if (!$customer) {
            return redirect()->route('linxen.home');
        }

        $order = null;

        try {
            $res = Http::withOptions(['verify' => false])
                ->timeout(6)
                ->withHeaders([
                    'X-Storefront-Code' => 'linxen',
                    'Authorization'     => 'Bearer ' . session('login_token'),
                ])
                ->get(config('services.erp.url') . "/api/storefront/orders/{$code}");

            if ($res->ok()) {
                $order = $res->json('order');
            }

        } catch (\Throwable $e) {
            Log::warning('[ACCOUNT ORDER DETAIL FAILED]', [
                'code'  => $code,
                'error' => $e->getMessage(),
            ]);
        }

        if (!$order) {
            abort(404);
        }

        return view('storefront.luxe.pages.account.orders.show', [
            'order'    => $order,
            'customer' => (object) $customer,
        ]);
    }
}

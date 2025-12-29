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
    $order  = null;
    $error  = null;

    try {
        $res = Http::withOptions(['verify' => false])
            ->timeout(6)
            ->withHeaders([
                'X-Storefront-Code' => 'linxen',
            ])
            ->get(config('services.erp.url') . "/api/storefront/orders/{$code}");

        // ❌ Lỗi HTTP / ERP chết
        if (!$res->ok()) {
            Log::warning('[STORE ORDER FETCH FAILED]', [
                'code'   => $code,
                'status' => $res->status(),
                'body'   => $res->body(),
            ]);

            $error = 'Không thể tải thông tin đơn hàng. Vui lòng thử lại sau.';
        } else {
            $payload = $res->json();

            // ❌ Lỗi nghiệp vụ
            if (empty($payload['success'])) {
                $error = $payload['message'] ?? 'Không thể xác minh đơn hàng.';
            }
            // ❌ Không có order
            elseif (empty($payload['order'])) {
                Log::warning('[STORE ORDER NOT FOUND]', [
                    'code'    => $code,
                    'payload' => $payload,
                ]);

                $error = 'Không tìm thấy đơn hàng với mã này.';
            } else {
                $order = (object) $payload['order'];
            }
        }

    } catch (\Throwable $e) {
        Log::error('[STORE ORDER EXCEPTION]', [
            'code'  => $code,
            'error' => $e->getMessage(),
        ]);

        $error = 'Có lỗi kỹ thuật xảy ra khi tải đơn hàng.';
    }

    // ❌ Có lỗi → render page thông báo (KHÔNG redirect)
    if ($error) {
        return view('storefront.luxe.pages.account.orders.show-error', [
            'orderCode' => $code,
            'message'   => $error,
        ]);
    }

    // ✅ OK
    return view('storefront.luxe.pages.account.orders.show', [
        'order' => $order,
    ]);
}


}

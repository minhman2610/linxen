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
     * 🔗 Resolve ERP order link (internal / debug)
     * =====================================================
     */
    protected function resolveErpOrderLink(string $code): ?string
    {
        // Cho phép bật / tắt qua env nếu cần
        if (!config('app.show_erp_link', true)) {
            return null;
        }

        $erpUrl = rtrim(config('services.erp.url'), '/');

        if (!$erpUrl || !str_starts_with($erpUrl, 'http')) {
            return null;
        }

        // 👉 TUỲ ERP: chỉnh path nếu khác
        return "{$erpUrl}/orders/{$code}";
    }

    /**
     * =====================================================
     * 🧾 DANH SÁCH ĐƠN HÀNG
     * =====================================================
     */
    public function index(Request $request)
    {
        $customer = session('customer');
        $orders   = [];
        $error    = null;

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
                ->get(
                    config('services.erp.url') . '/api/storefront/orders',
                    [
                        'phone' => $customer['phone'],
                    ]
                );

            if (!$res->ok()) {
                Log::warning('[ACCOUNT ORDERS FETCH FAILED]', [
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);

                $error = 'Không thể tải danh sách đơn hàng.';
            } else {
                $orders = collect($res->json('orders') ?? [])
                    ->map(function ($order) {
                        $order['erp_link'] = $this->resolveErpOrderLink($order['code'] ?? '');
                        return $order;
                    })
                    ->toArray();
            }

        } catch (\Throwable $e) {
            Log::error('[ACCOUNT ORDERS EXCEPTION]', [
                'error' => $e->getMessage(),
            ]);

            $error = 'Có lỗi kỹ thuật xảy ra khi tải danh sách đơn hàng.';
        }

        return view('storefront.luxe.pages.account.orders.index', [
            'orders'   => $orders,
            'customer' => (object) $customer,
            'error'    => $error,
        ]);
    }

    /**
     * =====================================================
     * 📦 CHI TIẾT ĐƠN HÀNG
     * =====================================================
     */
    public function show(string $code)
    {
        $order = null;
        $error = null;

        try {
            $res = Http::withOptions(['verify' => false])
                ->timeout(6)
                ->withHeaders([
                    'X-Storefront-Code' => 'linxen',
                ])
                ->get(
                    config('services.erp.url') . "/api/storefront/orders/{$code}"
                );

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
                }
                // ✅ OK
                else {
                    $order = (object) $payload['order'];

                    // 🔗 ERP LINK
                    $order->erp_link = $this->resolveErpOrderLink($order->code ?? $code);
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

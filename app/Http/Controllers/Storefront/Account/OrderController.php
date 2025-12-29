<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ERP\ErpStorefrontApi;

class OrderController extends Controller
{
    /**
     * =====================================================
     * 🧾 DANH SÁCH ĐƠN HÀNG
     * =====================================================
     * - Lấy theo SĐT khách từ session
     * - ERP là source of truth
     */
    public function index(Request $request, ErpStorefrontApi $erp)
    {
        $customer = session('customer');

        if (!$customer || empty($customer['phone'])) {
            return redirect()->route('linxen.home');
        }

        $res = $erp->orders($customer['phone']);

        return view('storefront.luxe.pages.account.orders.index', [
            'orders'   => $res['orders'] ?? [],
            'customer' => (object) $customer,
            'error'    => ($res['success'] ?? false)
                ? null
                : ($res['message'] ?? 'Không thể tải danh sách đơn hàng.'),
        ]);
    }

    /**
     * =====================================================
     * 📦 CHI TIẾT ĐƠN HÀNG
     * =====================================================
     * - Không redirect mù
     * - Lỗi thì render show-error
     */
    public function show(string $code, ErpStorefrontApi $erp)
    {
        $res = $erp->orderDetail($code);

        if (!($res['success'] ?? false)) {
            return view('storefront.luxe.pages.account.orders.show-error', [
                'orderCode' => $code,
                'message'   => $res['message'] ?? 'Không thể tải thông tin đơn hàng.',
            ]);
        }

        return view('storefront.luxe.pages.account.orders.show', [
            'order' => (object) $res['order'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ERP\ErpStorefrontApi;

class AccountController extends Controller
{
    protected ErpStorefrontApi $erp;

    public function __construct(ErpStorefrontApi $erp)
    {
        $this->erp = $erp;
    }

    /**
     * =====================================================
     * 🔐 CHECK LOGIN (STORE FRONT)
     * =====================================================
     */
    protected function requireLogin()
    {
        $customer = session('customer');

        if (!$customer) {
            return redirect()
                ->route('linxen.login')
                ->with('warning', 'Vui lòng đăng nhập để tiếp tục.');
        }

        return null;
    }

    /**
     * =====================================================
     * 👤 PROFILE (ERP)
     * =====================================================
     */
    public function profile()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        // Lấy profile từ ERP (dựa trên login_token / session)
        $customer = $this->erp->customerProfile();

        return view('storefront.luxe.pages.account.profile', [
            'user' => $customer,
        ]);
    }

    public function updateProfile(Request $request)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $this->erp->updateCustomerProfile($data);

        return back()->with('success', 'Cập nhật thông tin thành công');
    }

    /**
     * =====================================================
     * 📍 ADDRESSES (ERP)
     * =====================================================
     */
    public function addresses()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $addresses = $this->erp->customerAddresses();

        return view('storefront.luxe.pages.account.addresses', [
            'addresses' => $addresses,
        ]);
    }

    public function storeAddress(Request $request)
{
    // 🔐 Check login theo session customer
    if ($redirect = $this->requireLogin()) {
        return $redirect;
    }

    // ✅ Validate dữ liệu form (theo checkout)
    $data = $request->validate([
        'name'           => 'required|string|max:255',
        'phone'          => 'required|string|max:20',
        'location_id'    => 'required|integer',
        'ward_id'        => 'required|integer',
        'street'         => 'required|string|max:255',

        // optional nhưng nên có để ERP hiển thị
        'location_name'  => 'nullable|string|max:255',
        'ward_name'      => 'nullable|string|max:255',
    ]);

    try {
        // 🚀 Push sang ERP
        $res = $this->erp->createCustomerAddress([
            'name'          => $data['name'],
            'phone'         => $data['phone'],
            'location_id'   => $data['location_id'],
            'ward_id'       => $data['ward_id'],
            'street'        => $data['street'],
            'location_name' => $data['location_name'] ?? null,
            'ward_name'     => $data['ward_name'] ?? null,
        ]);

        /**
         * Kỳ vọng ERP trả về dạng:
         * {
         *   success: true|false,
         *   message: "...",
         * }
         */
        if (empty($res) || !($res['success'] ?? false)) {
            $msg = $res['message'] ?? 'Không thể lưu địa chỉ. Vui lòng thử lại.';
            return back()
                ->withInput()
                ->withErrors([
                    'address' => $msg,
                ]);
        }

        // ✅ Thành công
        return back()->with('success', 'Đã thêm địa chỉ nhận hàng');

    } catch (\Throwable $e) {
        // ❌ Lỗi kết nối / exception
        \Log::error('[ACCOUNT][STORE_ADDRESS_FAIL]', [
            'error' => $e->getMessage(),
        ]);

        return back()
            ->withInput()
            ->withErrors([
                'address' => 'Không kết nối được hệ thống. Vui lòng thử lại sau.',
            ]);
    }
}

}

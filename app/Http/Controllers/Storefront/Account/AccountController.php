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
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'address' => 'required|string|max:1000',
        ]);

        $this->erp->createCustomerAddress($data);

        return back()->with('success', 'Đã thêm địa chỉ nhận hàng');
    }
}

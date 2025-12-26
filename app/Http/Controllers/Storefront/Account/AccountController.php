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
     * 👤 PROFILE (ERP)
     * =====================================================
     */
    public function profile()
    {
        if (!auth()->check()) {
            return redirect()->route('linxen.login');
        }

        // Lấy customer từ ERP (qua token / session)
        $customer = $this->erp->get('/api/storefront/customer/profile');

        return view('storefront.luxe.pages.account.profile', [
            'user' => $customer,
        ]);
    }

    public function updateProfile(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('linxen.login');
        }

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        // Update profile ERP
        $this->erp->post('/api/storefront/customer/update-profile', $data);

        return back()->with('success', 'Cập nhật thông tin thành công');
    }

    /**
     * =====================================================
     * 📍 ADDRESSES (ERP)
     * =====================================================
     */
    public function addresses()
    {
        if (!auth()->check()) {
            return redirect()->route('linxen.login');
        }

        $addresses = $this->erp->get('/api/storefront/customer/addresses');

        return view('storefront.luxe.pages.account.addresses', [
            'addresses' => $addresses['addresses'] ?? [],
        ]);
    }

    public function storeAddress(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('linxen.login');
        }

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'address' => 'required|string|max:1000',
        ]);

        $this->erp->post('/api/storefront/customer/addresses', $data);

        return back()->with('success', 'Đã thêm địa chỉ nhận hàng');
    }
}

<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ERP\ErpCustomerService;

class AccountController extends Controller
{
    protected ErpCustomerService $erp;

    public function __construct(ErpCustomerService $erp)
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

        $customer = $this->erp->getCustomerByUser(auth()->user());

        return view('storefront.luxe.account.profile', [
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

        $this->erp->updateCustomerProfile(
            auth()->user(),
            $data
        );

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

        $addresses = $this->erp->getCustomerAddresses(
            auth()->user()
        );

        return view('storefront.luxe.account.addresses', [
            'addresses' => $addresses,
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

        $this->erp->createCustomerAddress(
            auth()->user(),
            $data
        );

        return back()->with('success', 'Đã thêm địa chỉ nhận hàng');
    }
}

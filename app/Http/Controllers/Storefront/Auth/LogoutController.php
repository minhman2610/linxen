<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * =====================================================
     * 🚪 LOGOUT (CLEAR STOREFRONT SESSION)
     * =====================================================
     */
    public function logout(Request $request)
    {
        // ❌ Xoá session customer
        $request->session()->forget('customer');

        // ❌ Xoá intended URL (nếu có)
        $request->session()->forget('url.intended');

        // 🔁 Regenerate session (an toàn)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ✅ Quay về trang chủ
        return redirect()
            ->route('linxen.home')
            ->with('success', 'Bạn đã đăng xuất thành công.');
    }
}

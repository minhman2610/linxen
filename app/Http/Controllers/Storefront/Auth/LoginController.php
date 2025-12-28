<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /**
     * =====================================================
     * 🧾 SHOW LOGIN PAGE
     * =====================================================
     */
    public function show()
    {
        if (session()->has('customer')) {
            return redirect()->route('linxen.account.index');
        }

        return view('storefront.luxe.pages.auth.login');
    }

    public function login(Request $request)
{
    $data = $request->validate([
        'phone'    => 'required|string|max:20',
        'password' => 'required|string|min:6',
    ]);

    try {
        $response = Http::withOptions([
                'verify' => false,
            ])
            ->timeout(10)
            ->withHeaders([
                'Accept'            => 'application/json',
                'X-Storefront-Code' => 'linxen',
            ])
            ->post(
                config('services.erp.base_url') . '/api/storefront/auth/login',
                $data
            );

        $json = $response->json();

        /* =====================================================
         * ❌ ERP LOGIN FAIL
         * ===================================================== */
        if ($response->failed() || empty($json['success'])) {

            $message = $json['message']
                ?? 'Số điện thoại hoặc mật khẩu không đúng.';

            Log::warning('❌ [STOREFRONT LOGIN FAILED]', [
                'status' => $response->status(),
                'body'   => $json,
            ]);

            // 👉 AJAX REQUEST (checkout modal)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            // 👉 WEB LOGIN (blade)
            return back()
                ->withInput($request->only('phone'))
                ->withErrors([
                    'login' => $message,
                ]);
        }

        /* =====================================================
         * ❌ ERP RESPONSE INVALID
         * ===================================================== */
        if (empty($json['customer'])) {

            $message = 'Không thể đăng nhập. Vui lòng thử lại.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()
                ->withInput($request->only('phone'))
                ->withErrors([
                    'login' => $message,
                ]);
        }

        /* =====================================================
         * ✅ LOGIN SUCCESS
         * ===================================================== */
        session([
            'customer' => $json['customer'],
        ]);

        // 👉 AJAX (checkout)
        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'customer' => $json['customer'],
            ]);
        }

        // 👉 WEB LOGIN
        return redirect()->intended(
            route('linxen.account.index')
        );

    } catch (\Throwable $e) {

        Log::error('🔥 [STOREFRONT LOGIN EXCEPTION]', [
            'error' => $e->getMessage(),
        ]);

        $message = 'Hệ thống đang bận. Vui lòng thử lại sau.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }

        return back()
            ->withInput($request->only('phone'))
            ->withErrors([
                'login' => $message,
            ]);
    }
}

}

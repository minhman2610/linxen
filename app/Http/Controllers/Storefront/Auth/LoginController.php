<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
        // Nếu đã login → không cho vào lại trang login
        if (session()->has('customer')) {
            return redirect()->route('linxen.account.index');
        }

        return view('storefront.luxe.pages.auth.login');
    }

    /**
     * =====================================================
     * 🔐 LOGIN (STOREFRONT → ERP AUTH)
     * =====================================================
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        try {

            /**
             * ⚠️ GỌI ERP AUTH LOGIN
             * ERP sẽ:
             * - Xác thực
             * - Trả customer info
             * - Set session / cookie nếu cần
             */
            $response = Http::withOptions([
                    'verify' => false,
                ])
                ->timeout(10)
                ->withHeaders([
                    'Accept'             => 'application/json',
                    'X-Storefront-Code'  => 'linxen',
                ])
                ->post(
                    config('services.erp.base_url') . '/api/storefront/auth/login',
                    $data
                );

            if ($response->failed()) {
                Log::warning('❌ [STOREFRONT LOGIN FAILED]', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return back()
                    ->withInput()
                    ->withErrors([
                        'login' => 'Số điện thoại hoặc mật khẩu không đúng.',
                    ]);
            }

            $json = $response->json();

            if (!($json['success'] ?? false) || empty($json['customer'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'login' => $json['message'] ?? 'Đăng nhập thất bại.',
                    ]);
            }

            /**
             * =====================================================
             * ✅ SET SESSION CUSTOMER (SOURCE OF TRUTH)
             * =====================================================
             */
            session([
                'customer' => $json['customer'],
            ]);

            /**
             * =====================================================
             * 🔁 REDIRECT SAU LOGIN
             * =====================================================
             */
            return redirect()->intended(
                route('linxen.account.index')
            );

        } catch (\Throwable $e) {

            Log::error('🔥 [STOREFRONT LOGIN EXCEPTION]', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'login' => 'Không thể kết nối hệ thống. Vui lòng thử lại.',
                ]);
        }
    }
}

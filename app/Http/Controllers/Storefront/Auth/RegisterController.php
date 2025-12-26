<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    /**
     * =====================================================
     * 🧾 SHOW REGISTER PAGE
     * =====================================================
     */
    public function show()
    {
        // Nếu đã login → không cho vào lại trang đăng ký
        if (session()->has('customer')) {
            return redirect()->route('linxen.account.index');
        }

        return view('storefront.luxe.pages.auth.register');
    }

    /**
     * =====================================================
     * 📝 REGISTER (STOREFRONT → ERP AUTH)
     * - Tạo tài khoản
     * - Auto login
     * =====================================================
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'phone'    => 'required|string|max:20',
            'email'    => 'nullable|email|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $payload = [
                'phone'    => $data['phone'],
                'password' => $data['password'],
            ];

            // Chỉ gửi email nếu có
            if (!empty($data['email'])) {
                $payload['email'] = $data['email'];
            }

            $response = Http::withOptions([
                    'verify' => false,
                ])
                ->timeout(10)
                ->withHeaders([
                    'Accept'            => 'application/json',
                    'X-Storefront-Code' => 'linxen',
                ])
                ->post(
                    config('services.erp.base_url') . '/api/storefront/auth/register',
                    $payload
                );

            /**
             * ❌ ERP REGISTER FAIL
             */
            if ($response->failed()) {
                $json = $response->json();

                Log::warning('❌ [STOREFRONT REGISTER FAILED]', [
                    'status' => $response->status(),
                    'body'   => $json,
                ]);

                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'register' => $json['message']
                            ?? 'Không thể tạo tài khoản. Vui lòng thử lại.',
                    ]);
            }

            $json = $response->json();

            /**
             * ❌ RESPONSE KHÔNG HỢP LỆ
             */
            if (empty($json['success']) || empty($json['customer'])) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'register' => 'Không thể tạo tài khoản. Vui lòng thử lại.',
                    ]);
            }

            /**
             * ✅ SET SESSION CUSTOMER (AUTO LOGIN)
             */
            session([
                'customer' => $json['customer'],
            ]);

            /**
             * 🔁 REDIRECT SAU REGISTER
             */
            return redirect()
                ->route('linxen.account.index')
                ->with('success', 'Tạo tài khoản thành công.');

        } catch (\Throwable $e) {

            Log::error('🔥 [STOREFRONT REGISTER EXCEPTION]', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'register' => 'Hệ thống đang bận. Vui lòng thử lại sau.',
                ]);
        }
    }
}

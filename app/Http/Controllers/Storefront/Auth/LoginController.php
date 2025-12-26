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

            /**
             * ❌ ERP LOGIN FAIL (401 / 422 / 500)
             */
            if ($response->failed()) {
                $json = $response->json();

                Log::warning('❌ [STOREFRONT LOGIN FAILED]', [
                    'status' => $response->status(),
                    'body'   => $json,
                ]);

                return back()
                    ->withInput($request->only('phone'))
                    ->withErrors([
                        'login' => $json['message']
                            ?? 'Số điện thoại hoặc mật khẩu không đúng.',
                    ]);
            }

            $json = $response->json();

            /**
             * ❌ ERP RESPONSE KHÔNG HỢP LỆ
             */
            if (empty($json['success']) || empty($json['customer'])) {
                return back()
                    ->withInput($request->only('phone'))
                    ->withErrors([
                        'login' => 'Không thể đăng nhập. Vui lòng thử lại.',
                    ]);
            }

            /**
             * ✅ SET SESSION CUSTOMER
             */
            session([
                'customer' => $json['customer'],
            ]);

            /**
             * 🔁 REDIRECT SAU LOGIN
             */
            return redirect()->intended(
                route('linxen.account.index')
            );

        } catch (\Throwable $e) {

            Log::error('🔥 [STOREFRONT LOGIN EXCEPTION]', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->only('phone'))
                ->withErrors([
                    'login' => 'Hệ thống đang bận. Vui lòng thử lại sau.',
                ]);
        }
    }
}

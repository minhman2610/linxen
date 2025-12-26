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
        // Đã login thì không cho đăng ký lại
        if (session()->has('customer')) {
            return redirect()->route('linxen.account.index');
        }

        return view('storefront.luxe.pages.auth.register');
    }

    /**
     * =====================================================
     * 📝 REGISTER (STOREFRONT → ERP AUTH)
     * - Chuẩn hoá SĐT (+84 → 0)
     * - Validate SĐT & mật khẩu
     * - Tạo tài khoản + auto login
     * =====================================================
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'phone'                 => 'required|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | 1️⃣ CHUẨN HOÁ SỐ ĐIỆN THOẠI
            |--------------------------------------------------------------------------
            */
            $phone = $this->normalizePhone($data['phone']);

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ VALIDATE SỐ ĐIỆN THOẠI (SAU CHUẨN HOÁ)
            |--------------------------------------------------------------------------
            | Format hợp lệ: 0xxxxxxxxx (10 số)
            */
            if (!preg_match('/^0[0-9]{9}$/', $phone)) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'register' => 'Số điện thoại không hợp lệ. Vui lòng nhập dạng 0xxxxxxxxx.',
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ BUILD PAYLOAD GỬI ERP
            |--------------------------------------------------------------------------
            */
            $payload = [
                'phone'    => $phone,                 // ✅ luôn lưu 0xxxxxxxxx
                'password' => $data['password'],
            ];

            if (!empty($data['email'])) {
                $payload['email'] = $data['email'];
            }

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ CALL ERP REGISTER API
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | 5️⃣ ERP REGISTER FAIL
            |--------------------------------------------------------------------------
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

            /*
            |--------------------------------------------------------------------------
            | 6️⃣ RESPONSE KHÔNG HỢP LỆ
            |--------------------------------------------------------------------------
            */
            if (empty($json['success']) || empty($json['customer'])) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'register' => 'Không thể tạo tài khoản. Vui lòng thử lại.',
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 7️⃣ AUTO LOGIN – SET SESSION CUSTOMER
            |--------------------------------------------------------------------------
            */
            session([
                'customer' => $json['customer'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | 8️⃣ REDIRECT SAU KHI ĐĂNG KÝ
            |--------------------------------------------------------------------------
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

    /**
     * =====================================================
     * 🔁 NORMALIZE PHONE
     * - +84xxxxxxxxx → 0xxxxxxxxx
     * - Giữ nguyên nếu đã là 0xxxxxxxxx
     * =====================================================
     */
    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+84')) {
            return '0' . substr($phone, 3);
        }

        return $phone;
    }
}

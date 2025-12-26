<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * =====================================================
     * 🧾 SHOW REGISTER PAGE
     * =====================================================
     */
    public function show()
    {
        // Đã login → không vào register
        if (session()->has('customer')) {
            return redirect()->route('linxen.account.index');
        }

        return view('storefront.luxe.pages.auth.register');
    }

    /**
     * =====================================================
     * 📝 REGISTER (STOREFRONT → ERP AUTH)
     * - Validate chặt phone + password
     * - Chuẩn hoá phone
     * - Auto login sau khi tạo
     * =====================================================
     */
    public function register(Request $request)
    {
        /**
         * -------------------------------------------------
         * 1️⃣ VALIDATE INPUT (STOREFRONT LEVEL)
         * -------------------------------------------------
         */
        $data = $request->validate(
            [
                'phone'    => [
                    'required',
                    'string',
                    'max:20',
                    // VN phone: 0xxx hoặc +84xxx
                    'regex:/^(0|\+84)[0-9]{9}$/',
                ],
                'email'    => 'nullable|email|max:255',
                'password' => 'required|string|min:6|confirmed',
            ],
            [
                'phone.required' => 'Vui lòng nhập số điện thoại.',
                'phone.regex'    => 'Số điện thoại không hợp lệ.',
                'password.min'   => 'Mật khẩu tối thiểu 6 ký tự.',
                'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            ]
        );

        /**
         * -------------------------------------------------
         * 2️⃣ NORMALIZE PHONE → +84
         * -------------------------------------------------
         */
        $phone = $data['phone'];

        if (Str::startsWith($phone, '0')) {
            $phone = '+84' . substr($phone, 1);
        }

        try {
            /**
             * -------------------------------------------------
             * 3️⃣ BUILD PAYLOAD GỬI ERP
             * -------------------------------------------------
             */
            $payload = [
                'phone'    => $phone,
                'password' => $data['password'],
            ];

            if (!empty($data['email'])) {
                $payload['email'] = $data['email'];
            }

            /**
             * -------------------------------------------------
             * 4️⃣ CALL ERP REGISTER API
             * -------------------------------------------------
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

            /**
             * -------------------------------------------------
             * ❌ ERP REGISTER FAIL
             * -------------------------------------------------
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
             * -------------------------------------------------
             * ❌ RESPONSE KHÔNG HỢP LỆ
             * -------------------------------------------------
             */
            if (empty($json['success']) || empty($json['customer'])) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'register' => 'Không thể tạo tài khoản. Vui lòng thử lại.',
                    ]);
            }

            /**
             * -------------------------------------------------
             * ✅ AUTO LOGIN → SET SESSION
             * -------------------------------------------------
             */
            session([
                'customer' => $json['customer'],
            ]);

            /**
             * -------------------------------------------------
             * 🔁 REDIRECT
             * -------------------------------------------------
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckoutController extends Controller
{
    protected string $erpBaseUrl;

    public function __construct()
    {
        $this->erpBaseUrl = rtrim(config('services.erp.base_url'), '/');
    }

    /**
 * =====================================================
 * 🔍 CHECK PHONE (STORE​FRONT → ERP – SOURCE OF TRUTH)
 * =====================================================
 */
public function checkPhone(Request $request): JsonResponse
{
    $request->validate([
        'phone' => 'required|string|max:20',
    ]);

    // =====================================================
    // 1️⃣ NORMALIZE PHONE (RẤT QUAN TRỌNG)
    // =====================================================
    $phone = $this->normalizePhone($request->input('phone'));

    if (!$phone) {
        return response()->json([
            'has_account'     => false,
            'has_profile'     => false,
            'has_erp_history' => false,
        ], 422);
    }

    try {
        $response = Http::withOptions([
                'verify' => false,
            ])
            ->timeout(8)
            ->withHeaders([
                'X-Storefront-Code' => 'linxen',
            ])
            ->get(
                "{$this->erpBaseUrl}/api/storefront/customers/check-phone",
                ['phone' => $phone]
            );

        if (!$response->ok()) {
            Log::warning('⚠️ [CHECK PHONE ERP HTTP ERROR]', [
                'phone'  => $phone,
                'status' => $response->status(),
            ]);

            return response()->json([
                'has_account'     => false,
                'has_profile'     => false,
                'has_erp_history' => false,
            ]);
        }

        $json = $response->json();

        // =====================================================
        // 2️⃣ MAP ĐÚNG NGHĨA TỪ ERP
        // =====================================================
        return response()->json([
            // có identity trong CRM hay chưa
            'has_profile'     => (bool) ($json['has_identity'] ?? false),

            // có account đăng nhập (member) hay chưa
            'has_account'     => (bool) ($json['has_account'] ?? false),

            // đã từng mua / có lịch sử ERP
            'has_erp_history' => (bool) ($json['has_erp_history'] ?? false),

            // thông tin hiển thị
            'name'            => $json['name'] ?? null,
            'phone'           => $phone,
            'customer_type'   => $json['customer_type'] ?? 'guest',
        ]);

    } catch (Throwable $e) {

        Log::error('🔥 [CHECK PHONE ERP EXCEPTION]', [
            'phone' => $phone,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'has_account'     => false,
            'has_profile'     => false,
            'has_erp_history' => false,
        ]);
    }
}
protected function normalizePhone(string $phone): ?string
{
    $phone = preg_replace('/\D+/', '', $phone);

    if (str_starts_with($phone, '84')) {
        $phone = '0' . substr($phone, 2);
    }

    if (strlen($phone) < 9 || strlen($phone) > 11) {
        return null;
    }

    return $phone;
}


    /**
 * =====================================================
 * 🔐 REGISTER INLINE (CREATE ACCOUNT + AUTO LOGIN)
 * STOREFRONT → ERP AUTH
 * =====================================================
 */
public function registerInline(Request $request): JsonResponse
{
    $data = $request->validate([
        'phone'    => 'required|string|max:20',
        'email'    => 'nullable|email|max:255',
        'password' => 'required|string|min:6',
    ]);

    try {

        $response = Http::withOptions([
                'verify' => false,
            ])
            ->timeout(10)
            ->withHeaders([
                'X-Storefront-Code' => 'linxen',
            ])
            ->post(
                "{$this->erpBaseUrl}/api/storefront/auth/register",
                $data
            );

        if ($response->failed()) {
            Log::error('❌ [REGISTER INLINE ERP FAILED]', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo tài khoản.',
            ], 400);
        }

        $json = $response->json();

        if (!($json['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $json['message'] ?? 'Đăng ký thất bại.',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | ✅ SET SESSION LOGIN STATE (LIN XÉN)
        |--------------------------------------------------------------------------
        | ERP đã auto-login → Storefront cần mirror state
        */
        if (!empty($json['customer'])) {
            session([
                'customer' => [
                    'id'    => $json['customer']['id'] ?? null,
                    'phone' => $json['customer']['phone'] ?? $data['phone'],
                    'name'  => $json['customer']['name'] ?? null,
                    'email' => $json['customer']['email'] ?? null,
                ],
            ]);
        } else {
            // fallback an toàn
            session([
                'customer' => [
                    'phone' => $data['phone'],
                ],
            ]);
        }

        // 👉 dùng cho banner checkout
        session()->flash('just_registered', true);

        return response()->json([
            'success' => true,
        ]);

    } catch (Throwable $e) {

        Log::error('🔥 [REGISTER INLINE EXCEPTION]', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Lỗi hệ thống khi tạo tài khoản.',
        ], 500);
    }
}


    /**
     * =====================================================
     * 🧾 CREATE ORDER
     * STOREFRONT (LIN XÉN) → ERP
     * =====================================================
     */
    public function create(Request $request): JsonResponse
    {
        Log::info('🟡 [LINXEN CHECKOUT RAW REQUEST]', [
            'json' => $request->json()->all(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ VALIDATE
        |--------------------------------------------------------------------------
        */
        $data = $request->validate([
            'storefront'              => 'required|string',

            'customer.name'           => 'required|string|max:255',
            'customer.phone'          => 'required|string|max:50',
            'customer.street'         => 'required|string|max:255',
            'customer.location_id'    => 'required|integer',
            'customer.ward_id'        => 'required|integer',
            'customer.location_name'  => 'required|string|max:255',
            'customer.ward_name'      => 'required|string|max:255',
            'customer.note'           => 'nullable|string|max:255',

            // MEMBER FLOW
            'member.action'           => 'nullable|in:login,register,skip',
            'member.email'            => 'nullable|email|max:255',
            'member.password'         => 'nullable|string|max:255',

            'items'                   => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer',
            'items.*.qty'             => 'required|integer|min:1',
            'items.*.price'           => 'required|numeric|min:0',
            'items.*.note'            => 'nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ BUILD PAYLOAD → ERP
        |--------------------------------------------------------------------------
        */
        $payload = [
            'storefront' => $data['storefront'],

            'customer' => [
                'name'          => $data['customer']['name'],
                'phone'         => $data['customer']['phone'],
                'street'        => $data['customer']['street'],
                'location_id'   => $data['customer']['location_id'],
                'ward_id'       => $data['customer']['ward_id'],
                'location_name' => $data['customer']['location_name'],
                'ward_name'     => $data['customer']['ward_name'],
                'note'          => $data['customer']['note'] ?? null,
            ],

            // 🔑 MEMBER INTENT
            'member' => [
                'action'   => $data['member']['action'] ?? 'skip',
                'email'    => $data['member']['email'] ?? null,
                'password' => $data['member']['password'] ?? null,
            ],

            'items' => collect($data['items'])->map(fn ($i) => [
                'product_id' => (int) $i['product_id'],
                'qty'        => (int) $i['qty'],
                'price'      => (float) $i['price'],
                'note'       => $i['note'] ?? null,
            ])->values()->all(),
        ];

        Log::info('📦 [LINXEN → ERP PAYLOAD]', $payload);

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ CALL ERP CREATE ORDER
        |--------------------------------------------------------------------------
        */
        try {
            $response = Http::withOptions([
                    'verify' => false,
                ])
                ->timeout(15)
                ->post(
                    "{$this->erpBaseUrl}/api/storefront/orders",
                    $payload
                );

            if ($response->failed()) {
                Log::error('❌ [LINXEN → ERP CREATE ORDER FAILED]', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'ERP không tạo được đơn hàng.',
                ], 500);
            }

            $json = $response->json();

            if (!($json['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $json['message'] ?? 'Tạo đơn hàng thất bại.',
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ CLEAR CART
            |--------------------------------------------------------------------------
            */
            session()->forget('cart');

            return response()->json([
                'success'    => true,
                'order_code' => $json['order_code'] ?? null,
                'erp_order'  => $json,
            ]);

        } catch (Throwable $e) {

            Log::error('🔥 [LINXEN CHECKOUT EXCEPTION]', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi tạo đơn hàng.',
            ], 500);
        }
    }
}

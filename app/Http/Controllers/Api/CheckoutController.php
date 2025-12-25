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

public function checkPhone(Request $request): JsonResponse
{
    $request->validate([
        'phone' => 'required|string|max:20',
    ]);

    $phone = trim($request->input('phone'));

    /*
    |--------------------------------------------------------------------------
    | 🔍 CHECK PHONE VIA ERP (SINGLE SOURCE OF TRUTH)
    |--------------------------------------------------------------------------
    | ERP chịu trách nhiệm:
    | - Phone có account website không?
    | - Phone có lịch sử mua hàng không?
    | - Có tên khách hàng không?
    |--------------------------------------------------------------------------
    */
    try {
        $response = Http::withOptions([
                'verify' => false,
            ])
            ->timeout(8)
            ->get(
                "{$this->erpBaseUrl}/api/storefront/customers/check-phone",
                ['phone' => $phone]
            );

        if (!$response->ok()) {
            Log::warning('⚠️ [CHECK PHONE ERP HTTP ERROR]', [
                'phone'  => $phone,
                'status' => $response->status(),
            ]);

            // Fail soft – coi như khách mới
            return response()->json([
                'has_account'      => false,
                'has_profile'      => false,
                'has_erp_history'  => false,
            ]);
        }

        $json = $response->json();

        /*
        |--------------------------------------------------------------------------
        | ERP RESPONSE CONTRACT (EXPECTED)
        |--------------------------------------------------------------------------
        | {
        |   "found": true,
        |   "has_account": true|false,
        |   "has_erp_history": true|false,
        |   "name": "Nguyen Van A"
        | }
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'has_account'      => (bool) ($json['has_account'] ?? false),
            'has_profile'      => false, // storefront KHÔNG quyết profile
            'has_erp_history'  => (bool) ($json['has_erp_history'] ?? false),
            'name'             => $json['name'] ?? null,
        ]);

    } catch (\Throwable $e) {

        Log::error('🔥 [CHECK PHONE ERP EXCEPTION]', [
            'phone' => $phone,
            'error' => $e->getMessage(),
        ]);

        // Fail soft – không chặn checkout
        return response()->json([
            'has_account'      => false,
            'has_profile'      => false,
            'has_erp_history'  => false,
        ]);
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
        /*
        |--------------------------------------------------------------------------
        | 0️⃣ LOG RAW REQUEST (RẤT QUAN TRỌNG ĐỂ DEBUG)
        |--------------------------------------------------------------------------
        */
        Log::info('🟡 [LINXEN CHECKOUT RAW REQUEST]', [
            'json' => $request->json()->all(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Validate dữ liệu từ checkout.js
        |--------------------------------------------------------------------------
        */
        $data = $request->validate([
            'storefront'            => 'required|string',

            'customer.name'         => 'required|string|max:255',
            'customer.phone'        => 'required|string|max:50',
            'customer.street'       => 'required|string|max:255',
            'customer.location_id'  => 'required|integer',
            'customer.ward_id'      => 'required|integer',
            'customer.location_name'=> 'required|string|max:255',
            'customer.ward_name'    => 'required|string|max:255',
            'customer.note'         => 'nullable|string|max:255',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer',
            'items.*.qty'           => 'required|integer|min:1',
            'items.*.price'         => 'required|numeric|min:0',
            'items.*.note'          => 'nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Build payload gửi ERP (ĐÚNG ROUTE ERP)
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
        | 3️⃣ Gọi ERP tạo đơn
        |--------------------------------------------------------------------------
        | ERP ROUTE:
        | POST {ERP_BASE_URL}/api/storefront/orders
        |--------------------------------------------------------------------------
        */
        try {
            $response = Http::withOptions([
        'verify' => false, // 🔥 FIX SSL ERROR 60
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
            | 4️⃣ Clear cart storefront sau khi ERP tạo đơn OK
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

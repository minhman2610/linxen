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
     * 🧾 CREATE ORDER
     * STOREFRONT (LIN XÉN) → ERP
     * =====================================================
     */
    public function create(Request $request)
{
    dd('HIT CHECKOUT CONTROLLER');
}

    public function create1(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | 0️⃣ LOG RAW REQUEST (DEBUG)
        |--------------------------------------------------------------------------
        */
        Log::info('🟡 [LINXEN CHECKOUT RAW REQUEST]', [
            'all'  => $request->all(),
            'json' => $request->json()->all(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Validate customer info
        |--------------------------------------------------------------------------
        */
        $data = $request->validate([
            'customer.name'        => 'required|string|max:255',
            'customer.phone'       => 'required|string|max:50',
            'customer.location_id' => 'required|integer',
            'customer.ward_id'     => 'required|integer',
            'customer.street'      => 'required|string|max:255',
            'customer.note'        => 'nullable|string|max:255',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Lấy cart từ session storefront
        |--------------------------------------------------------------------------
        */
        $cart = session('cart', []);

        if (empty($cart) || !is_array($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Giỏ hàng trống, không thể tạo đơn.',
            ], 422);
        }

        Log::info('🛒 [LINXEN STOREFRONT CART]', [
            'cart' => $cart,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Build items gửi ERP (DÙNG KV SKU)
        |--------------------------------------------------------------------------
        */
        $items = collect($cart)
            ->map(function ($item) {
                return [
                    // 🔑 BẮT BUỘC: SKU KiotViet
                    'kv_sku' => $item['sku'] ?? null,
                    'qty'    => (int) ($item['qty'] ?? 0),
                    'price'  => (float) ($item['price'] ?? 0),
                    'note'   => $item['note'] ?? null,
                ];
            })
            ->filter(fn ($i) => !empty($i['kv_sku']) && $i['qty'] > 0)
            ->values()
            ->all();

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm trong giỏ hàng không hợp lệ.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Build payload gửi ERP
        |--------------------------------------------------------------------------
        */
        $payload = [
            'channel' => 'linxen_web',

            'customer' => [
                'name'    => $data['customer']['name'],
                'phone'   => $data['customer']['phone'],
                'address' => trim(
                    $data['customer']['street']
                    . ', ward_id=' . $data['customer']['ward_id']
                    . ', location_id=' . $data['customer']['location_id']
                ),
            ],

            'items' => $items,
        ];

        Log::info('📦 [LINXEN → ERP PAYLOAD]', [
            'payload' => $payload,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Gọi ERP tạo đơn
        |--------------------------------------------------------------------------
        */
        try {
            $response = Http::withHeaders([
                    'X-ERP-TOKEN'   => config('services.erp.api_token'),
                    'X-STOREFRONT' => config('services.erp.storefront'),
                    'Accept'       => 'application/json',
                ])
                ->withOptions([
                    'verify' => false, // tránh lỗi SSL local
                ])
                ->timeout(15)
                ->post(
                    "{$this->erpBaseUrl}/api/sales/pos/orders",
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
            | 6️⃣ Clear cart sau khi tạo đơn thành công
            |--------------------------------------------------------------------------
            */
            session()->forget('cart');

            return response()->json([
                'success'    => true,
                'order_code' => $json['kv_order']['orderCode'] ?? null,
                'kv_order'   => $json['kv_order'] ?? null,
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

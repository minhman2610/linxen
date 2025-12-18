<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
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
     * LINXEN Storefront → ERP
     * =====================================================
     */
    public function create(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Validate dữ liệu checkout (chỉ customer info)
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

        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Giỏ hàng trống, không thể tạo đơn.',
            ], 422);
        }

        Log::info('🛒 STOREFRONT CART RAW', [
            'cart' => $cart,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Build items gửi ERP
        |--------------------------------------------------------------------------
        */
        $items = collect($cart)
            ->map(function ($item) {
                return [
                    // ⚠️ BẮT BUỘC là SKU KiotViet
                    'kv_sku'  => $item['sku'] ?? $item['kv_sku'] ?? null,
                    'qty'     => (int) ($item['qty'] ?? 1),
                    'price'   => (float) ($item['price'] ?? 0),
                    'note'    => $item['note'] ?? null,
                    'rs_code' => $item['rs_code'] ?? null,
                ];
            })
            ->filter(fn ($i) => !empty($i['kv_sku']))
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

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Gọi ERP tạo đơn (có auth storefront)
        |--------------------------------------------------------------------------
        */
        try {
            $response = Http::withHeaders([
                    'X-ERP-TOKEN'  => config('services.erp.api_token'),
                    'X-STOREFRONT'=> config('services.erp.storefront'),
                    'Accept'      => 'application/json',
                ])
                ->withOptions([
                    'verify' => false, // fix curl error 60
                ])
                ->timeout(15)
                ->post(
                    "{$this->erpBaseUrl}/api/sales/pos/orders",
                    $payload
                );

            if ($response->failed()) {
                Log::error('❌ STOREFRONT → ERP create order failed', [
                    'url'     => "{$this->erpBaseUrl}/api/sales/pos/orders",
                    'payload' => $payload,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
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
            Log::error('🔥 CheckoutController exception', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống khi tạo đơn hàng.',
            ], 500);
        }
    }
}

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
     * Storefront → ERP (/api/sales/pos/orders)
     * =====================================================
     */
    public function create(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Validate dữ liệu checkout
        |--------------------------------------------------------------------------
        */
        $data = $request->validate([
            'customer.name'        => 'required|string|max:255',
            'customer.phone'       => 'required|string|max:50',
            'customer.location_id' => 'required|integer',
            'customer.ward_id'     => 'required|integer',
            'customer.street'      => 'required|string|max:255',
            'customer.note'        => 'nullable|string|max:255',

            // items KHÔNG gửi từ frontend
            // BE ERP sẽ xử lý dựa trên session/cart mapping
            'items'                => 'nullable|array',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Build payload gửi ERP (khớp createOrder ERP)
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

            // ❗ ERP sẽ map items từ cart/session nội bộ
            'items' => $data['items'] ?? [],
        ];

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Gọi ERP tạo đơn
        |--------------------------------------------------------------------------
        */
        try {
            $response = Http::withOptions([
                    'verify' => false, // fix curl error 60
                ])
                ->timeout(12)
                ->post(
                    "{$this->erpBaseUrl}/api/sales/pos/orders",
                    $payload
                );

            if ($response->failed()) {
                Log::error('❌ CheckoutController ERP create order failed', [
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

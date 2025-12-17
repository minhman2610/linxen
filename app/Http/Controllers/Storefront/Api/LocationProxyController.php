<?php

namespace App\Http\Controllers\Storefront\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Throwable;

class LocationProxyController extends Controller
{
    protected string $erpBaseUrl;

    public function __construct()
    {
        $this->erpBaseUrl = rtrim(config('services.erp.base_url'), '/');
    }

    /**
     * =====================================================
     * 📍 Proxy locations từ ERP
     * =====================================================
     */
    public function locations(Request $request): JsonResponse
    {
        $url = "{$this->erpBaseUrl}/api/locations";

        try {
            $response = Http::withOptions([
                    'verify' => false, // 🔧 fix curl 60
                ])
                ->timeout(8)
                ->get($url, $request->query());

            if ($response->failed()) {
                Log::error('❌ ERP locations API failed', [
                    'url'      => $url,
                    'query'    => $request->query(),
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return response()->json([
                    'error'   => true,
                    'message' => 'ERP trả về lỗi khi lấy danh sách khu vực.',
                ], 500);
            }

            return response()->json($response->json());

        } catch (Throwable $e) {
            Log::error('🔥 ERP locations API exception', [
                'url'    => $url,
                'query'  => $request->query(),
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => true,
                'message' => 'Không thể kết nối ERP (locations).',
            ], 500);
        }
    }

    /**
     * =====================================================
     * 🏠 Proxy wards theo location từ ERP
     * =====================================================
     */
    public function wards(Request $request, int $locationId): JsonResponse
    {
        $url = "{$this->erpBaseUrl}/api/locations/{$locationId}/wards";

        try {
            $response = Http::withOptions([
                    'verify' => false, // 🔧 fix curl 60
                ])
                ->timeout(8)
                ->get($url, $request->query());

            if ($response->failed()) {
                Log::error('❌ ERP wards API failed', [
                    'url'        => $url,
                    'locationId' => $locationId,
                    'query'      => $request->query(),
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                ]);

                return response()->json([
                    'error'   => true,
                    'message' => 'ERP trả về lỗi khi lấy danh sách phường/xã.',
                ], 500);
            }

            return response()->json($response->json());

        } catch (Throwable $e) {
            Log::error('🔥 ERP wards API exception', [
                'url'        => $url,
                'locationId' => $locationId,
                'query'      => $request->query(),
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => true,
                'message' => 'Không thể kết nối ERP (wards).',
            ], 500);
        }
    }
}

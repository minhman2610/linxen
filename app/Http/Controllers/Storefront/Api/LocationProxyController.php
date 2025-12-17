<?php

namespace App\Http\Controllers\Storefront\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

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
        $response = Http::withOptions([
                // 🔧 FIX curl error 60 (SSL certificate problem)
                'verify' => false,
            ])
            ->timeout(8)
            ->get(
                "{$this->erpBaseUrl}/api/locations",
                $request->query()
            );

        if ($response->failed()) {
            return response()->json([
                'error'   => true,
                'message' => 'Không lấy được dữ liệu khu vực từ ERP.',
                'status'  => $response->status(),
            ], 500);
        }

        return response()->json($response->json());
    }

    /**
     * =====================================================
     * 🏠 Proxy wards theo location từ ERP
     * =====================================================
     */
    public function wards(Request $request, int $locationId): JsonResponse
    {
        $response = Http::withOptions([
                // 🔧 FIX curl error 60 (SSL certificate problem)
                'verify' => false,
            ])
            ->timeout(8)
            ->get(
                "{$this->erpBaseUrl}/api/locations/{$locationId}/wards",
                $request->query()
            );

        if ($response->failed()) {
            return response()->json([
                'error'   => true,
                'message' => 'Không lấy được dữ liệu phường/xã từ ERP.',
                'status'  => $response->status(),
            ], 500);
        }

        return response()->json($response->json());
    }
}

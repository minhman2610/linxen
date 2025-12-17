<?php

namespace App\Services\ERP;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpStorefrontApi
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.erp.base_url'), '/');
        $this->token   = config('services.erp.token');
    }

    /**
     * =====================================================
     * 🏠 HOME DATA
     * =====================================================
     */
    public function home(string $brand): array
    {
        $data = $this->get("/api/storefront/{$brand}/home");

        // Normalize để frontend dùng ổn định
        return [
            'hero'     => $data['hero'] ?? null,
            'products' => $data['products'] ?? [],
        ];
    }

    /**
     * =====================================================
     * 👗 PRODUCT DETAIL
     * slug format: ten-san-pham-product_id
     * =====================================================
     */
    public function product(string $brand, string $slug): ?array
    {
        // Brand chỉ là context hiển thị, không dùng để filter
        $data = $this->get("/api/storefront/{$brand}/product/{$slug}");

        // Không có dữ liệu → coi như không tồn tại
        if (empty($data) || !is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * =====================================================
     * 📦 COLLECTION
     * =====================================================
     */
    public function collection(string $brand, string $slug): array
    {
        $data = $this->get("/api/storefront/{$brand}/collection/{$slug}");

        return [
            'collection' => $data['collection'] ?? null,
            'products'   => $data['products'] ?? [],
        ];
    }

    /**
     * =====================================================
     * 🔌 CORE REQUEST
     *  - Internal ERP
     *  - SSL verify OFF
     * =====================================================
     */
    protected function get(string $uri): array
    {
        $url = $this->baseUrl . $uri;

        try {
            $res = Http::withToken($this->token)
                ->withOptions([
                    'verify'  => false, // 🔥 internal ERP – bỏ SSL verify
                    'timeout' => 8,
                ])
                ->acceptJson()
                ->get($url);

            if ($res->failed()) {
                Log::error('[LINXEN][ERP_API_FAIL]', [
                    'url'    => $url,
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);

                return [];
            }

            $json = $res->json();

            if (!is_array($json)) {
                Log::error('[LINXEN][ERP_API_INVALID_JSON]', [
                    'url'  => $url,
                    'body' => $res->body(),
                ]);
                return [];
            }

            return $json;

        } catch (\Throwable $e) {

            Log::error('[LINXEN][ERP_API_EXCEPTION]', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}

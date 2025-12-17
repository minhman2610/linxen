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
        return $this->get("/api/storefront/{$brand}/home");
    }

    /**
     * =====================================================
     * 👗 PRODUCT DETAIL
     * =====================================================
     */
    public function product(string $brand, string $slug): ?array
    {
        return $this->get("/api/storefront/{$brand}/product/{$slug}");
    }

    /**
     * =====================================================
     * 📦 COLLECTION
     * =====================================================
     */
    public function collection(string $brand, string $slug): ?array
    {
        return $this->get("/api/storefront/{$brand}/collection/{$slug}");
    }

    /**
     * =====================================================
     * 🔌 CORE REQUEST (SSL VERIFY OFF – INTERNAL USE)
     * =====================================================
     */
    protected function get(string $uri): array
    {
        $url = $this->baseUrl . $uri;

        try {
            $res = Http::withToken($this->token)
                ->withOptions([
                    'verify' => false,   // 🔥 CHỐT: bỏ verify SSL
                    'timeout' => 5,
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

            return $res->json() ?? [];

        } catch (\Throwable $e) {

            Log::error('[LINXEN][ERP_API_EXCEPTION]', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}

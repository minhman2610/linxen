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

        return [
            'hero'     => $data['hero'] ?? null,
            'products' => $data['products'] ?? [],
        ];
    }

    /**
     * =====================================================
     * 👗 PRODUCT DETAIL
     * =====================================================
     */
    public function product(string $brand, string $slug): ?array
    {
        $data = $this->get("/api/storefront/{$brand}/product/{$slug}");

        return empty($data) || !is_array($data) ? null : $data;
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
     * 👤 CUSTOMER – PROFILE
     * =====================================================
     */
    public function customerProfile(): array
    {
        return $this->fetch('/api/storefront/customer/profile');
    }

    public function updateCustomerProfile(array $payload): array
    {
        return $this->post('/api/storefront/customer/update-profile', $payload);
    }

    /**
 * =====================================================
 * 📍 CUSTOMER – ADDRESSES
 * =====================================================
 */
public function customerAddresses(): array
{
    $res = $this->fetch('/api/storefront/customer/addresses');

    if (!is_array($res) || !($res['success'] ?? false)) {
        return [];
    }

    $data = $res['data'] ?? [];

    // ERP trả data không phải array → ép về []
    if (!is_array($data)) {
        return [];
    }

    return $data;
}
/**
 * =====================================================
 * 🗑 CUSTOMER – DELETE ADDRESS
 * =====================================================
 */
public function deleteCustomerAddress(int $addressId): array
{
    $res = $this->post("/api/storefront/customer/addresses/{$addressId}/delete");

    if (!is_array($res)) {
        return [
            'success' => false,
            'message' => 'ERP không phản hồi hợp lệ.',
        ];
    }

    return [
        'success' => (bool) ($res['success'] ?? false),
        'message' => $res['message'] ?? null,
    ];
}
/**
 * =====================================================
 * ⭐ CUSTOMER – SET DEFAULT ADDRESS
 * =====================================================
 */
public function setDefaultCustomerAddress(int $addressId): array
{
    $res = $this->post("/api/storefront/customer/addresses/{$addressId}/default");

    if (!is_array($res)) {
        return [
            'success' => false,
            'message' => 'ERP không phản hồi hợp lệ.',
        ];
    }

    return [
        'success' => (bool) ($res['success'] ?? false),
        'message' => $res['message'] ?? null,
    ];
}


public function createCustomerAddress(array $payload): array
{
    $res = $this->post('/api/storefront/customer/addresses', $payload);

    /**
     * Chuẩn hoá response để controller xử lý thống nhất
     */
    if (!is_array($res)) {
        return [
            'success' => false,
            'message' => 'Hệ thống ERP không phản hồi hợp lệ.',
        ];
    }

    return [
        'success' => (bool) ($res['success'] ?? false),
        'message' => $res['message'] ?? null,
        'data'    => $res['data'] ?? null,
    ];
}


    /**
     * =====================================================
     * 🔓 PUBLIC FETCH (GET)
     * =====================================================
     */
    public function fetch(string $uri): array
    {
        return $this->get($uri);
    }

    /**
     * =====================================================
     * 🔐 CORE GET REQUEST
     * =====================================================
     */
    protected function get(string $uri): array
    {
        $url = $this->baseUrl . $uri;

        try {
            $res = Http::withToken($this->token)
                ->withHeaders($this->buildHeaders())
                ->withOptions([
                    'verify'  => false, // internal ERP
                    'timeout' => 8,
                ])
                ->acceptJson()
                ->get($url);

            if ($res->failed()) {
                Log::error('[LINXEN][ERP_API_GET_FAIL]', [
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
            Log::error('[LINXEN][ERP_API_GET_EXCEPTION]', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * =====================================================
     * 🔐 CORE POST REQUEST
     * =====================================================
     */
    protected function post(string $uri, array $payload = []): array
    {
        $url = $this->baseUrl . $uri;

        try {
            $res = Http::withToken($this->token)
                ->withHeaders($this->buildHeaders())
                ->withOptions([
                    'verify'  => false,
                    'timeout' => 8,
                ])
                ->acceptJson()
                ->post($url, $payload);

            if ($res->failed()) {
                Log::error('[LINXEN][ERP_API_POST_FAIL]', [
                    'url'    => $url,
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);
                return [];
            }

            return $res->json() ?? [];

        } catch (\Throwable $e) {
            Log::error('[LINXEN][ERP_API_POST_EXCEPTION]', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * =====================================================
     * 🧠 BUILD COMMON HEADERS
     * =====================================================
     */
    protected function buildHeaders(): array
    {
        return array_filter([
            // Identity customer (source of truth: storefront session)
            'X-Customer-Phone' => session('customer.phone'),

            // Optional – để ERP log context
            'X-Storefront-Code' => 'LINXEN',
        ]);
    }
}

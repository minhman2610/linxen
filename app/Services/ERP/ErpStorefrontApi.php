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
 * 👤 CUSTOMER PROFILE (ERP)
 * =====================================================
 * ERP response:
 * {
 *   success: true|false,
 *   data?: array,
 *   message?: string
 * }
 */
public function customerProfile(): array
{
    try {
        $res = $this->fetch('/api/storefront/customer/profile');

        // ❌ ERP fail / format sai
        if (!is_array($res) || !array_key_exists('success', $res)) {
            return [
                'success' => false,
                'message' => 'Invalid ERP response',
            ];
        }

        // ❌ ERP báo lỗi (401, 400, ...)
        if (!($res['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $res['message'] ?? 'Không thể tải thông tin tài khoản.',
            ];
        }

        // ✅ Thành công
        return [
            'success' => true,
            'data'    => is_array($res['data'] ?? null) ? $res['data'] : [],
        ];

    } catch (\Throwable $e) {

        \Log::error('[LINXEN][CUSTOMER_PROFILE_FAIL]', [
            'error' => $e->getMessage(),
        ]);

        return [
            'success' => false,
            'message' => 'Hệ thống đang bận. Vui lòng thử lại sau.',
        ];
    }
}


    /**
 * =====================================================
 * ✏️ UPDATE CUSTOMER PROFILE (ERP)
 * =====================================================
 * ERP route:
 * POST /api/storefront/customer/profile
 *
 * ERP response:
 * {
 *   success: true|false,
 *   message?: string
 * }
 */
public function updateCustomerProfile(array $payload): array
{
    try {
        $res = $this->post('/api/storefront/customer/profile', $payload);

        // ❌ ERP fail / format sai
        if (!is_array($res) || !($res['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $res['message'] ?? 'Không thể cập nhật thông tin cá nhân.',
            ];
        }

        // ✅ Thành công
        return [
            'success' => true,
            'message' => $res['message'] ?? 'Cập nhật thông tin thành công.',
        ];

    } catch (\Throwable $e) {

        \Log::error('[LINXEN][UPDATE_PROFILE_FAIL]', [
            'error' => $e->getMessage(),
        ]);

        return [
            'success' => false,
            'message' => 'Hệ thống đang bận. Vui lòng thử lại sau.',
        ];
    }
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
public function updateCustomerAddress(int $id, array $payload): array
{
    return $this->post("/api/storefront/customer/addresses/{$id}/update", $payload);
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
 * 🧾 ORDERS LIST (STORE ACCOUNT)
 * =====================================================
 * ERP route:
 * GET /api/storefront/orders?phone=...
 */
public function orders(?string $phone = null): array
{
    try {
        $query = [];

        if ($phone) {
            $query['phone'] = $phone;
        }

        $res = $this->get('/api/storefront/orders' . (!empty($query) ? '?' . http_build_query($query) : ''));

        if (!is_array($res) || !($res['success'] ?? false)) {
            return [
                'success' => false,
                'orders'  => [],
                'message' => $res['message'] ?? 'Không thể tải danh sách đơn hàng.',
            ];
        }

        return [
            'success' => true,
            'orders'  => is_array($res['orders'] ?? null) ? $res['orders'] : [],
        ];

    } catch (\Throwable $e) {
        Log::error('[LINXEN][ORDERS_LIST_FAIL]', [
            'error' => $e->getMessage(),
        ]);

        return [
            'success' => false,
            'orders'  => [],
            'message' => 'Hệ thống đang bận. Vui lòng thử lại sau.',
        ];
    }
}


/**
 * =====================================================
 * 📦 ORDER DETAIL
 * =====================================================
 * ERP route:
 * GET /api/storefront/orders/{code}
 */
public function orderDetail(string $code): array
{
    try {
        $res = $this->get("/api/storefront/orders/{$code}");

        if (!is_array($res) || !($res['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $res['message'] ?? 'Không tìm thấy đơn hàng.',
            ];
        }

        if (empty($res['order']) || !is_array($res['order'])) {
            return [
                'success' => false,
                'message' => 'Dữ liệu đơn hàng không hợp lệ.',
            ];
        }

        return [
            'success' => true,
            'order'   => $res['order'],
        ];

    } catch (\Throwable $e) {
        Log::error('[LINXEN][ORDER_DETAIL_FAIL]', [
            'code'  => $code,
            'error' => $e->getMessage(),
        ]);

        return [
            'success' => false,
            'message' => 'Hệ thống đang bận. Vui lòng thử lại sau.',
        ];
    }
}
/**
 * =====================================================
 * 📦 COLLECTION (STORE CATEGORY) – FAST & SAFE (FIXED)
 * =====================================================
 */
public function collection(
    string $brand,
    string $slug,
    int $page = 1,
    int $limit = 24
): array {
    // 🔒 Normalize params
    $page  = max($page, 1);
    $limit = min(max($limit, 1), 48);

    // 🔑 Cache key (PAGE ĐÃ ĐƯỢC TÁCH RIÊNG)
    $cacheKey = "storefront:collection:{$brand}:{$slug}:{$page}:{$limit}";

    return \Cache::remember($cacheKey, now()->addMinutes(5), function () use (
        $brand,
        $slug,
        $page,
        $limit
    ) {

        // =====================================================
        // 1️⃣ CALL ERP API (3mg.ai – PAGINATED)
        // =====================================================
        $data = $this->get(
            "/api/storefront/{$brand}/collection/{$slug}",
            [
                'page'  => $page,
                'limit'=> $limit,
            ]
        );

        if (
            empty($data)
            || !is_array($data)
            || empty($data['collection'])
        ) {
            return [
                'collection' => null,
                'products'   => [],
                'meta'       => null,
            ];
        }

        // =====================================================
        // 2️⃣ COLLECTION META (PASS-THROUGH)
        // =====================================================
        $collection = [
            'name'        => $data['collection']['name'] ?? 'Bộ sưu tập',
            'description' => $data['collection']['description'] ?? null,
            'hero'        => $data['collection']['hero'] ?? null,
            'slug'        => $slug,
        ];

        // =====================================================
        // 3️⃣ PRODUCTS – ROOT KEY `thumb` (QUAN TRỌNG)
        // =====================================================
        $products = collect($data['products'] ?? [])
            ->map(function ($p) {

                // ✅ ĐÚNG CẤU TRÚC DATA ĐANG CHẠY
                $thumb = $p['thumb'] ?? null;

                if (!$thumb) {
                    return null;
                }

                return [
                    'product_id' => $p['product_id'] ?? null,
                    'name'       => $p['name'] ?? null,
                    'slug'       => $p['slug'] ?? null,
                    'price'      => $p['price'] ?? null,
                    'available'  => $p['available'] ?? 0,
                    'colors'     => $p['colors'] ?? [],
                    'thumb'      => $thumb,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        // =====================================================
        // 4️⃣ META PAGINATION (PASS-THROUGH)
        // =====================================================
        $meta = $data['meta'] ?? null;

        return [
            'collection' => $collection,
            'products'   => $products,
            'meta'       => $meta,
        ];
    });
}




    /**
 * =====================================================
 * 🧠 BUILD COMMON HEADERS
 * =====================================================
 */
protected function buildHeaders(): array
{
    $customer = session('customer');

    return array_filter([
        // ✅ ĐÚNG: lấy từ session array
        'X-Customer-Phone'   => $customer['phone'] ?? null,

        // Context storefront
        'X-Storefront-Code' => 'LINXEN',
    ]);
}

}

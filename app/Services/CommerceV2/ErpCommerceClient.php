<?php

namespace App\Services\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ErpCommerceClient
{
    public function listing(
        int $limit = 8,
        ?string $cursor = null
    ): array {
        return $this->get(
            '/catalog/products',
            array_filter([
                'limit' => max(1, min(12, $limit)),
                'cursor' => $cursor,
            ], fn ($value) => $value !== null && $value !== '')
        );
    }

    public function product(string $reference): array
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new CommerceV2ClientException(
                'Sản phẩm không hợp lệ.',
                404,
                'storefront_product_reference_invalid'
            );
        }

        return $this->get(
            '/catalog/products/' . rawurlencode($reference),
            [],
            20
        );
    }

    public function search(
        string $query,
        int $limit = 8,
        ?string $cursor = null
    ): array {
        return $this->get(
            '/catalog/search',
            array_filter([
                'q' => $query,
                'limit' => max(1, min(12, $limit)),
                'cursor' => $cursor,
            ], fn ($value) => $value !== null && $value !== '')
        );
    }

    public function collections(): array
    {
        return $this->get(
            '/catalog/collections',
            [],
            30
        );
    }

    public function collection(
        string $slug,
        int $limit = 8,
        ?string $cursor = null
    ): array {
        return $this->get(
            '/catalog/collections/' . rawurlencode($slug),
            array_filter([
                'limit' => max(1, min(12, $limit)),
                'cursor' => $cursor,
            ], fn ($value) => $value !== null && $value !== '')
        );
    }


    public function validateCart(array $items): array
    {
        return $this->request(
            'POST',
            '/cart/validate',
            ['items' => array_values($items)]
        );
    }

    public function exchangeCustomerTicket(string $ticket): array
    {
        return $this->request(
            'POST',
            '/customer/auth/exchange',
            ['ticket' => $ticket]
        );
    }

    public function customerAccount(string $customerToken): array
    {
        return $this->request(
            'GET',
            '/customer/account',
            [],
            $customerToken
        );
    }

    public function logoutCustomer(string $customerToken): array
    {
        return $this->request(
            'DELETE',
            '/customer/auth/session',
            [],
            $customerToken
        );
    }

    public function configurationStatus(): array
    {
        $baseUrl = $this->baseUrl();
        $token = $this->token();
        $site = $this->site();

        return [
            'enabled' => (bool) config(
                'commerce_v2.enabled',
                true
            ),
            'base_url_configured' => $baseUrl !== '',
            'base_url_https' => Str::startsWith(
                $baseUrl,
                'https://'
            ),
            'site' => $site,
            'site_configured' => $site !== '',
            'token_configured' => $token !== '',
            'token_length' => strlen($token),
            'cache_store' => (string) config(
                'commerce_v2.cache_store',
                'file'
            ),
        ];
    }


    protected function request(
        string $method,
        string $path,
        array $payload = [],
        ?string $customerToken = null
    ): array {
        $this->assertConfigured();

        $requestId = (string) Str::uuid();

        try {
            $http = Http::acceptJson()
                ->withToken($this->token())
                ->withHeaders(array_filter([
                    'X-Commerce-Site' => $this->site(),
                    'X-Commerce-Customer-Token' => trim(
                        (string) $customerToken
                    ),
                    'X-Request-ID' => $requestId,
                    'User-Agent' => 'LinXen-Storefront-V2/1.1',
                ]))
                ->connectTimeout(max(
                    1,
                    (int) config(
                        'commerce_v2.connect_timeout_seconds',
                        3
                    )
                ))
                ->timeout(max(
                    2,
                    (int) config(
                        'commerce_v2.timeout_seconds',
                        8
                    )
                ));

            $response = match (strtoupper($method)) {
                'GET' => $http->get($this->url($path), $payload),
                'POST' => $http->post($this->url($path), $payload),
                'DELETE' => $http->delete($this->url($path), $payload),
                default => throw new CommerceV2ClientException(
                    'Commerce HTTP method không hợp lệ.',
                    500,
                    'storefront_erp_method_invalid'
                ),
            };
        } catch (CommerceV2ClientException $e) {
            throw $e;
        } catch (ConnectionException $e) {
            throw new CommerceV2ClientException(
                'Không thể kết nối hệ thống Commerce.',
                503,
                'storefront_erp_connection_failed',
                ['request_id' => $requestId],
                $e
            );
        } catch (Throwable $e) {
            throw new CommerceV2ClientException(
                'Hệ thống Commerce đang bận.',
                503,
                'storefront_erp_request_failed',
                ['request_id' => $requestId],
                $e
            );
        }

        return $this->decodeResponse(
            $response,
            $requestId
        );
    }

    protected function get(
        string $path,
        array $query = [],
        ?int $freshSeconds = null
    ): array {
        $this->assertConfigured();

        $freshSeconds ??= max(
            1,
            (int) config(
                'commerce_v2.fresh_cache_seconds',
                10
            )
        );
        $staleSeconds = max(
            $freshSeconds,
            (int) config(
                'commerce_v2.stale_cache_seconds',
                300
            )
        );

        $cache = Cache::store(
            (string) config(
                'commerce_v2.cache_store',
                'file'
            )
        );
        $key = $this->cacheKey($path, $query);
        $staleKey = $key . ':stale';

        $fresh = $cache->get($key);

        if (is_array($fresh)) {
            $fresh['_storefront_cache'] = 'fresh';

            return $fresh;
        }

        try {
            $payload = $this->performGet($path, $query);
            $cache->put($key, $payload, $freshSeconds);
            $cache->put($staleKey, $payload, $staleSeconds);

            $payload['_storefront_cache'] = 'origin';

            return $payload;
        } catch (CommerceV2ClientException $e) {
            $stale = $cache->get($staleKey);

            if (is_array($stale) && $e->httpStatus >= 500) {
                $stale['_storefront_cache'] = 'stale';
                $stale['_storefront_warning'] = [
                    'code' => $e->errorCode,
                    'message' => $e->getMessage(),
                ];

                return $stale;
            }

            throw $e;
        }
    }

    protected function performGet(
        string $path,
        array $query
    ): array {
        $url = $this->url($path);
        $requestId = (string) Str::uuid();

        try {
            $response = Http::acceptJson()
                ->withToken($this->token())
                ->withHeaders([
                    'X-Commerce-Site' => $this->site(),
                    'X-Request-ID' => $requestId,
                    'User-Agent' => 'LinXen-Storefront-V2/1.0',
                ])
                ->connectTimeout(max(
                    1,
                    (int) config(
                        'commerce_v2.connect_timeout_seconds',
                        3
                    )
                ))
                ->timeout(max(
                    2,
                    (int) config(
                        'commerce_v2.timeout_seconds',
                        8
                    )
                ))
                ->retry(
                    max(
                        1,
                        (int) config(
                            'commerce_v2.retry_times',
                            2
                        )
                    ),
                    max(
                        0,
                        (int) config(
                            'commerce_v2.retry_sleep_ms',
                            250
                        )
                    ),
                    throw: false
                )
                ->get($url, $query);
        } catch (ConnectionException $e) {
            throw new CommerceV2ClientException(
                'Không thể kết nối hệ thống sản phẩm.',
                503,
                'storefront_erp_connection_failed',
                [
                    'request_id' => $requestId,
                ],
                $e
            );
        } catch (Throwable $e) {
            throw new CommerceV2ClientException(
                'Hệ thống sản phẩm đang bận.',
                503,
                'storefront_erp_request_failed',
                [
                    'request_id' => $requestId,
                ],
                $e
            );
        }

        return $this->decodeResponse(
            $response,
            $requestId
        );
    }

    protected function decodeResponse(
        Response $response,
        string $requestId
    ): array {
        $json = $response->json();

        if (! is_array($json)) {
            throw new CommerceV2ClientException(
                'Hệ thống sản phẩm trả dữ liệu không hợp lệ.',
                502,
                'storefront_erp_invalid_json',
                [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                ]
            );
        }

        if (
            $response->successful()
            && ($json['ok'] ?? false) === true
            && is_array($json['data'] ?? null)
        ) {
            return [
                'data' => $json['data'],
                'meta' => is_array($json['meta'] ?? null)
                    ? $json['meta']
                    : [],
                'request_id' => (string) (
                    data_get($json, 'meta.request_id')
                    ?: $response->header('X-Request-ID')
                    ?: $requestId
                ),
            ];
        }

        $status = $response->status();
        $code = (string) data_get(
            $json,
            'error.code',
            'storefront_erp_error'
        );
        $message = (string) data_get(
            $json,
            'error.message',
            'Không thể tải dữ liệu sản phẩm.'
        );

        throw new CommerceV2ClientException(
            $message,
            $status >= 400 && $status <= 599
                ? $status
                : 502,
            $code !== '' ? $code : 'storefront_erp_error',
            [
                'request_id' => data_get(
                    $json,
                    'meta.request_id',
                    $requestId
                ),
                'status' => $status,
            ]
        );
    }

    protected function assertConfigured(): void
    {
        if (! (bool) config('commerce_v2.enabled', true)) {
            throw new CommerceV2ClientException(
                'Storefront V2 đang tạm tắt.',
                503,
                'storefront_v2_disabled'
            );
        }

        if ($this->baseUrl() === '') {
            throw new CommerceV2ClientException(
                'Chưa cấu hình ERP Commerce V2 URL.',
                503,
                'storefront_erp_base_url_missing'
            );
        }

        if (
            app()->environment('production')
            && ! Str::startsWith(
                $this->baseUrl(),
                'https://'
            )
        ) {
            throw new CommerceV2ClientException(
                'ERP Commerce V2 URL phải dùng HTTPS.',
                503,
                'storefront_erp_https_required'
            );
        }

        if ($this->site() === '') {
            throw new CommerceV2ClientException(
                'Chưa cấu hình Commerce site.',
                503,
                'storefront_erp_site_missing'
            );
        }

        if ($this->token() === '') {
            throw new CommerceV2ClientException(
                'Chưa cấu hình Commerce token.',
                503,
                'storefront_erp_token_missing'
            );
        }
    }

    protected function url(string $path): string
    {
        return $this->baseUrl()
            . '/sites/'
            . rawurlencode($this->site())
            . '/'
            . ltrim($path, '/');
    }

    protected function baseUrl(): string
    {
        return rtrim(
            trim((string) config(
                'commerce_v2.base_url',
                ''
            )),
            '/'
        );
    }

    protected function site(): string
    {
        return trim((string) config(
            'commerce_v2.site',
            'linxen'
        ));
    }

    protected function token(): string
    {
        return trim((string) config(
            'commerce_v2.token',
            ''
        ));
    }

    protected function cacheKey(
        string $path,
        array $query
    ): string {
        ksort($query);

        return 'linxen:commerce_v2:'
            . hash('sha256', json_encode([
                'site' => $this->site(),
                'path' => $path,
                'query' => $query,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }
}
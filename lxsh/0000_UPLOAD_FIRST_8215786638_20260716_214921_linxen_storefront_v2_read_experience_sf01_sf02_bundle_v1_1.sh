#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_storefront_v2_read_experience_sf01_sf02_bundle_v1_1'
ROUTE_MARKER_START='/* AI_PATCH_LINXEN_STOREFRONT_V2_ROUTES_V1_START */'
ROUTE_MARKER_END='/* AI_PATCH_LINXEN_STOREFRONT_V2_ROUTES_V1_END */'
ENV_MARKER_START='# AI_PATCH_LINXEN_COMMERCE_V2_ENV_V1_START'
ENV_MARKER_END='# AI_PATCH_LINXEN_COMMERCE_V2_ENV_V1_END'
COMMAND_MARKER_START='/* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_START */'
COMMAND_MARKER_END='/* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_END */'

BOOTSTRAP='bootstrap/app.php'
CONFIG='config/commerce_v2.php'
EXCEPTION='app/Exceptions/CommerceV2/CommerceV2ClientException.php'
CLIENT='app/Services/CommerceV2/ErpCommerceClient.php'
PRESENTER='app/Services/CommerceV2/CommerceV2Presenter.php'
CONTROLLER='app/Http/Controllers/CommerceV2/CatalogPageController.php'
SMOKE_COMMAND='app/Console/Commands/CommerceV2SmokeCommand.php'
ROUTE_FILE='routes/commerce_v2.php'
WEB_ROUTES='routes/web.php'
ENV_EXAMPLE='.env.example'

LAYOUT='resources/views/commerce_v2/layouts/app.blade.php'
PRODUCT_CARD='resources/views/commerce_v2/partials/product-card.blade.php'
PAGINATION='resources/views/commerce_v2/partials/pagination.blade.php'
HOME_VIEW='resources/views/commerce_v2/pages/home.blade.php'
SHOP_VIEW='resources/views/commerce_v2/pages/shop.blade.php'
PRODUCT_VIEW='resources/views/commerce_v2/pages/product.blade.php'
SEARCH_VIEW='resources/views/commerce_v2/pages/search.blade.php'
COLLECTION_VIEW='resources/views/commerce_v2/pages/collection.blade.php'
ERROR_VIEW='resources/views/commerce_v2/pages/error.blade.php'

CSS_FILE='public/commerce-v2/commerce.css'
JS_FILE='public/commerce-v2/commerce.js'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

FILES=(
  "$BOOTSTRAP"
  "$CONFIG"
  "$EXCEPTION"
  "$CLIENT"
  "$PRESENTER"
  "$CONTROLLER"
  "$SMOKE_COMMAND"
  "$ROUTE_FILE"
  "$WEB_ROUTES"
  "$ENV_EXAMPLE"
  "$LAYOUT"
  "$PRODUCT_CARD"
  "$PAGINATION"
  "$HOME_VIEW"
  "$SHOP_VIEW"
  "$PRODUCT_VIEW"
  "$SEARCH_VIEW"
  "$COLLECTION_VIEW"
  "$ERROR_VIEW"
  "$CSS_FILE"
  "$JS_FILE"
)

backup_file() {
    local file="$1"
    mkdir -p "$BACKUP_ROOT/$(dirname "$file")"

    if [ -e "$file" ]; then
        cp -p "$file" "$BACKUP_ROOT/$file"
        printf 'existing\t%s\n' "$file" >> "$MANIFEST"
    else
        printf 'new\t%s\n' "$file" >> "$MANIFEST"
    fi
}

rollback() {
    local status=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' \
          'Có lỗi bắt buộc. Đang rollback toàn bộ SF-01/SF-02 bundle...' \
          >&2

        while IFS=$'\t' read -r kind file; do
            case "$kind" in
                existing)
                    if [ -f "$BACKUP_ROOT/$file" ]; then
                        mkdir -p "$(dirname "$file")"
                        cp -p "$BACKUP_ROOT/$file" "$file"
                    fi
                    ;;
                new)
                    rm -f "$file"
                    ;;
            esac
        done < "$MANIFEST"
    fi

    exit "$status"
}

trap rollback ERR

test -f artisan || {
    printf '%s\n' \
      'ERROR: Chạy patch từ root Laravel Storefront Lin Xén, nơi có artisan.' \
      >&2
    exit 1
}

test -f "$BOOTSTRAP" || {
    printf 'ERROR: Thiếu %s\n' "$BOOTSTRAP" >&2
    exit 1
}

test -f composer.json || {
    printf '%s\n' 'ERROR: Thiếu composer.json.' >&2
    exit 1
}

php -r '
$data = json_decode(file_get_contents("composer.json"), true);
$framework = $data["require"]["laravel/framework"] ?? "";
if (!is_string($framework) || $framework === "") {
    fwrite(STDERR, "ERROR: Không xác định được Laravel framework.\n");
    exit(1);
}
echo "LARAVEL_REQUIRE={$framework}\n";
'

for file in "${FILES[@]}"; do
    backup_file "$file"
done

mkdir -p \
  "$(dirname "$CONFIG")" \
  "$(dirname "$EXCEPTION")" \
  "$(dirname "$CLIENT")" \
  "$(dirname "$PRESENTER")" \
  "$(dirname "$CONTROLLER")" \
  "$(dirname "$SMOKE_COMMAND")" \
  "$(dirname "$ROUTE_FILE")" \
  "$(dirname "$LAYOUT")" \
  "$(dirname "$PRODUCT_CARD")" \
  "$(dirname "$HOME_VIEW")" \
  "$(dirname "$CSS_FILE")"

PATCH_WRITTEN=1

cat > "$CONFIG" <<'PHP'
<?php

return [
    'enabled' => env('ERP_COMMERCE_V2_ENABLED', true),

    'base_url' => rtrim(
        (string) env(
            'ERP_COMMERCE_V2_BASE_URL',
            'https://3mg.ai/api/commerce/v2'
        ),
        '/'
    ),

    'site' => env('ERP_COMMERCE_V2_SITE', 'linxen'),

    'token' => env('ERP_COMMERCE_V2_TOKEN'),

    'timeout_seconds' => (int) env(
        'ERP_COMMERCE_V2_TIMEOUT',
        8
    ),

    'connect_timeout_seconds' => (int) env(
        'ERP_COMMERCE_V2_CONNECT_TIMEOUT',
        3
    ),

    'retry_times' => (int) env(
        'ERP_COMMERCE_V2_RETRY_TIMES',
        2
    ),

    'retry_sleep_ms' => (int) env(
        'ERP_COMMERCE_V2_RETRY_SLEEP_MS',
        250
    ),

    'cache_store' => env(
        'ERP_COMMERCE_V2_CACHE_STORE',
        'file'
    ),

    'fresh_cache_seconds' => (int) env(
        'ERP_COMMERCE_V2_FRESH_CACHE_SECONDS',
        10
    ),

    'stale_cache_seconds' => (int) env(
        'ERP_COMMERCE_V2_STALE_CACHE_SECONDS',
        300
    ),

    'stage_prefix' => env(
        'LINXEN_STOREFRONT_V2_PREFIX',
        'v2'
    ),

    'brand_name' => env(
        'LINXEN_STOREFRONT_V2_BRAND_NAME',
        'LIN XÉN'
    ),

    'support_phone' => env(
        'LINXEN_STOREFRONT_V2_SUPPORT_PHONE',
        ''
    ),

    'support_url' => env(
        'LINXEN_STOREFRONT_V2_SUPPORT_URL',
        ''
    ),
];
PHP

cat > "$EXCEPTION" <<'PHP'
<?php

namespace App\Exceptions\CommerceV2;

use RuntimeException;
use Throwable;

class CommerceV2ClientException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 503,
        public readonly string $errorCode = 'commerce_v2_unavailable',
        public readonly array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
PHP

cat > "$CLIENT" <<'PHP'
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
PHP

cat > "$PRESENTER" <<'PHP'
<?php

namespace App\Services\CommerceV2;

use Illuminate\Support\Str;

class CommerceV2Presenter
{
    public function productSummary(array $product): array
    {
        $slug = trim((string) data_get(
            $product,
            'slug',
            data_get($product, 'id', '')
        ));
        $cover = (array) data_get(
            $product,
            'cover',
            []
        );
        $price = (array) data_get(
            $product,
            'price',
            []
        );
        $colors = collect((array) data_get(
            $product,
            'colors',
            []
        ))
            ->map(fn ($color) => [
                'id' => (string) data_get($color, 'id'),
                'code' => (string) data_get(
                    $color,
                    'code'
                ),
                'label' => (string) data_get(
                    $color,
                    'label'
                ),
                'hex' => (string) data_get(
                    $color,
                    'hex'
                ),
                'sellable' => (bool) data_get(
                    $color,
                    'sellable',
                    false
                ),
                'available' => (float) data_get(
                    $color,
                    'available',
                    0
                ),
                'cover_url' => (string) data_get(
                    $color,
                    'cover.url',
                    ''
                ),
                'available_sizes' => array_values(
                    (array) data_get(
                        $color,
                        'available_sizes',
                        []
                    )
                ),
            ])
            ->values()
            ->all();

        return [
            'id' => (string) data_get($product, 'id'),
            'code' => (string) data_get(
                $product,
                'code'
            ),
            'slug' => $slug,
            'url' => route(
                'commerce.v2.product',
                ['slug' => $slug]
            ),
            'name' => (string) data_get(
                $product,
                'name'
            ),
            'short_name' => (string) data_get(
                $product,
                'short_name'
            ),
            'cover_url' => (string) (
                data_get($cover, 'url')
                ?: data_get($cover, 'thumb_url')
            ),
            'cover_alt' => (string) data_get(
                $product,
                'name',
                'Sản phẩm Lin Xén'
            ),
            'price_min' => (float) data_get(
                $price,
                'min',
                0
            ),
            'price_max' => (float) data_get(
                $price,
                'max',
                0
            ),
            'original_min' => (float) data_get(
                $price,
                'original_min',
                data_get($price, 'min', 0)
            ),
            'has_sale' => (bool) data_get(
                $price,
                'has_sale',
                false
            ),
            'is_range' => (bool) data_get(
                $price,
                'is_range',
                false
            ),
            'available_total' => (float) data_get(
                $product,
                'availability.available_total',
                0
            ),
            'in_stock' => (bool) data_get(
                $product,
                'availability.in_stock',
                false
            ),
            'colors' => $colors,
        ];
    }

    public function productDetail(array $product): array
    {
        $summary = $this->productSummary($product);
        $media = collect((array) data_get(
            $product,
            'media.items',
            []
        ))
            ->map(fn ($item) => [
                'id' => (string) data_get($item, 'id'),
                'url' => (string) data_get($item, 'url'),
                'thumb_url' => (string) (
                    data_get($item, 'thumb_url')
                    ?: data_get($item, 'url')
                ),
                'color_code' => (string) data_get(
                    $item,
                    'color.code'
                ),
                'color_name' => (string) data_get(
                    $item,
                    'color.name'
                ),
                'shot' => (string) data_get(
                    $item,
                    'shot'
                ),
            ])
            ->filter(fn ($item) => $item['url'] !== '')
            ->values()
            ->all();

        $supportMedia = collect((array) data_get(
            $product,
            'media.support_items',
            []
        ))
            ->map(fn ($item) => [
                'id' => (string) data_get($item, 'id'),
                'url' => (string) data_get($item, 'url'),
                'support_role' => (string) data_get(
                    $item,
                    'support_role'
                ),
            ])
            ->filter(fn ($item) => $item['url'] !== '')
            ->values()
            ->all();

        $colors = collect((array) data_get(
            $product,
            'colors',
            []
        ))
            ->map(function ($color) {
                $sizes = collect((array) data_get(
                    $color,
                    'sizes',
                    []
                ))
                    ->map(fn ($size) => [
                        'size' => (string) data_get(
                            $size,
                            'size'
                        ),
                        'sellable_sku_id' => (string) data_get(
                            $size,
                            'sellable_sku_id'
                        ),
                        'sku' => (string) data_get(
                            $size,
                            'sku'
                        ),
                        'available' => (float) data_get(
                            $size,
                            'available',
                            0
                        ),
                        'sellable' => (bool) data_get(
                            $size,
                            'sellable',
                            false
                        ),
                        'availability_status' => (string) data_get(
                            $size,
                            'availability_status'
                        ),
                        'price_current' => (float) data_get(
                            $size,
                            'price.current',
                            0
                        ),
                        'price_original' => (float) data_get(
                            $size,
                            'price.original',
                            0
                        ),
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => (string) data_get($color, 'id'),
                    'code' => (string) data_get(
                        $color,
                        'code'
                    ),
                    'label' => (string) data_get(
                        $color,
                        'label'
                    ),
                    'hex' => (string) data_get(
                        $color,
                        'hex'
                    ),
                    'available' => (float) data_get(
                        $color,
                        'available',
                        0
                    ),
                    'sellable' => (bool) data_get(
                        $color,
                        'sellable',
                        false
                    ),
                    'cover_url' => (string) data_get(
                        $color,
                        'cover.url',
                        ''
                    ),
                    'sizes' => $sizes,
                ];
            })
            ->values()
            ->all();

        return array_merge($summary, [
            'description' => (string) data_get(
                $product,
                'description'
            ),
            'specs' => array_values(
                (array) data_get($product, 'specs', [])
            ),
            'materials' => (array) data_get(
                $product,
                'materials',
                []
            ),
            'media' => $media,
            'support_media' => $supportMedia,
            'colors' => $colors,
            'public_ready' => (bool) data_get(
                $product,
                'public_ready',
                false
            ),
        ]);
    }

    public function collection(array $collection): array
    {
        $slug = (string) data_get(
            $collection,
            'slug'
        );

        return [
            'id' => (string) data_get(
                $collection,
                'id'
            ),
            'slug' => $slug,
            'url' => route(
                'commerce.v2.collection',
                ['slug' => $slug]
            ),
            'name' => (string) data_get(
                $collection,
                'name'
            ),
            'description' => (string) data_get(
                $collection,
                'description'
            ),
            'hero_image' => (string) data_get(
                $collection,
                'hero_image'
            ),
            'seo_title' => (string) data_get(
                $collection,
                'seo.title'
            ),
            'seo_description' => (string) data_get(
                $collection,
                'seo.description'
            ),
        ];
    }

    public function money(float|int $amount): string
    {
        return number_format(
            (float) $amount,
            0,
            ',',
            '.'
        ) . '₫';
    }

    public function normalizeProductReference(
        string $slug
    ): string {
        $slug = trim($slug);

        if (
            preg_match('/-rs-(\d+)$/i', $slug, $matches)
            === 1
        ) {
            return 'rs_' . $matches[1];
        }

        return $slug;
    }

    public function safeSeoDescription(
        string $value,
        string $fallback
    ): string {
        $value = Str::squish(strip_tags($value));

        return $value !== ''
            ? Str::limit($value, 155, '')
            : $fallback;
    }
}
PHP

cat > "$CONTROLLER" <<'PHP'
<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CommerceV2Presenter;
use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Throwable;

class CatalogPageController extends Controller
{
    public function __construct(
        protected ErpCommerceClient $client,
        protected CommerceV2Presenter $presenter
    ) {
    }

    public function home(Request $request): View|Response
    {
        try {
            $listing = $this->client->listing(8);
            $collections = $this->client->collections();

            return view('commerce_v2.pages.home', [
                'products' => $this->presentProducts(
                    (array) data_get(
                        $listing,
                        'data.items',
                        []
                    )
                ),
                'collections' => $this->presentCollections(
                    (array) data_get(
                        $collections,
                        'data.items',
                        []
                    )
                ),
                'cacheStatus' => data_get(
                    $listing,
                    '_storefront_cache'
                ),
                'pageTitle' => 'LIN XÉN — Váy thiết kế hiện đại',
                'pageDescription' => 'Khám phá váy thiết kế LIN XÉN với màu sắc, kích thước, giá và tồn kho được cập nhật từ hệ thống chính thức.',
            ]);
        } catch (CommerceV2ClientException $e) {
            return $this->errorView($e);
        }
    }

    public function shop(Request $request): View|Response
    {
        try {
            $listing = $this->client->listing(
                $this->limit($request),
                $request->query('cursor')
            );

            return view('commerce_v2.pages.shop', [
                'products' => $this->presentProducts(
                    (array) data_get(
                        $listing,
                        'data.items',
                        []
                    )
                ),
                'pagination' => (array) data_get(
                    $listing,
                    'meta.pagination',
                    []
                ),
                'cacheStatus' => data_get(
                    $listing,
                    '_storefront_cache'
                ),
                'pageTitle' => 'Sản phẩm — LIN XÉN',
                'pageDescription' => 'Danh sách sản phẩm LIN XÉN đang sẵn sàng để mua.',
            ]);
        } catch (CommerceV2ClientException $e) {
            return $this->errorView($e);
        }
    }

    public function product(
        Request $request,
        string $slug
    ): View|Response {
        try {
            $reference = $this->presenter
                ->normalizeProductReference($slug);
            $result = $this->client->product($reference);
            $product = $this->presenter->productDetail(
                (array) data_get($result, 'data', [])
            );

            if (! $product['public_ready']) {
                throw new CommerceV2ClientException(
                    'Sản phẩm chưa sẵn sàng.',
                    404,
                    'storefront_product_not_ready'
                );
            }

            return view('commerce_v2.pages.product', [
                'product' => $product,
                'cacheStatus' => data_get(
                    $result,
                    '_storefront_cache'
                ),
                'pageTitle' => $product['name'] . ' — LIN XÉN',
                'pageDescription' => $this->presenter
                    ->safeSeoDescription(
                        $product['description'],
                        'Xem màu, kích thước, giá và tồn kho của '
                            . $product['name']
                            . '.'
                    ),
                'ogImage' => $product['cover_url'],
            ]);
        } catch (CommerceV2ClientException $e) {
            return $this->errorView($e);
        }
    }

    public function search(Request $request): View|Response
    {
        $query = Str::squish(
            (string) $request->query('q', '')
        );

        if ($query === '') {
            return view('commerce_v2.pages.search', [
                'query' => '',
                'products' => [],
                'pagination' => [],
                'pageTitle' => 'Tìm kiếm — LIN XÉN',
                'pageDescription' => 'Tìm kiếm sản phẩm LIN XÉN theo tên, mã sản phẩm, SKU hoặc màu.',
            ]);
        }

        try {
            $result = $this->client->search(
                $query,
                $this->limit($request),
                $request->query('cursor')
            );

            return view('commerce_v2.pages.search', [
                'query' => $query,
                'products' => $this->presentProducts(
                    (array) data_get(
                        $result,
                        'data.items',
                        []
                    )
                ),
                'pagination' => (array) data_get(
                    $result,
                    'meta.pagination',
                    []
                ),
                'cacheStatus' => data_get(
                    $result,
                    '_storefront_cache'
                ),
                'pageTitle' => 'Tìm “' . $query . '” — LIN XÉN',
                'pageDescription' => 'Kết quả tìm kiếm sản phẩm LIN XÉN cho từ khóa ' . $query . '.',
            ]);
        } catch (CommerceV2ClientException $e) {
            if (
                $e->errorCode
                === 'commerce_catalog_search_query_too_short'
            ) {
                return view('commerce_v2.pages.search', [
                    'query' => $query,
                    'products' => [],
                    'pagination' => [],
                    'validationMessage' => $e->getMessage(),
                    'pageTitle' => 'Tìm kiếm — LIN XÉN',
                    'pageDescription' => 'Tìm kiếm sản phẩm LIN XÉN.',
                ]);
            }

            return $this->errorView($e);
        }
    }

    public function collection(
        Request $request,
        string $slug
    ): View|Response {
        try {
            $result = $this->client->collection(
                $slug,
                $this->limit($request),
                $request->query('cursor')
            );
            $collection = $this->presenter->collection(
                (array) data_get(
                    $result,
                    'data.collection',
                    []
                )
            );

            return view('commerce_v2.pages.collection', [
                'collection' => $collection,
                'filters' => (array) data_get(
                    $result,
                    'data.filters',
                    []
                ),
                'products' => $this->presentProducts(
                    (array) data_get(
                        $result,
                        'data.items',
                        []
                    )
                ),
                'pagination' => (array) data_get(
                    $result,
                    'meta.pagination',
                    []
                ),
                'cacheStatus' => data_get(
                    $result,
                    '_storefront_cache'
                ),
                'pageTitle' => (
                    $collection['seo_title']
                    ?: $collection['name']
                ) . ' — LIN XÉN',
                'pageDescription' => $this->presenter
                    ->safeSeoDescription(
                        $collection['seo_description']
                            ?: $collection['description'],
                        'Bộ sưu tập '
                            . $collection['name']
                            . ' của LIN XÉN.'
                    ),
                'ogImage' => $collection['hero_image'],
            ]);
        } catch (CommerceV2ClientException $e) {
            return $this->errorView($e);
        }
    }

    protected function presentProducts(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => $this->presenter
                ->productSummary((array) $item))
            ->filter(fn ($item) => (
                $item['id'] !== ''
                && $item['name'] !== ''
                && $item['cover_url'] !== ''
            ))
            ->values()
            ->all();
    }

    protected function presentCollections(
        array $items
    ): array {
        return collect($items)
            ->map(fn ($item) => $this->presenter
                ->collection((array) $item))
            ->filter(fn ($item) => (
                $item['slug'] !== ''
                && $item['name'] !== ''
            ))
            ->values()
            ->all();
    }

    protected function limit(Request $request): int
    {
        return max(
            1,
            min(
                12,
                (int) $request->query('limit', 8)
            )
        );
    }

    protected function errorView(
        CommerceV2ClientException $e
    ): Response {
        $status = max(
            400,
            min(599, $e->httpStatus)
        );

        return response()->view(
            'commerce_v2.pages.error',
            [
                'status' => $status,
                'errorCode' => $e->errorCode,
                'message' => $e->getMessage(),
                'requestId' => data_get(
                    $e->details,
                    'request_id'
                ),
                'pageTitle' => $status === 404
                    ? 'Không tìm thấy — LIN XÉN'
                    : 'Hệ thống đang bận — LIN XÉN',
                'pageDescription' => 'Trang sản phẩm LIN XÉN tạm thời chưa thể hiển thị.',
            ],
            $status
        );
    }
}
PHP

cat > "$SMOKE_COMMAND" <<'PHP'
<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\Console\Command;
use Throwable;

class CommerceV2SmokeCommand extends Command
{
    protected $signature = 'commerce-v2:smoke
        {--product=rs_4451 : Public product id}
        {--sku=SP14546158 : Exact SKU search}
        {--collection=moi-nhat : Collection slug}
        {--limit=2 : Small smoke page size}
        {--json : Machine-readable JSON}';

    protected $description = 'Read-only smoke for Lin Xén Storefront V2 → ERP Commerce V2.';

    public function handle(
        ErpCommerceClient $client
    ): int {
        try {
            $limit = max(
                1,
                min(4, (int) $this->option('limit'))
            );
            $productId = (string) $this->option(
                'product'
            );
            $sku = (string) $this->option('sku');
            $collectionSlug = (string) $this->option(
                'collection'
            );

            $configuration = $client
                ->configurationStatus();
            $listing = $client->listing($limit);
            $product = $client->product($productId);
            $search = $client->search(
                $sku,
                $limit
            );
            $collections = $client->collections();
            $collection = $client->collection(
                $collectionSlug,
                $limit
            );

            $listingItems = (array) data_get(
                $listing,
                'data.items',
                []
            );
            $searchItems = (array) data_get(
                $search,
                'data.items',
                []
            );
            $collectionItems = (array) data_get(
                $collection,
                'data.items',
                []
            );

            $checks = [
                'configuration' => (
                    data_get(
                        $configuration,
                        'base_url_configured'
                    ) === true
                    && data_get(
                        $configuration,
                        'site_configured'
                    ) === true
                    && data_get(
                        $configuration,
                        'token_configured'
                    ) === true
                ),
                'listing_non_empty' => count(
                    $listingItems
                ) > 0,
                'product_exact' => data_get(
                    $product,
                    'data.id'
                ) === $productId,
                'search_exact_first' => data_get(
                    $searchItems,
                    '0.id'
                ) === $productId,
                'collections_non_empty' => count(
                    (array) data_get(
                        $collections,
                        'data.items',
                        []
                    )
                ) > 0,
                'collection_exact' => data_get(
                    $collection,
                    'data.collection.slug'
                ) === $collectionSlug,
                'collection_non_empty' => count(
                    $collectionItems
                ) > 0,
            ];

            $ok = ! in_array(false, $checks, true);

            $result = [
                'ok' => $ok,
                'configuration' => $configuration,
                'summary' => [
                    'listing_returned' => count(
                        $listingItems
                    ),
                    'product_id' => data_get(
                        $product,
                        'data.id'
                    ),
                    'search_first' => data_get(
                        $searchItems,
                        '0.id'
                    ),
                    'collections_count' => count(
                        (array) data_get(
                            $collections,
                            'data.items',
                            []
                        )
                    ),
                    'collection_slug' => data_get(
                        $collection,
                        'data.collection.slug'
                    ),
                    'collection_returned' => count(
                        $collectionItems
                    ),
                ],
                'checks' => $checks,
                'mutation' => 'none',
            ];

            if ($this->option('json')) {
                $this->line((string) json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                ));
            } else {
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['listing_returned', data_get($result, 'summary.listing_returned')],
                        ['product_id', data_get($result, 'summary.product_id')],
                        ['search_first', data_get($result, 'summary.search_first')],
                        ['collections_count', data_get($result, 'summary.collections_count')],
                        ['collection_slug', data_get($result, 'summary.collection_slug')],
                        ['collection_returned', data_get($result, 'summary.collection_returned')],
                    ]
                );

                foreach ($checks as $name => $passed) {
                    $this->line(
                        strtoupper($name)
                        . '='
                        . ($passed ? 'PASS' : 'FAIL')
                    );
                }

                $this->line(
                    'STOREFRONT_V2_SMOKE='
                    . ($ok ? 'PASS' : 'FAIL')
                );
            }

            return $ok
                ? self::SUCCESS
                : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
PHP

cat > "$ROUTE_FILE" <<'PHP'
<?php

use App\Http\Controllers\CommerceV2\CatalogPageController;
use Illuminate\Support\Facades\Route;

$prefix = trim(
    (string) config(
        'commerce_v2.stage_prefix',
        'v2'
    ),
    '/'
);

Route::prefix($prefix)
    ->name('commerce.v2.')
    ->group(function () {
        Route::get('/', [
            CatalogPageController::class,
            'home',
        ])->name('home');

        Route::get('/shop', [
            CatalogPageController::class,
            'shop',
        ])->name('shop');

        Route::get('/search', [
            CatalogPageController::class,
            'search',
        ])->name('search');

        Route::get('/collections/{slug}', [
            CatalogPageController::class,
            'collection',
        ])
            ->where('slug', '[A-Za-z0-9._-]+')
            ->name('collection');

        Route::get('/p/{slug}', [
            CatalogPageController::class,
            'product',
        ])
            ->where('slug', '[A-Za-z0-9._-]+')
            ->name('product');
    });
PHP

cat > "$LAYOUT" <<'BLADE'
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle ?? 'LIN XÉN' }}</title>
    <meta name="description" content="{{ $pageDescription ?? 'Váy thiết kế LIN XÉN.' }}">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="LIN XÉN">
    <meta property="og:title" content="{{ $pageTitle ?? 'LIN XÉN' }}">
    <meta property="og:description" content="{{ $pageDescription ?? 'Váy thiết kế LIN XÉN.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <link rel="stylesheet" href="{{ asset('commerce-v2/commerce.css') }}?v=1">
    @stack('head')
</head>
<body class="lxv2-body">
    <a class="lxv2-skip" href="#main-content">Bỏ qua điều hướng</a>

    <header class="lxv2-header">
        <div class="lxv2-header__inner">
            <a class="lxv2-brand" href="{{ route('commerce.v2.home') }}" aria-label="LIN XÉN">
                <span class="lxv2-brand__mark">LX</span>
                <span class="lxv2-brand__text">
                    <strong>{{ config('commerce_v2.brand_name', 'LIN XÉN') }}</strong>
                    <small>Váy thiết kế</small>
                </span>
            </a>

            <nav class="lxv2-nav" aria-label="Điều hướng chính">
                <a href="{{ route('commerce.v2.home') }}" @class(['active' => request()->routeIs('commerce.v2.home')])>Trang chủ</a>
                <a href="{{ route('commerce.v2.shop') }}" @class(['active' => request()->routeIs('commerce.v2.shop')])>Sản phẩm</a>
                <a href="{{ route('commerce.v2.search') }}" @class(['active' => request()->routeIs('commerce.v2.search')])>Tìm kiếm</a>
            </nav>

            <form class="lxv2-header-search" method="get" action="{{ route('commerce.v2.search') }}">
                <label class="sr-only" for="lxv2HeaderSearch">Tìm sản phẩm</label>
                <input
                    id="lxv2HeaderSearch"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tên, mã sản phẩm, SKU..."
                    autocomplete="off"
                >
                <button type="submit" aria-label="Tìm kiếm">⌕</button>
            </form>
        </div>
    </header>

    @if(($cacheStatus ?? null) === 'stale')
        <div class="lxv2-status-bar">
            Dữ liệu đang hiển thị từ bản lưu gần nhất do hệ thống cập nhật tạm thời gián đoạn.
        </div>
    @endif

    <main id="main-content" class="lxv2-main">
        @yield('content')
    </main>

    <footer class="lxv2-footer">
        <div>
            <strong>{{ config('commerce_v2.brand_name', 'LIN XÉN') }}</strong>
            <p>Thiết kế dành cho những khoảnh khắc anh muốn mình thật đẹp.</p>
        </div>
        <div class="lxv2-footer__links">
            <a href="{{ route('commerce.v2.shop') }}">Sản phẩm</a>
            <a href="{{ route('commerce.v2.search') }}">Tìm kiếm</a>
            @if(config('commerce_v2.support_url'))
                <a href="{{ config('commerce_v2.support_url') }}" rel="nofollow">Hỗ trợ</a>
            @endif
        </div>
    </footer>

    <nav class="lxv2-bottom-nav" aria-label="Điều hướng di động">
        <a href="{{ route('commerce.v2.home') }}" @class(['active' => request()->routeIs('commerce.v2.home')])>
            <span>⌂</span><small>Trang chủ</small>
        </a>
        <a href="{{ route('commerce.v2.shop') }}" @class(['active' => request()->routeIs('commerce.v2.shop')])>
            <span>◇</span><small>Sản phẩm</small>
        </a>
        <a href="{{ route('commerce.v2.search') }}" @class(['active' => request()->routeIs('commerce.v2.search')])>
            <span>⌕</span><small>Tìm kiếm</small>
        </a>
    </nav>

    <script src="{{ asset('commerce-v2/commerce.js') }}?v=1" defer></script>
    @stack('scripts')
</body>
</html>
BLADE

cat > "$PRODUCT_CARD" <<'BLADE'
<article class="lxv2-card">
    <a class="lxv2-card__media" href="{{ $product['url'] }}">
        <img
            src="{{ $product['cover_url'] }}"
            alt="{{ $product['cover_alt'] }}"
            loading="lazy"
            width="720"
            height="900"
        >
        @if($product['has_sale'])
            <span class="lxv2-card__badge">Ưu đãi</span>
        @endif
    </a>

    <div class="lxv2-card__body">
        <div class="lxv2-card__meta">
            <span>{{ $product['code'] }}</span>
            <span>{{ $product['in_stock'] ? 'Còn hàng' : 'Tạm hết' }}</span>
        </div>

        <h3><a href="{{ $product['url'] }}">{{ $product['name'] }}</a></h3>

        <div class="lxv2-price">
            <strong>{{ number_format($product['price_min'], 0, ',', '.') }}₫</strong>
            @if($product['has_sale'] && $product['original_min'] > $product['price_min'])
                <del>{{ number_format($product['original_min'], 0, ',', '.') }}₫</del>
            @endif
        </div>

        @if(!empty($product['colors']))
            <div class="lxv2-swatches" aria-label="Màu sản phẩm">
                @foreach(array_slice($product['colors'], 0, 6) as $color)
                    <span
                        class="lxv2-swatch"
                        title="{{ $color['label'] }}"
                        style="--swatch: {{ $color['hex'] ?: '#d8d0ca' }}"
                    ></span>
                @endforeach
                @if(count($product['colors']) > 6)
                    <small>+{{ count($product['colors']) - 6 }}</small>
                @endif
            </div>
        @endif
    </div>
</article>
BLADE

cat > "$PAGINATION" <<'BLADE'
@if(!empty($pagination['has_more']) && !empty($pagination['next_cursor']))
    @php
        $query = array_filter(array_merge(
            request()->query(),
            ['cursor' => $pagination['next_cursor']]
        ));
    @endphp

    <div class="lxv2-load-more">
        <a class="lxv2-button lxv2-button--outline" href="{{ request()->url() . '?' . http_build_query($query) }}">
            Xem thêm sản phẩm
        </a>
    </div>
@endif
BLADE

cat > "$HOME_VIEW" <<'BLADE'
@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-hero">
    <div class="lxv2-hero__content">
        <p class="lxv2-eyebrow">LIN XÉN · STOREFRONT V2</p>
        <h1>Thiết kế giúp anh tự tin trong từng khoảnh khắc.</h1>
        <p>Giá, màu, kích thước và tồn kho được lấy trực tiếp từ hệ thống thương mại chính thức.</p>
        <div class="lxv2-actions">
            <a class="lxv2-button" href="{{ route('commerce.v2.shop') }}">Khám phá sản phẩm</a>
            <a class="lxv2-button lxv2-button--ghost" href="{{ route('commerce.v2.search') }}">Tìm theo mã hoặc tên</a>
        </div>
    </div>
    <div class="lxv2-hero__visual" aria-hidden="true">
        <span>LIN</span>
        <span>XÉN</span>
    </div>
</section>

@if(!empty($collections))
<section class="lxv2-section">
    <div class="lxv2-section__head">
        <div>
            <p class="lxv2-eyebrow">Chọn nhanh</p>
            <h2>Bộ sưu tập</h2>
        </div>
    </div>
    <div class="lxv2-collection-row">
        @foreach($collections as $collection)
            <a class="lxv2-collection-pill" href="{{ $collection['url'] }}">
                <strong>{{ $collection['name'] }}</strong>
                @if($collection['description'])
                    <small>{{ $collection['description'] }}</small>
                @endif
            </a>
        @endforeach
    </div>
</section>
@endif

<section class="lxv2-section">
    <div class="lxv2-section__head">
        <div>
            <p class="lxv2-eyebrow">Mới cập nhật</p>
            <h2>Sản phẩm nổi bật</h2>
        </div>
        <a href="{{ route('commerce.v2.shop') }}">Xem tất cả →</a>
    </div>

    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">Chưa có sản phẩm sẵn sàng hiển thị.</div>
        @endforelse
    </div>
</section>

<section class="lxv2-trust">
    <article><strong>Dữ liệu thật</strong><span>Giá và tồn kho từ ERP Commerce V2.</span></article>
    <article><strong>Ảnh đã duyệt</strong><span>Chỉ sử dụng media được phép bán hàng.</span></article>
    <article><strong>Chọn đúng SKU</strong><span>Màu và size gắn với SKU bán chính xác.</span></article>
</section>
@endsection
BLADE

cat > "$SHOP_VIEW" <<'BLADE'
@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Danh mục</p>
    <h1>Sản phẩm LIN XÉN</h1>
    <p>Khám phá các thiết kế đang sẵn sàng theo giá và tồn kho hiện tại.</p>
</section>

<section class="lxv2-section lxv2-section--flush">
    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">Chưa tìm thấy sản phẩm sẵn sàng bán.</div>
        @endforelse
    </div>

    @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
</section>
@endsection
BLADE

cat > "$SEARCH_VIEW" <<'BLADE'
@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-page-head lxv2-search-head">
    <p class="lxv2-eyebrow">Tìm kiếm</p>
    <h1>Tìm thiết kế phù hợp</h1>
    <form class="lxv2-search-form" method="get" action="{{ route('commerce.v2.search') }}">
        <input
            name="q"
            value="{{ $query }}"
            placeholder="Tên váy, mã RS, SKU hoặc màu..."
            autofocus
            autocomplete="off"
        >
        <button class="lxv2-button" type="submit">Tìm sản phẩm</button>
    </form>
    @if(!empty($validationMessage))
        <p class="lxv2-form-message">{{ $validationMessage }}</p>
    @elseif($query !== '')
        <p>Kết quả cho “{{ $query }}”</p>
    @endif
</section>

@if($query !== '')
<section class="lxv2-section lxv2-section--flush">
    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">
                Không tìm thấy sản phẩm phù hợp. Hãy thử mã RS, SKU hoặc từ khóa khác.
            </div>
        @endforelse
    </div>

    @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
</section>
@endif
@endsection
BLADE

cat > "$COLLECTION_VIEW" <<'BLADE'
@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-page-head lxv2-collection-head" @if($collection['hero_image']) style="--hero-image:url('{{ $collection['hero_image'] }}')" @endif>
    <p class="lxv2-eyebrow">Bộ sưu tập</p>
    <h1>{{ $collection['name'] }}</h1>
    @if($collection['description'])
        <p>{{ $collection['description'] }}</p>
    @endif
</section>

<section class="lxv2-section lxv2-section--flush">
    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">Bộ sưu tập này chưa có sản phẩm sẵn sàng bán.</div>
        @endforelse
    </div>

    @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
</section>
@endsection
BLADE

cat > "$PRODUCT_VIEW" <<'BLADE'
@extends('commerce_v2.layouts.app')

@section('og_type', 'product')

@section('content')
<section class="lxv2-pdp" data-lxv2-product>
    <div class="lxv2-gallery">
        <div class="lxv2-gallery__main">
            <img
                data-lxv2-main-image
                src="{{ $product['cover_url'] }}"
                alt="{{ $product['name'] }}"
                width="900"
                height="1125"
            >
        </div>

        @if(!empty($product['media']))
            <div class="lxv2-gallery__thumbs">
                @foreach(array_slice($product['media'], 0, 12) as $index => $media)
                    <button
                        type="button"
                        @class(['active' => $index === 0])
                        data-lxv2-thumb
                        data-image="{{ $media['url'] }}"
                        data-color="{{ $media['color_code'] }}"
                        aria-label="Xem ảnh {{ $index + 1 }}"
                    >
                        <img src="{{ $media['thumb_url'] }}" alt="" loading="lazy">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="lxv2-pdp__info">
        <p class="lxv2-eyebrow">{{ $product['code'] }}</p>
        <h1>{{ $product['name'] }}</h1>

        <div class="lxv2-pdp__price" data-lxv2-price>
            <strong>{{ number_format($product['price_min'], 0, ',', '.') }}₫</strong>
            @if($product['has_sale'] && $product['original_min'] > $product['price_min'])
                <del>{{ number_format($product['original_min'], 0, ',', '.') }}₫</del>
            @endif
        </div>

        <p class="lxv2-stock">
            {{ $product['in_stock'] ? 'Đang có hàng' : 'Tạm hết hàng' }}
            @if($product['available_total'] > 0)
                · {{ (int) $product['available_total'] }} sản phẩm khả dụng
            @endif
        </p>

        @if(!empty($product['description']))
            <div class="lxv2-description">{{ $product['description'] }}</div>
        @endif

        <div class="lxv2-selector">
            <div class="lxv2-selector__label">
                <strong>Màu sắc</strong>
                <span data-lxv2-color-label>Chọn màu</span>
            </div>
            <div class="lxv2-color-options">
                @foreach($product['colors'] as $color)
                    <button
                        type="button"
                        class="lxv2-color-option"
                        data-lxv2-color
                        data-code="{{ $color['code'] }}"
                        data-label="{{ $color['label'] }}"
                        data-cover="{{ $color['cover_url'] }}"
                        data-sizes='@json($color['sizes'])'
                        @disabled(!$color['sellable'])
                    >
                        <span style="--swatch:{{ $color['hex'] ?: '#d8d0ca' }}"></span>
                        <small>{{ $color['label'] }}</small>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="lxv2-selector">
            <div class="lxv2-selector__label">
                <strong>Kích thước</strong>
                <span data-lxv2-size-label>Chọn màu trước</span>
            </div>
            <div class="lxv2-size-options" data-lxv2-sizes></div>
        </div>

        <div class="lxv2-selection-summary" data-lxv2-selection hidden>
            <span data-lxv2-selected-text></span>
            <small data-lxv2-selected-stock></small>
        </div>

        <button class="lxv2-button lxv2-button--wide" type="button" disabled data-lxv2-buy>
            Chọn màu và kích thước
        </button>
        <p class="lxv2-next-phase-note">
            Giỏ hàng và thanh toán an toàn sẽ được mở ở giai đoạn tiếp theo.
        </p>

        @if(!empty($product['specs']))
            <div class="lxv2-specs">
                <h2>Thông tin thiết kế</h2>
                <dl>
                    @foreach($product['specs'] as $spec)
                        <div>
                            <dt>{{ data_get($spec, 'label') }}</dt>
                            <dd>{{ data_get($spec, 'value') }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        @if(!empty($product['support_media']))
            <div class="lxv2-support-media">
                <h2>Thông tin hỗ trợ</h2>
                @foreach($product['support_media'] as $media)
                    <a href="{{ $media['url'] }}" target="_blank" rel="noopener">
                        {{ $media['support_role'] === 'size_chart' ? 'Xem bảng kích thước' : 'Xem hướng dẫn' }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

<script type="application/json" id="lxv2ProductData">
@json([
    'id' => $product['id'],
    'name' => $product['name'],
    'colors' => $product['colors'],
])
</script>
@endsection
BLADE

cat > "$ERROR_VIEW" <<'BLADE'
@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
<section class="lxv2-error">
    <span>{{ $status }}</span>
    <p class="lxv2-eyebrow">{{ $errorCode }}</p>
    <h1>{{ $status === 404 ? 'Không tìm thấy sản phẩm' : 'Hệ thống đang cập nhật' }}</h1>
    <p>{{ $message }}</p>
    @if($requestId)
        <small>Mã kiểm tra: {{ $requestId }}</small>
    @endif
    <div class="lxv2-actions">
        <a class="lxv2-button" href="{{ route('commerce.v2.home') }}">Về trang chủ</a>
        <a class="lxv2-button lxv2-button--outline" href="{{ route('commerce.v2.shop') }}">Xem sản phẩm</a>
    </div>
</section>
@endsection
BLADE

cat > "$CSS_FILE" <<'CSS'
:root {
    --lxv2-bg: #f7f5f1;
    --lxv2-surface: #ffffff;
    --lxv2-text: #171512;
    --lxv2-muted: #716b63;
    --lxv2-line: #e6e0d8;
    --lxv2-accent: #7b2f3e;
    --lxv2-accent-dark: #54202b;
    --lxv2-soft: #efe8e2;
    --lxv2-success: #276749;
    --lxv2-shadow: 0 18px 48px rgba(31, 25, 20, .08);
    --lxv2-radius: 22px;
    --lxv2-max: 1320px;
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    color: var(--lxv2-text);
    background: var(--lxv2-bg);
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    -webkit-font-smoothing: antialiased;
}

a {
    color: inherit;
    text-decoration: none;
}

button,
input {
    font: inherit;
}

img {
    display: block;
    max-width: 100%;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
}

.lxv2-skip {
    position: fixed;
    left: 16px;
    top: -60px;
    z-index: 1000;
    padding: 10px 14px;
    background: var(--lxv2-text);
    color: #fff;
    border-radius: 10px;
}

.lxv2-skip:focus {
    top: 16px;
}

.lxv2-header {
    position: sticky;
    top: 0;
    z-index: 100;
    border-bottom: 1px solid rgba(230, 224, 216, .85);
    background: rgba(247, 245, 241, .9);
    backdrop-filter: blur(18px);
}

.lxv2-header__inner {
    width: min(var(--lxv2-max), calc(100% - 40px));
    min-height: 76px;
    margin: auto;
    display: grid;
    grid-template-columns: auto 1fr minmax(260px, 360px);
    align-items: center;
    gap: 30px;
}

.lxv2-brand {
    display: inline-flex;
    align-items: center;
    gap: 11px;
}

.lxv2-brand__mark {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    color: #fff;
    background: var(--lxv2-accent);
    font-family: Georgia, serif;
    font-size: 14px;
    letter-spacing: .08em;
}

.lxv2-brand__text {
    display: grid;
    line-height: 1.05;
}

.lxv2-brand__text strong {
    font-family: Georgia, serif;
    font-size: 20px;
    letter-spacing: .08em;
}

.lxv2-brand__text small {
    margin-top: 5px;
    color: var(--lxv2-muted);
    font-size: 10px;
    letter-spacing: .15em;
    text-transform: uppercase;
}

.lxv2-nav {
    display: flex;
    justify-content: center;
    gap: 28px;
}

.lxv2-nav a {
    padding: 10px 2px;
    color: var(--lxv2-muted);
    font-size: 14px;
    font-weight: 700;
    border-bottom: 2px solid transparent;
}

.lxv2-nav a.active,
.lxv2-nav a:hover {
    color: var(--lxv2-text);
    border-color: var(--lxv2-accent);
}

.lxv2-header-search {
    height: 42px;
    display: grid;
    grid-template-columns: 1fr 42px;
    overflow: hidden;
    border: 1px solid var(--lxv2-line);
    border-radius: 999px;
    background: #fff;
}

.lxv2-header-search input {
    min-width: 0;
    padding: 0 16px;
    border: 0;
    outline: 0;
    background: transparent;
}

.lxv2-header-search button {
    border: 0;
    background: transparent;
    cursor: pointer;
    font-size: 21px;
}

.lxv2-status-bar {
    padding: 8px 20px;
    color: #694c00;
    background: #fff5cc;
    text-align: center;
    font-size: 12px;
    font-weight: 700;
}

.lxv2-main {
    width: min(var(--lxv2-max), calc(100% - 40px));
    min-height: 65vh;
    margin: 0 auto;
    padding: 32px 0 70px;
}

.lxv2-hero {
    min-height: 530px;
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    overflow: hidden;
    border-radius: 34px;
    background:
        radial-gradient(circle at 20% 20%, rgba(255,255,255,.75), transparent 38%),
        linear-gradient(135deg, #ede1db, #d8c0ba);
    box-shadow: var(--lxv2-shadow);
}

.lxv2-hero__content {
    align-self: center;
    max-width: 680px;
    padding: clamp(36px, 7vw, 88px);
}

.lxv2-eyebrow {
    margin: 0 0 12px;
    color: var(--lxv2-accent);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .16em;
    text-transform: uppercase;
}

.lxv2-hero h1,
.lxv2-page-head h1,
.lxv2-pdp__info h1,
.lxv2-error h1 {
    margin: 0;
    font-family: Georgia, "Times New Roman", serif;
    font-weight: 500;
    line-height: 1.04;
    letter-spacing: -.035em;
}

.lxv2-hero h1 {
    max-width: 740px;
    font-size: clamp(44px, 6vw, 78px);
}

.lxv2-hero__content > p:not(.lxv2-eyebrow) {
    max-width: 600px;
    margin: 24px 0 0;
    color: #514942;
    font-size: 17px;
    line-height: 1.75;
}

.lxv2-actions {
    margin-top: 30px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.lxv2-button {
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 20px;
    border: 1px solid var(--lxv2-accent);
    border-radius: 999px;
    color: #fff;
    background: var(--lxv2-accent);
    font-weight: 800;
    cursor: pointer;
}

.lxv2-button:hover {
    background: var(--lxv2-accent-dark);
}

.lxv2-button--ghost,
.lxv2-button--outline {
    color: var(--lxv2-accent);
    background: transparent;
}

.lxv2-button--ghost:hover,
.lxv2-button--outline:hover {
    color: #fff;
}

.lxv2-button--wide {
    width: 100%;
    border-radius: 14px;
}

.lxv2-button:disabled {
    border-color: #cfc7bf;
    color: #8d857d;
    background: #ebe6e1;
    cursor: not-allowed;
}

.lxv2-hero__visual {
    position: relative;
    display: grid;
    place-content: center;
    color: rgba(84, 32, 43, .85);
    background:
        linear-gradient(145deg, transparent 25%, rgba(255,255,255,.3)),
        repeating-linear-gradient(115deg, rgba(255,255,255,.15) 0 2px, transparent 2px 12px);
    font-family: Georgia, serif;
    font-size: clamp(68px, 10vw, 150px);
    line-height: .72;
    letter-spacing: -.08em;
}

.lxv2-hero__visual span:last-child {
    margin-left: .55em;
}

.lxv2-section {
    margin-top: 72px;
}

.lxv2-section--flush {
    margin-top: 26px;
}

.lxv2-section__head {
    margin-bottom: 24px;
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 20px;
}

.lxv2-section__head h2,
.lxv2-specs h2,
.lxv2-support-media h2 {
    margin: 0;
    font-family: Georgia, serif;
    font-size: clamp(30px, 3vw, 44px);
    font-weight: 500;
}

.lxv2-section__head > a {
    color: var(--lxv2-accent);
    font-weight: 800;
}

.lxv2-collection-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.lxv2-collection-pill {
    min-height: 130px;
    display: grid;
    align-content: end;
    gap: 7px;
    padding: 22px;
    border: 1px solid var(--lxv2-line);
    border-radius: var(--lxv2-radius);
    background: var(--lxv2-surface);
    transition: transform .2s ease, box-shadow .2s ease;
}

.lxv2-collection-pill:hover {
    transform: translateY(-3px);
    box-shadow: var(--lxv2-shadow);
}

.lxv2-collection-pill strong {
    font-family: Georgia, serif;
    font-size: 24px;
    font-weight: 500;
}

.lxv2-collection-pill small {
    color: var(--lxv2-muted);
    line-height: 1.5;
}

.lxv2-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 28px 18px;
}

.lxv2-card {
    min-width: 0;
}

.lxv2-card__media {
    position: relative;
    aspect-ratio: 4 / 5;
    display: block;
    overflow: hidden;
    border-radius: 18px;
    background: #e9e4df;
}

.lxv2-card__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .5s ease;
}

.lxv2-card:hover .lxv2-card__media img {
    transform: scale(1.025);
}

.lxv2-card__badge {
    position: absolute;
    left: 12px;
    top: 12px;
    padding: 6px 10px;
    border-radius: 999px;
    color: #fff;
    background: var(--lxv2-accent);
    font-size: 11px;
    font-weight: 900;
}

.lxv2-card__body {
    padding: 14px 3px 0;
}

.lxv2-card__meta {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    color: var(--lxv2-muted);
    font-size: 11px;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.lxv2-card h3 {
    min-height: 46px;
    margin: 8px 0;
    font-family: Georgia, serif;
    font-size: 18px;
    font-weight: 500;
    line-height: 1.35;
}

.lxv2-price {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.lxv2-price strong {
    color: var(--lxv2-accent);
    font-size: 16px;
}

.lxv2-price del {
    color: #989088;
    font-size: 13px;
}

.lxv2-swatches {
    min-height: 25px;
    margin-top: 11px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lxv2-swatch {
    width: 18px;
    height: 18px;
    display: inline-block;
    border: 2px solid #fff;
    border-radius: 50%;
    background: var(--swatch);
    box-shadow: 0 0 0 1px #cfc8c0;
}

.lxv2-swatches small {
    color: var(--lxv2-muted);
}

.lxv2-trust {
    margin-top: 72px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    overflow: hidden;
    border: 1px solid var(--lxv2-line);
    border-radius: var(--lxv2-radius);
    background: var(--lxv2-surface);
}

.lxv2-trust article {
    padding: 28px;
    display: grid;
    gap: 7px;
    border-right: 1px solid var(--lxv2-line);
}

.lxv2-trust article:last-child {
    border-right: 0;
}

.lxv2-trust strong {
    font-family: Georgia, serif;
    font-size: 21px;
    font-weight: 500;
}

.lxv2-trust span {
    color: var(--lxv2-muted);
    font-size: 14px;
    line-height: 1.55;
}

.lxv2-page-head {
    padding: 52px 0 34px;
    text-align: center;
}

.lxv2-page-head h1 {
    font-size: clamp(42px, 6vw, 72px);
}

.lxv2-page-head > p:not(.lxv2-eyebrow) {
    max-width: 650px;
    margin: 16px auto 0;
    color: var(--lxv2-muted);
    line-height: 1.7;
}

.lxv2-collection-head {
    min-height: 320px;
    display: grid;
    align-content: center;
    overflow: hidden;
    padding-inline: 30px;
    border-radius: 28px;
    background:
        linear-gradient(rgba(247,245,241,.83), rgba(247,245,241,.88)),
        var(--hero-image, linear-gradient(135deg, #eee4de, #d8c0ba)) center / cover;
}

.lxv2-search-form {
    max-width: 760px;
    margin: 26px auto 0;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
}

.lxv2-search-form input {
    min-width: 0;
    height: 50px;
    padding: 0 18px;
    border: 1px solid var(--lxv2-line);
    border-radius: 999px;
    background: #fff;
    outline: 0;
}

.lxv2-search-form input:focus {
    border-color: var(--lxv2-accent);
    box-shadow: 0 0 0 3px rgba(123,47,62,.1);
}

.lxv2-form-message {
    color: #a13b2f !important;
    font-weight: 700;
}

.lxv2-load-more {
    margin-top: 42px;
    text-align: center;
}

.lxv2-empty {
    grid-column: 1 / -1;
    min-height: 180px;
    display: grid;
    place-items: center;
    padding: 30px;
    border: 1px dashed #cfc8c0;
    border-radius: var(--lxv2-radius);
    color: var(--lxv2-muted);
    text-align: center;
}

.lxv2-pdp {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(360px, .85fr);
    gap: clamp(30px, 5vw, 76px);
    align-items: start;
}

.lxv2-gallery {
    min-width: 0;
}

.lxv2-gallery__main {
    aspect-ratio: 4 / 5;
    overflow: hidden;
    border-radius: 26px;
    background: #e9e4df;
}

.lxv2-gallery__main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lxv2-gallery__thumbs {
    margin-top: 12px;
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: 76px;
    gap: 9px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.lxv2-gallery__thumbs button {
    aspect-ratio: 4 / 5;
    overflow: hidden;
    padding: 0;
    border: 2px solid transparent;
    border-radius: 10px;
    background: transparent;
    cursor: pointer;
}

.lxv2-gallery__thumbs button.active {
    border-color: var(--lxv2-accent);
}

.lxv2-gallery__thumbs img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lxv2-pdp__info {
    position: sticky;
    top: 108px;
    padding: 10px 0;
}

.lxv2-pdp__info h1 {
    font-size: clamp(38px, 4.2vw, 58px);
}

.lxv2-pdp__price {
    margin-top: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.lxv2-pdp__price strong {
    color: var(--lxv2-accent);
    font-size: 25px;
}

.lxv2-pdp__price del {
    color: #938b83;
}

.lxv2-stock {
    color: var(--lxv2-success);
    font-size: 14px;
    font-weight: 800;
}

.lxv2-description {
    margin-top: 24px;
    color: #504a44;
    line-height: 1.75;
}

.lxv2-selector {
    margin-top: 28px;
}

.lxv2-selector__label {
    margin-bottom: 11px;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    font-size: 14px;
}

.lxv2-selector__label span {
    color: var(--lxv2-muted);
}

.lxv2-color-options,
.lxv2-size-options {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.lxv2-color-option {
    min-width: 78px;
    min-height: 68px;
    display: grid;
    justify-items: center;
    align-content: center;
    gap: 6px;
    padding: 9px;
    border: 1px solid var(--lxv2-line);
    border-radius: 13px;
    background: #fff;
    cursor: pointer;
}

.lxv2-color-option > span {
    width: 25px;
    height: 25px;
    border: 2px solid #fff;
    border-radius: 50%;
    background: var(--swatch);
    box-shadow: 0 0 0 1px #bfb7af;
}

.lxv2-color-option small {
    max-width: 90px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.lxv2-color-option.active {
    border-color: var(--lxv2-accent);
    box-shadow: 0 0 0 2px rgba(123,47,62,.12);
}

.lxv2-color-option:disabled {
    opacity: .38;
    cursor: not-allowed;
}

.lxv2-size-option {
    min-width: 52px;
    height: 44px;
    border: 1px solid var(--lxv2-line);
    border-radius: 11px;
    background: #fff;
    cursor: pointer;
    font-weight: 800;
}

.lxv2-size-option.active {
    border-color: var(--lxv2-accent);
    color: #fff;
    background: var(--lxv2-accent);
}

.lxv2-size-option:disabled {
    text-decoration: line-through;
    opacity: .38;
    cursor: not-allowed;
}

.lxv2-selection-summary {
    margin: 20px 0 12px;
    display: grid;
    gap: 4px;
    padding: 13px 15px;
    border-radius: 12px;
    background: var(--lxv2-soft);
}

.lxv2-selection-summary small {
    color: var(--lxv2-muted);
}

.lxv2-next-phase-note {
    margin: 10px 0 0;
    color: var(--lxv2-muted);
    text-align: center;
    font-size: 12px;
}

.lxv2-specs,
.lxv2-support-media {
    margin-top: 36px;
    padding-top: 28px;
    border-top: 1px solid var(--lxv2-line);
}

.lxv2-specs h2,
.lxv2-support-media h2 {
    font-size: 27px;
}

.lxv2-specs dl {
    margin: 18px 0 0;
}

.lxv2-specs dl > div {
    display: grid;
    grid-template-columns: 130px 1fr;
    gap: 15px;
    padding: 11px 0;
    border-bottom: 1px solid var(--lxv2-line);
}

.lxv2-specs dt {
    color: var(--lxv2-muted);
}

.lxv2-specs dd {
    margin: 0;
    font-weight: 700;
}

.lxv2-support-media {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.lxv2-support-media h2 {
    width: 100%;
}

.lxv2-support-media a {
    padding: 10px 13px;
    border: 1px solid var(--lxv2-line);
    border-radius: 10px;
    background: #fff;
    font-size: 13px;
    font-weight: 800;
}

.lxv2-error {
    min-height: 520px;
    display: grid;
    place-content: center;
    justify-items: center;
    text-align: center;
}

.lxv2-error > span {
    color: #d5c4bd;
    font-family: Georgia, serif;
    font-size: 120px;
    line-height: .9;
}

.lxv2-error h1 {
    font-size: clamp(38px, 5vw, 64px);
}

.lxv2-error > p:not(.lxv2-eyebrow) {
    max-width: 600px;
    color: var(--lxv2-muted);
    line-height: 1.7;
}

.lxv2-error small {
    color: var(--lxv2-muted);
}

.lxv2-footer {
    width: min(var(--lxv2-max), calc(100% - 40px));
    margin: 0 auto 30px;
    display: flex;
    justify-content: space-between;
    gap: 30px;
    padding: 34px;
    border-radius: var(--lxv2-radius);
    color: #fff;
    background: #201c19;
}

.lxv2-footer strong {
    font-family: Georgia, serif;
    font-size: 24px;
    letter-spacing: .06em;
}

.lxv2-footer p {
    color: #bcb2aa;
}

.lxv2-footer__links {
    display: flex;
    align-items: center;
    gap: 22px;
}

.lxv2-bottom-nav {
    display: none;
}

@media (max-width: 1050px) {
    .lxv2-header__inner {
        grid-template-columns: auto 1fr;
    }

    .lxv2-nav {
        justify-content: flex-end;
    }

    .lxv2-header-search {
        display: none;
    }

    .lxv2-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .lxv2-pdp {
        grid-template-columns: 1fr 420px;
        gap: 34px;
    }
}

@media (max-width: 780px) {
    .lxv2-header__inner,
    .lxv2-main,
    .lxv2-footer {
        width: min(100% - 24px, var(--lxv2-max));
    }

    .lxv2-header__inner {
        min-height: 64px;
    }

    .lxv2-nav {
        display: none;
    }

    .lxv2-main {
        padding-top: 14px;
        padding-bottom: 90px;
    }

    .lxv2-hero {
        min-height: auto;
        grid-template-columns: 1fr;
    }

    .lxv2-hero__content {
        padding: 42px 24px;
    }

    .lxv2-hero h1 {
        font-size: 48px;
    }

    .lxv2-hero__visual {
        min-height: 240px;
        font-size: 88px;
    }

    .lxv2-section {
        margin-top: 48px;
    }

    .lxv2-collection-row {
        grid-template-columns: 1fr;
    }

    .lxv2-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px 10px;
    }

    .lxv2-card__media {
        border-radius: 13px;
    }

    .lxv2-card h3 {
        min-height: 42px;
        font-size: 16px;
    }

    .lxv2-trust {
        grid-template-columns: 1fr;
    }

    .lxv2-trust article {
        border-right: 0;
        border-bottom: 1px solid var(--lxv2-line);
    }

    .lxv2-trust article:last-child {
        border-bottom: 0;
    }

    .lxv2-pdp {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .lxv2-pdp__info {
        position: static;
    }

    .lxv2-gallery__main {
        border-radius: 18px;
    }

    .lxv2-search-form {
        grid-template-columns: 1fr;
    }

    .lxv2-footer {
        margin-bottom: 82px;
        flex-direction: column;
    }

    .lxv2-footer__links {
        flex-wrap: wrap;
    }

    .lxv2-bottom-nav {
        position: fixed;
        left: 50%;
        bottom: max(10px, env(safe-area-inset-bottom));
        z-index: 120;
        width: min(360px, calc(100% - 24px));
        min-height: 64px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        transform: translateX(-50%);
        overflow: hidden;
        border: 1px solid rgba(230,224,216,.9);
        border-radius: 22px;
        background: rgba(255,255,255,.94);
        box-shadow: 0 16px 50px rgba(34, 27, 23, .18);
        backdrop-filter: blur(16px);
    }

    .lxv2-bottom-nav a {
        display: grid;
        place-content: center;
        justify-items: center;
        gap: 2px;
        color: var(--lxv2-muted);
    }

    .lxv2-bottom-nav a.active {
        color: var(--lxv2-accent);
        background: #f8f1ee;
    }

    .lxv2-bottom-nav span {
        font-size: 20px;
    }

    .lxv2-bottom-nav small {
        font-size: 10px;
        font-weight: 800;
    }
}

@media (max-width: 420px) {
    .lxv2-brand__text small {
        display: none;
    }

    .lxv2-hero h1 {
        font-size: 42px;
    }

    .lxv2-section__head {
        align-items: start;
    }

    .lxv2-section__head > a {
        font-size: 12px;
    }

    .lxv2-card__meta {
        display: grid;
        gap: 3px;
    }

    .lxv2-card h3 {
        min-height: 62px;
    }

    .lxv2-swatches {
        gap: 4px;
    }

    .lxv2-swatch {
        width: 16px;
        height: 16px;
    }

    .lxv2-page-head {
        padding-top: 34px;
    }
}
CSS

cat > "$JS_FILE" <<'JS'
(function () {
    'use strict';

    const root = document.querySelector('[data-lxv2-product]');

    if (!root) {
        return;
    }

    const mainImage = root.querySelector('[data-lxv2-main-image]');
    const colorLabel = root.querySelector('[data-lxv2-color-label]');
    const sizeLabel = root.querySelector('[data-lxv2-size-label]');
    const sizesRoot = root.querySelector('[data-lxv2-sizes]');
    const summary = root.querySelector('[data-lxv2-selection]');
    const selectedText = root.querySelector('[data-lxv2-selected-text]');
    const selectedStock = root.querySelector('[data-lxv2-selected-stock]');
    const buyButton = root.querySelector('[data-lxv2-buy]');
    const priceRoot = root.querySelector('[data-lxv2-price]');

    let selectedColor = null;
    let selectedSize = null;

    function money(value) {
        const amount = Number(value || 0);

        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0
        }).format(amount);
    }

    function setMainImage(url) {
        if (!mainImage || !url) {
            return;
        }

        mainImage.src = url;
    }

    function activateThumb(button) {
        root.querySelectorAll('[data-lxv2-thumb]').forEach((item) => {
            item.classList.toggle('active', item === button);
        });
    }

    function updateSelection() {
        const ready = selectedColor && selectedSize;

        if (summary) {
            summary.hidden = !ready;
        }

        if (ready) {
            selectedText.textContent = selectedColor.label + ' · Size ' + selectedSize.size;
            selectedStock.textContent = selectedSize.available > 0
                ? 'Còn ' + Math.floor(selectedSize.available) + ' sản phẩm'
                : 'Tạm hết hàng';

            if (priceRoot && selectedSize.price_current > 0) {
                priceRoot.innerHTML = '<strong>' + money(selectedSize.price_current) + '</strong>'
                    + (
                        selectedSize.price_original > selectedSize.price_current
                            ? '<del>' + money(selectedSize.price_original) + '</del>'
                            : ''
                    );
            }
        }

        if (buyButton) {
            buyButton.disabled = true;
            buyButton.textContent = ready
                ? 'Giỏ hàng sẽ mở ở giai đoạn tiếp theo'
                : 'Chọn màu và kích thước';
        }
    }

    function renderSizes(sizes) {
        if (!sizesRoot) {
            return;
        }

        selectedSize = null;
        sizesRoot.innerHTML = '';

        if (!Array.isArray(sizes) || sizes.length === 0) {
            if (sizeLabel) {
                sizeLabel.textContent = 'Chưa có kích thước khả dụng';
            }

            updateSelection();
            return;
        }

        if (sizeLabel) {
            sizeLabel.textContent = 'Chọn kích thước';
        }

        sizes.forEach((size) => {
            const button = document.createElement('button');
            const sellable = Boolean(size.sellable) && Number(size.available || 0) > 0;

            button.type = 'button';
            button.className = 'lxv2-size-option';
            button.textContent = size.size || '—';
            button.disabled = !sellable;
            button.setAttribute('aria-label', 'Size ' + (size.size || ''));

            button.addEventListener('click', () => {
                sizesRoot.querySelectorAll('.lxv2-size-option').forEach((item) => {
                    item.classList.toggle('active', item === button);
                });

                selectedSize = size;
                if (sizeLabel) {
                    sizeLabel.textContent = 'Size ' + size.size;
                }
                updateSelection();
            });

            sizesRoot.appendChild(button);
        });

        updateSelection();
    }

    root.querySelectorAll('[data-lxv2-thumb]').forEach((button) => {
        button.addEventListener('click', () => {
            activateThumb(button);
            setMainImage(button.dataset.image);
        });
    });

    root.querySelectorAll('[data-lxv2-color]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.disabled) {
                return;
            }

            root.querySelectorAll('[data-lxv2-color]').forEach((item) => {
                item.classList.toggle('active', item === button);
            });

            let sizes = [];

            try {
                sizes = JSON.parse(button.dataset.sizes || '[]');
            } catch (error) {
                sizes = [];
            }

            selectedColor = {
                code: button.dataset.code || '',
                label: button.dataset.label || ''
            };

            if (colorLabel) {
                colorLabel.textContent = selectedColor.label;
            }

            setMainImage(button.dataset.cover);
            renderSizes(sizes);

            const matchingThumb = root.querySelector(
                '[data-lxv2-thumb][data-color="' + CSS.escape(selectedColor.code) + '"]'
            );

            if (matchingThumb) {
                activateThumb(matchingThumb);
            }
        });
    });
})();
JS

export SFV2_WEB_ROUTES="$WEB_ROUTES"
export SFV2_ENV_EXAMPLE="$ENV_EXAMPLE"
export SFV2_ROUTE_START="$ROUTE_MARKER_START"
export SFV2_ROUTE_END="$ROUTE_MARKER_END"
export SFV2_ENV_START="$ENV_MARKER_START"
export SFV2_ENV_END="$ENV_MARKER_END"

php <<'PHP'
<?php

function replaceMarkerBlock(
    string $source,
    string $start,
    string $end,
    string $block
): string {
    $startCount = substr_count($source, $start);
    $endCount = substr_count($source, $end);

    if ($startCount !== $endCount || $startCount > 1) {
        throw new RuntimeException(
            "Marker không cân bằng: {$start}"
        );
    }

    if ($startCount === 0) {
        return rtrim($source) . PHP_EOL . PHP_EOL
            . trim($block) . PHP_EOL;
    }

    $pattern = '/'
        . preg_quote($start, '/')
        . '.*?'
        . preg_quote($end, '/')
        . '/s';

    $patched = preg_replace(
        $pattern,
        trim($block),
        $source,
        1,
        $count
    );

    if (! is_string($patched) || $count !== 1) {
        throw new RuntimeException(
            "Không rebuild được marker block: {$start}"
        );
    }

    return $patched;
}

$webRoutes = getenv('SFV2_WEB_ROUTES');
$envExample = getenv('SFV2_ENV_EXAMPLE');
$routeStart = getenv('SFV2_ROUTE_START');
$routeEnd = getenv('SFV2_ROUTE_END');
$envStart = getenv('SFV2_ENV_START');
$envEnd = getenv('SFV2_ENV_END');

$routeSource = file_get_contents($webRoutes);

if (! is_string($routeSource)) {
    throw new RuntimeException(
        'Không đọc được routes/web.php.'
    );
}

$routeBlock = <<<'BLOCK'
/* AI_PATCH_LINXEN_STOREFRONT_V2_ROUTES_V1_START */
require __DIR__ . '/commerce_v2.php';
/* AI_PATCH_LINXEN_STOREFRONT_V2_ROUTES_V1_END */
BLOCK;

$routePatched = replaceMarkerBlock(
    $routeSource,
    $routeStart,
    $routeEnd,
    $routeBlock
);

if (
    file_put_contents($webRoutes, $routePatched)
    !== strlen($routePatched)
) {
    throw new RuntimeException(
        'Không ghi đầy đủ routes/web.php.'
    );
}

$envSource = is_file($envExample)
    ? file_get_contents($envExample)
    : '';

if (! is_string($envSource)) {
    throw new RuntimeException(
        'Không đọc được .env.example.'
    );
}

$envBlock = <<<'BLOCK'
# AI_PATCH_LINXEN_COMMERCE_V2_ENV_V1_START
ERP_COMMERCE_V2_ENABLED=true
ERP_COMMERCE_V2_BASE_URL=https://3mg.ai/api/commerce/v2
ERP_COMMERCE_V2_SITE=linxen
ERP_COMMERCE_V2_TOKEN=
ERP_COMMERCE_V2_TIMEOUT=8
ERP_COMMERCE_V2_CONNECT_TIMEOUT=3
ERP_COMMERCE_V2_RETRY_TIMES=2
ERP_COMMERCE_V2_RETRY_SLEEP_MS=250
ERP_COMMERCE_V2_CACHE_STORE=file
ERP_COMMERCE_V2_FRESH_CACHE_SECONDS=10
ERP_COMMERCE_V2_STALE_CACHE_SECONDS=300
LINXEN_STOREFRONT_V2_PREFIX=v2
LINXEN_STOREFRONT_V2_BRAND_NAME="LIN XÉN"
LINXEN_STOREFRONT_V2_SUPPORT_PHONE=
LINXEN_STOREFRONT_V2_SUPPORT_URL=
# AI_PATCH_LINXEN_COMMERCE_V2_ENV_V1_END
BLOCK;

$envPatched = replaceMarkerBlock(
    $envSource,
    $envStart,
    $envEnd,
    $envBlock
);

if (
    file_put_contents($envExample, $envPatched)
    !== strlen($envPatched)
) {
    throw new RuntimeException(
        'Không ghi đầy đủ .env.example.'
    );
}
PHP

for file in \
  "$CONFIG" \
  "$EXCEPTION" \
  "$CLIENT" \
  "$PRESENTER" \
  "$CONTROLLER" \
  "$SMOKE_COMMAND" \
  "$ROUTE_FILE" \
  "$WEB_ROUTES"
do
    php -l "$file"
done

grep -Fq "$ROUTE_MARKER_START" "$WEB_ROUTES"
grep -Fq "$ROUTE_MARKER_END" "$WEB_ROUTES"
grep -Fq "$ENV_MARKER_START" "$ENV_EXAMPLE"
grep -Fq "$ENV_MARKER_END" "$ENV_EXAMPLE"

for forbidden in \
  'kiotviet_products' \
  '/api/storefront/' \
  'Illuminate\Support\Facades\DB' \
  'CollectionEngine' \
  'RsCollectionService'
do
    if grep -R -F "$forbidden" \
        "$CLIENT" \
        "$PRESENTER" \
        "$CONTROLLER" \
        "$ROUTE_FILE" \
        "$LAYOUT" \
        "$HOME_VIEW" \
        "$SHOP_VIEW" \
        "$PRODUCT_VIEW" \
        "$SEARCH_VIEW" \
        "$COLLECTION_VIEW" \
        >/dev/null 2>&1
    then
        printf 'ERROR: Legacy/direct DB token found: %s\n' \
          "$forbidden" >&2
        exit 1
    fi
done

if grep -R -E \
    'ERP_COMMERCE_V2_TOKEN|ERP_API_TOKEN' \
    resources/views/commerce_v2 \
    public/commerce-v2 \
    >/dev/null 2>&1
then
    printf '%s\n' \
      'ERROR: Token marker bị lộ trong view/public assets.' \
      >&2
    exit 1
fi

export LINXEN_BOOTSTRAP_FILE="$BOOTSTRAP"
export LINXEN_COMMAND_MARKER_START="$COMMAND_MARKER_START"
export LINXEN_COMMAND_MARKER_END="$COMMAND_MARKER_END"

php <<'PHP'
<?php

$path = getenv('LINXEN_BOOTSTRAP_FILE');
$start = getenv('LINXEN_COMMAND_MARKER_START');
$end = getenv('LINXEN_COMMAND_MARKER_END');

if (! is_string($path) || ! is_file($path)) {
    fwrite(STDERR, "ERROR: bootstrap/app.php không hợp lệ.\n");
    exit(1);
}

$source = file_get_contents($path);

if (! is_string($source)) {
    fwrite(STDERR, "ERROR: Không đọc được bootstrap/app.php.\n");
    exit(1);
}

$commandDirectory = "__DIR__.'/../app/Console/Commands'";

$startCount = substr_count($source, $start);
$endCount = substr_count($source, $end);

if ($startCount !== $endCount || $startCount > 1) {
    fwrite(
        STDERR,
        "ERROR: Command discovery marker không cân bằng hoặc bị lặp.\n"
    );
    exit(1);
}

if ($startCount === 1) {
    if (! str_contains($source, $commandDirectory)) {
        fwrite(
            STDERR,
            "ERROR: Marker command discovery có nhưng thiếu directory contract.\n"
        );
        exit(1);
    }

    echo "COMMAND_DISCOVERY_PATCH=ALREADY_APPLIED\n";
    exit(0);
}

if (str_contains($source, $commandDirectory)) {
    echo "COMMAND_DIRECTORY=ALREADY_REGISTERED\n";
    exit(0);
}

$anchor = "    ->withMiddleware(";
$anchorCount = substr_count($source, $anchor);

if ($anchorCount !== 1) {
    fwrite(
        STDERR,
        "ERROR: bootstrap withMiddleware anchor expected 1; found "
            . $anchorCount
            . "\n"
    );
    exit(1);
}

$block = <<<'BLOCK'
    /* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_START */
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    /* AI_PATCH_LINXEN_COMMERCE_V2_COMMAND_DISCOVERY_V1_END */
BLOCK;

$patched = str_replace(
    $anchor,
    $block . "\n" . $anchor,
    $source
);

$written = file_put_contents($path, $patched);

if ($written === false || $written !== strlen($patched)) {
    fwrite(STDERR, "ERROR: Không ghi đầy đủ bootstrap/app.php.\n");
    exit(1);
}

echo "COMMAND_DISCOVERY_PATCH=APPLIED\n";
PHP

php -l "$BOOTSTRAP"

(
    umask 0022
    CACHE_STORE=file     SESSION_DRIVER=file     php artisan optimize:clear
)

php <<'PHP'
<?php

$root = getcwd();

require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';

$kernel = $app->make(
    Illuminate\Contracts\Console\Kernel::class
);
$kernel->bootstrap();

$prefix = trim(
    (string) config(
        'commerce_v2.stage_prefix',
        'v2'
    ),
    '/'
);

$expected = [
    $prefix => App\Http\Controllers\CommerceV2\CatalogPageController::class
        . '@home',
    $prefix . '/shop' => App\Http\Controllers\CommerceV2\CatalogPageController::class
        . '@shop',
    $prefix . '/search' => App\Http\Controllers\CommerceV2\CatalogPageController::class
        . '@search',
    $prefix . '/collections/{slug}' => App\Http\Controllers\CommerceV2\CatalogPageController::class
        . '@collection',
    $prefix . '/p/{slug}' => App\Http\Controllers\CommerceV2\CatalogPageController::class
        . '@product',
];

$found = [];

foreach ($app['router']->getRoutes() as $route) {
    $uri = $route->uri();

    if (! array_key_exists($uri, $expected)) {
        continue;
    }

    if (! in_array('GET', $route->methods(), true)) {
        fwrite(
            STDERR,
            "ERROR: V2 route không hỗ trợ GET: {$uri}\n"
        );
        exit(1);
    }

    $action = $route->getActionName();

    if ($action !== $expected[$uri]) {
        fwrite(
            STDERR,
            "ERROR: V2 route action sai: {$uri}"
                . " expected={$expected[$uri]}"
                . " actual={$action}\n"
        );
        exit(1);
    }

    $found[$uri] = $action;
}

$missing = array_diff_key($expected, $found);

if ($missing !== []) {
    fwrite(
        STDERR,
        'ERROR: Thiếu Storefront V2 route: '
            . implode(', ', array_keys($missing))
            . PHP_EOL
    );
    exit(1);
}

foreach ($found as $uri => $action) {
    echo "ROUTE_URI={$uri}\n";
    echo "ROUTE_ACTION={$action}\n";
}

echo "STOREFRONT_V2_TARGETED_ROUTE_DISCOVERY=PASS\n";
PHP

php artisan list --raw \
  | grep -E '^commerce-v2:smoke([[:space:]]|$)' \
  >/dev/null

printf '%s\n' 'COMMAND_DISCOVERY=PASS'
printf '%s\n' 'COMMAND_DISCOVERY_INTEGRATED=YES'

trap - ERR

printf '%s\n' 'LINXEN_STOREFRONT_V2_SF01_SF02_V1_1_PATCH=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'TARGET_CODE_TREE=STOREFRONT'
printf '%s\n' 'STAGING_PREFIX=/v2'
printf '%s\n' 'HOMEPAGE_CUTOVER=NO'
printf '%s\n' 'V1_ROUTES_CHANGED=NO'
printf '%s\n' 'ERP_DIRECT_DB_ACCESS=NO'
printf '%s\n' 'LEGACY_STOREFRONT_API_USED=NO'
printf '%s\n' 'SERVER_SIDE_ERP_TOKEN_ONLY=YES'
printf '%s\n' 'PAGES=HOME,SHOP,PDP,SEARCH,COLLECTION'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'PATCH_PROVIDER_CALL=NONE'
printf '%s\n' 'NPM_BUILD=NOT_REQUIRED'
printf '%s\n' 'CART_CHECKOUT_AUTH=NOT_INCLUDED'

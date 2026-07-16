#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NAME='linxen_storefront_v2_pdp_render_hotfix_v1'

CONTROLLER='app/Http/Controllers/CommerceV2/CatalogPageController.php'
SMOKE_COMMAND='app/Console/Commands/CommerceV2SmokeCommand.php'
PRODUCT_VIEW='resources/views/commerce_v2/pages/product.blade.php'
JS_FILE='public/commerce-v2/storefront-v2.js'

BACKUP_ROOT="storage/app/ai_patch_backups/${PATCH_NAME}_$(date +%Y%m%d_%H%M%S)"
MANIFEST="${BACKUP_ROOT}/manifest.tsv"
PATCH_WRITTEN=0

rollback() {
    STATUS=$?

    if [ "$PATCH_WRITTEN" -eq 1 ] && [ -f "$MANIFEST" ]; then
        printf '%s\n' 'Có lỗi bắt buộc. Đang rollback PDP render hotfix...' >&2

        while IFS=$'\t' read -r KIND FILE; do
            if [ "$KIND" = 'existing' ] && [ -f "$BACKUP_ROOT/$FILE" ]; then
                mkdir -p "$(dirname "$FILE")"
                cp -p "$BACKUP_ROOT/$FILE" "$FILE"
            fi
        done < "$MANIFEST"
    fi

    exit "$STATUS"
}

trap rollback ERR

test -f artisan || {
    printf '%s\n' 'ERROR: Hãy chạy patch từ root Laravel Lin Xén.' >&2
    exit 1
}

for FILE in \
  "$CONTROLLER" \
  "$SMOKE_COMMAND" \
  "$PRODUCT_VIEW" \
  "$JS_FILE"
do
    test -f "$FILE" || {
        printf 'ERROR: Thiếu source Storefront V2: %s\n' "$FILE" >&2
        exit 1
    }

    mkdir -p "$BACKUP_ROOT/$(dirname "$FILE")"
    cp -p "$FILE" "$BACKUP_ROOT/$FILE"
    printf 'existing\t%s\n' "$FILE" >> "$MANIFEST"
done

PATCH_WRITTEN=1

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

            $productPayloadJson = $this->productPayloadJson(
                $product
            );

            return response()->view(
                'commerce_v2.pages.product',
                [
                    'product' => $product,
                    'productPayloadJson' => $productPayloadJson,
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
                ]
            );
        } catch (CommerceV2ClientException $e) {
            return $this->errorView($e);
        } catch (Throwable $e) {
            report($e);

            return $this->errorView(
                new CommerceV2ClientException(
                    'Trang sản phẩm đang được cập nhật.',
                    500,
                    'storefront_product_render_failed',
                    [],
                    $e
                )
            );
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

    protected function productPayloadJson(
        array $product
    ): string {
        $encoded = json_encode(
            [
                'id' => (string) data_get(
                    $product,
                    'id'
                ),
                'name' => (string) data_get(
                    $product,
                    'name'
                ),
                'colors' => array_values(
                    (array) data_get(
                        $product,
                        'colors',
                        []
                    )
                ),
            ],
            JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_AMP
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        return $encoded;
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

use App\Services\CommerceV2\CommerceV2Presenter;
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
        ErpCommerceClient $client,
        CommerceV2Presenter $presenter
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

            $presentedProduct = $presenter->productDetail(
                (array) data_get($product, 'data', [])
            );

            $productPayloadJson = json_encode(
                [
                    'id' => data_get(
                        $presentedProduct,
                        'id'
                    ),
                    'name' => data_get(
                        $presentedProduct,
                        'name'
                    ),
                    'colors' => data_get(
                        $presentedProduct,
                        'colors',
                        []
                    ),
                ],
                JSON_HEX_TAG
                | JSON_HEX_APOS
                | JSON_HEX_AMP
                | JSON_HEX_QUOT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );

            $productViewHtml = view(
                'commerce_v2.pages.product',
                [
                    'product' => $presentedProduct,
                    'productPayloadJson' => $productPayloadJson,
                    'cacheStatus' => null,
                    'pageTitle' => data_get(
                        $presentedProduct,
                        'name',
                        'Sản phẩm'
                    ) . ' — LIN XÉN',
                    'pageDescription' => 'Storefront V2 render smoke.',
                    'ogImage' => data_get(
                        $presentedProduct,
                        'cover_url',
                        ''
                    ),
                ]
            )->render();

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
                'product_presenter' => (
                    data_get(
                        $presentedProduct,
                        'id'
                    ) === $productId
                    && data_get(
                        $presentedProduct,
                        'public_ready'
                    ) === true
                    && count(
                        (array) data_get(
                            $presentedProduct,
                            'colors',
                            []
                        )
                    ) > 0
                ),
                'product_view_render' => (
                    str_contains(
                        $productViewHtml,
                        'data-lxv2-product'
                    )
                    && str_contains(
                        $productViewHtml,
                        'lxv2ProductData'
                    )
                    && str_contains(
                        $productViewHtml,
                        $productId
                    )
                ),
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
                        class="{{ $index === 0 ? 'active' : '' }}"
                        data-lxv2-thumb
                        data-image="{{ $media['url'] }}"
                        data-color="{{ $media['color_code'] }}"
                        aria-label="Xem ảnh {{ $index + 1 }}"
                    >
                        <img
                            src="{{ $media['thumb_url'] }}"
                            alt=""
                            loading="lazy"
                        >
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
            @if(
                $product['has_sale']
                && $product['original_min'] > $product['price_min']
            )
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
            <div class="lxv2-description">
                {{ $product['description'] }}
            </div>
        @endif

        <div class="lxv2-selector">
            <div class="lxv2-selector__label">
                <strong>Màu sắc</strong>
                <span data-lxv2-color-label>Chọn màu</span>
            </div>

            <div class="lxv2-color-options">
                @foreach($product['colors'] as $colorIndex => $color)
                    <button
                        type="button"
                        class="lxv2-color-option"
                        data-lxv2-color
                        data-color-index="{{ $colorIndex }}"
                        data-code="{{ $color['code'] }}"
                        data-label="{{ $color['label'] }}"
                        data-cover="{{ $color['cover_url'] }}"
                        {{ $color['sellable'] ? '' : 'disabled' }}
                    >
                        <span
                            style="--swatch:{{ $color['hex'] ?: '#d8d0ca' }}"
                        ></span>
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
            <div
                class="lxv2-size-options"
                data-lxv2-sizes
            ></div>
        </div>

        <div
            class="lxv2-selection-summary"
            data-lxv2-selection
            hidden
        >
            <span data-lxv2-selected-text></span>
            <small data-lxv2-selected-stock></small>
        </div>

        <button
            class="lxv2-button lxv2-button--wide"
            type="button"
            disabled
            data-lxv2-buy
        >
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
                    <a
                        href="{{ $media['url'] }}"
                        target="_blank"
                        rel="noopener"
                    >
                        {{
                            $media['support_role'] === 'size_chart'
                                ? 'Xem bảng kích thước'
                                : 'Xem hướng dẫn'
                        }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

<script
    type="application/json"
    id="lxv2ProductData"
>{!! $productPayloadJson !!}</script>
@endsection

BLADE

cat > "$JS_FILE" <<'JS'
(function () {
    'use strict';

    const root = document.querySelector('[data-lxv2-product]');

    if (!root) {
        return;
    }

    const payloadElement = document.getElementById('lxv2ProductData');
    let productData = { colors: [] };

    if (payloadElement) {
        try {
            productData = JSON.parse(
                payloadElement.textContent || '{"colors":[]}'
            );
        } catch (error) {
            productData = { colors: [] };
        }
    }

    const productColors = Array.isArray(productData.colors)
        ? productData.colors
        : [];

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
            if (selectedText) {
                selectedText.textContent = selectedColor.label
                    + ' · Size '
                    + selectedSize.size;
            }

            if (selectedStock) {
                selectedStock.textContent = selectedSize.available > 0
                    ? 'Còn '
                        + Math.floor(selectedSize.available)
                        + ' sản phẩm'
                    : 'Tạm hết hàng';
            }

            if (priceRoot && selectedSize.price_current > 0) {
                priceRoot.innerHTML = '<strong>'
                    + money(selectedSize.price_current)
                    + '</strong>'
                    + (
                        selectedSize.price_original
                            > selectedSize.price_current
                            ? '<del>'
                                + money(selectedSize.price_original)
                                + '</del>'
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
            const sellable = Boolean(size.sellable)
                && Number(size.available || 0) > 0;

            button.type = 'button';
            button.className = 'lxv2-size-option';
            button.textContent = size.size || '—';
            button.disabled = !sellable;
            button.setAttribute(
                'aria-label',
                'Size ' + (size.size || '')
            );

            button.addEventListener('click', () => {
                sizesRoot
                    .querySelectorAll('.lxv2-size-option')
                    .forEach((item) => {
                        item.classList.toggle(
                            'active',
                            item === button
                        );
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

            const colorIndex = Number(
                button.dataset.colorIndex || -1
            );
            const color = (
                Number.isInteger(colorIndex)
                && colorIndex >= 0
            ) ? productColors[colorIndex] : null;
            const sizes = color && Array.isArray(color.sizes)
                ? color.sizes
                : [];

            selectedColor = {
                code: button.dataset.code || '',
                label: button.dataset.label || ''
            };

            if (colorLabel) {
                colorLabel.textContent = selectedColor.label;
            }

            setMainImage(button.dataset.cover);
            renderSizes(sizes);

            const escapedCode = window.CSS
                && typeof window.CSS.escape === 'function'
                ? window.CSS.escape(selectedColor.code)
                : selectedColor.code.replace(
                    /[^A-Za-z0-9_-]/g,
                    ''
                );

            const matchingThumb = root.querySelector(
                '[data-lxv2-thumb][data-color="'
                    + escapedCode
                    + '"]'
            );

            if (matchingThumb) {
                activateThumb(matchingThumb);
            }
        });
    });
})();

JS

php -l "$CONTROLLER"
php -l "$SMOKE_COMMAND"

grep -Fq 'productPayloadJson' "$CONTROLLER"
grep -Fq 'product_view_render' "$SMOKE_COMMAND"
grep -Fq '{!! $productPayloadJson !!}' "$PRODUCT_VIEW"
grep -Fq 'data-color-index' "$PRODUCT_VIEW"
grep -Fq 'productData.colors' "$JS_FILE"

(
    umask 0022

    if [ "$(id -u)" -eq 0 ] \
        && command -v sudo >/dev/null 2>&1 \
        && id www-data >/dev/null 2>&1
    then
        sudo -u www-data env \
          HOME="$(pwd)" \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan optimize:clear
    else
        env \
          HOME="$(pwd)" \
          CACHE_STORE=file \
          SESSION_DRIVER=file \
          php artisan optimize:clear
    fi
)

ARTISAN=(php artisan)

if [ "$(id -u)" -eq 0 ] \
    && command -v sudo >/dev/null 2>&1 \
    && id www-data >/dev/null 2>&1
then
    ARTISAN=(
      sudo -u www-data env
      "HOME=$(pwd)"
      CACHE_STORE=file
      SESSION_DRIVER=file
      php artisan
    )
fi

"${ARTISAN[@]}" view:cache
"${ARTISAN[@]}" view:clear

trap - ERR

printf '%s\n' 'LINXEN_STOREFRONT_V2_PDP_RENDER_HOTFIX=PASS'
printf 'BACKUP_DIR=%s\n' "$BACKUP_ROOT"
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'ERP_PROVIDER_CALL_DURING_PATCH=NONE'
printf '%s\n' 'PDP_JSON_TRANSPORT=SAFE_SCRIPT_BLOB'
printf '%s\n' 'PDP_RENDER_GUARD=ENABLED'
printf '%s\n' 'SMOKE_PRODUCT_VIEW_RENDER=ENABLED'
printf '%s\n' 'V1_STOREFRONT=UNCHANGED'

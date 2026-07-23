<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CommerceV2Presenter;
use App\Services\CommerceV2\ErpCommerceClient;
use App\Services\CommerceV2\Pdp\PdpPageComposer;
use App\Services\CommerceV2\Pdp\PdpPresentationResolver;
use App\Services\CommerceV2\Pdp\PdpViewModelBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Throwable;

class CatalogPageController extends Controller
{
    public function __construct(
        protected ErpCommerceClient $client,
        protected CommerceV2Presenter $presenter,
        protected PdpViewModelBuilder $pdpViewModelBuilder,
        protected PdpPresentationResolver $pdpPresentationResolver,
        protected PdpPageComposer $pdpPageComposer
    ) {}

    public function home(Request $request): View
    {
        return view('commerce_v2.pages.home', [
            'products' => [],
            'pagination' => [
                'has_more' => true,
                'next_cursor' => null,
            ],
            'pageTitle' => 'LIN XÉN — Váy thiết kế cho nhịp sống hiện đại',
            'pageDescription' => 'Khám phá thiết kế LIN XÉN qua hình ảnh đã duyệt, màu sắc, kích thước, giá và tồn kho từ hệ thống chính thức.',
        ]);
    }

    public function homeProducts(Request $request): JsonResponse
    {
        try {
            $listing = $this->client->listing(
                8,
                $request->query('cursor'),
                120
            );
            $products = $this->presentProducts(
                (array) data_get(
                    $listing,
                    'data.items',
                    []
                )
            );
            $pagination = (array) data_get(
                $listing,
                'meta.pagination',
                []
            );

            return response()->json([
                'html' => view(
                    'commerce_v2.themes.luxe_commerce_v1.partials.home-product-batch',
                    compact('products')
                )->render(),
                'has_more' => (bool) data_get(
                    $pagination,
                    'has_more',
                    false
                ),
                'next_cursor' => (string) data_get(
                    $pagination,
                    'next_cursor',
                    ''
                ),
            ]);
        } catch (CommerceV2ClientException $e) {
            return response()->json([
                'message' => 'Không thể tải thêm sản phẩm lúc này.',
            ], max(400, min(599, $e->httpStatus)));
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

    /* AI_PATCH_LINXEN_PDP_PRESENTATION_ENGINE_V1 */
    public function product(
        Request $request,
        string $slug
    ): View|Response {
        return $this->renderProduct($request, $slug, null);
    }

    public function productPreview(
        Request $request,
        string $variant,
        string $slug
    ): View|Response {
        return $this->renderProduct($request, $slug, $variant);
    }

    protected function renderProduct(
        Request $request,
        string $slug,
        ?string $forcedVariant
    ): View|Response {
        try {
            $reference = $this->presenter->normalizeProductReference($slug);
            $result = $this->client->product($reference);
            $product = $this->presenter->productDetail((array) data_get($result, 'data', []));

            if (! $product['public_ready']) {
                throw new CommerceV2ClientException('Sản phẩm chưa sẵn sàng.', 404, 'storefront_product_not_ready');
            }

            $product['related_products'] = $this->relatedProducts(
                $product
            );

            $viewModel = $this->pdpViewModelBuilder->build($product);
            $presentation = $this->pdpPageComposer->compose(
                $this->pdpPresentationResolver->resolve($request, $viewModel, $forcedVariant),
                $viewModel
            );
            $payload = [
                'product' => $product,
                'productPayloadJson' => $this->productPayloadJson($product),
                'pdp' => $viewModel,
                'presentation' => $presentation,
                'pdpPresentation' => $presentation,
                'cacheStatus' => data_get($result, '_storefront_cache'),
                'pageTitle' => $product['name'].' — LIN XÉN',
                'pageDescription' => $this->presenter->safeSeoDescription(
                    $product['description'],
                    'Xem màu, kích thước, giá và tồn kho của '.$product['name'].'.'
                ),
                'ogImage' => $product['cover_url'],
            ];
            $response = response()->view(
                (string) data_get($presentation, 'view', 'commerce_v2.pages.product'),
                $payload
            );

            if ((bool) data_get($presentation, 'is_preview')) {
                $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
                $response->headers->set('Cache-Control', 'private, no-store');
            }

            return $response;
        } catch (CommerceV2ClientException $e) {
            return $this->errorView($e);
        } catch (Throwable $e) {
            report($e);

            return $this->errorView(new CommerceV2ClientException(
                'Trang sản phẩm đang được cập nhật.',
                500,
                'storefront_product_render_failed',
                [],
                $e
            ));
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
                'pageTitle' => 'Tìm “'.$query.'” — LIN XÉN',
                'pageDescription' => 'Kết quả tìm kiếm sản phẩm LIN XÉN cho từ khóa '.$query.'.',
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
                ).' — LIN XÉN',
                'pageDescription' => $this->presenter
                    ->safeSeoDescription(
                        $collection['seo_description']
                            ?: $collection['description'],
                        'Bộ sưu tập '
                            .$collection['name']
                            .' của LIN XÉN.'
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

    /**
     * Keep PDP discovery useful while the ERP has no explicit product-DNA
     * relation for a given product. Upstream curated matches always win;
     * otherwise we show a small, current selection from the sellable catalog.
     */
    protected function relatedProducts(array $product): array
    {
        $currentId = (string) data_get($product, 'id');
        $currentSlug = (string) data_get($product, 'slug');
        $curated = $this->presentProducts((array) data_get(
            $product,
            'related_products',
            []
        ));

        if ($curated !== []) {
            return collect($curated)
                ->reject(fn ($item) => (
                    (string) data_get($item, 'id') === $currentId
                    || (string) data_get($item, 'slug') === $currentSlug
                ))
                ->take(4)
                ->values()
                ->all();
        }

        try {
            $listing = $this->client->listing(12, null, 120);

            return collect($this->presentProducts((array) data_get(
                $listing,
                'data.items',
                []
            )))
                ->reject(fn ($item) => (
                    (string) data_get($item, 'id') === $currentId
                    || (string) data_get($item, 'slug') === $currentSlug
                ))
                ->take(4)
                ->values()
                ->all();
        } catch (CommerceV2ClientException) {
            return [];
        }
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
        return json_encode(
            $product,
            JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_AMP
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
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

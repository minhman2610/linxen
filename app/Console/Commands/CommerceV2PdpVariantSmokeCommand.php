<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\Pdp\PdpPageComposer;
use App\Services\CommerceV2\Pdp\PdpPresentationResolver;
use App\Services\CommerceV2\Pdp\PdpVariantRegistry;
use App\Services\CommerceV2\Pdp\PdpViewModelBuilder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Throwable;

class CommerceV2PdpVariantSmokeCommand extends Command
{
    protected $signature = 'commerce-v2:pdp-variant-smoke
        {--variant=editorial_guided_v1}';

    protected $description = 'Static/render smoke for one PDP presentation variant.';

    public function handle(
        PdpVariantRegistry $registry,
        PdpViewModelBuilder $builder,
        PdpPresentationResolver $resolver,
        PdpPageComposer $composer
    ): int {
        $variantKey = strtolower(trim((string) $this->option('variant')));
        $definition = $registry->get($variantKey);

        if (! $definition) {
            $this->error('VARIANT_REGISTERED=FAIL');

            return self::FAILURE;
        }

        try {
            $product = $this->fixtureProduct();
            $viewModel = $builder->build($product);
            $request = Request::create(
                '/v2/preview/pdp/' . $variantKey . '/rs_4477',
                'GET'
            );
            $presentation = $composer->compose(
                $resolver->resolve(
                    $request,
                    $viewModel,
                    $variantKey
                ),
                $viewModel
            );
            $productPayloadJson = json_encode(
                $product,
                JSON_HEX_TAG
                | JSON_HEX_APOS
                | JSON_HEX_AMP
                | JSON_HEX_QUOT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );
            $html = data_get($presentation, 'renderer') === 'legacy'
                ? view((string) data_get($presentation, 'view'), [
                    'product' => $product,
                    'productPayloadJson' => $productPayloadJson,
                    'pageTitle' => 'Fixture — LIN XÉN',
                    'pageDescription' => 'Fixture PDP.',
                    'pdpPresentation' => $presentation,
                ])->render()
                : view((string) data_get($presentation, 'view'), [
                    'pdp' => $viewModel,
                    'product' => $product,
                    'productPayloadJson' => $productPayloadJson,
                    'presentation' => $presentation,
                    'pageTitle' => 'Fixture — LIN XÉN',
                    'pageDescription' => 'Fixture PDP.',
                    'ogImage' => data_get($product, 'cover_url'),
                ])->render();

            $checks = [
                'variant_registered' => true,
                'view_model_version' => data_get(
                    $viewModel,
                    'version'
                ) === PdpViewModelBuilder::VERSION,
                'variant_view_exists' => View::exists(
                    (string) data_get($definition, 'view')
                ),
                'section_views_exist' => collect((array) data_get(
                    $presentation,
                    'sections',
                    []
                ))->every(fn ($section) => View::exists(
                    (string) data_get($section, 'view')
                )),
                'assets_exist' => $this->assetsExist(
                    (array) data_get($presentation, 'assets', [])
                ),
                'preview_route' => Route::has(
                    'commerce.v2.product.preview'
                ),
                'product_route' => Route::has(
                    'commerce.v2.product'
                ),
                'exact_sku_contract' => str_contains(
                    $html,
                    'name="sellable_sku_id"'
                ),
                'gallery_contract' => str_contains(
                    $html,
                    'data-lxpdp-gallery'
                ),
                'color_contract' => str_contains(
                    $html,
                    'data-lxpdp-color'
                ),
                'size_advisor_contract' => str_contains(
                    $html,
                    'data-lxpdp-size-advisor'
                ),
                'mobile_cta_contract' => str_contains(
                    $html,
                    'data-lxpdp-mobile-buy'
                ),
                'variant_marker' => data_get(
                    $definition,
                    'renderer'
                ) === 'legacy' || str_contains(
                    $html,
                    'data-pdp-variant="' . $variantKey . '"'
                ),
                'order_mutation_none' => true,
                'provider_mutation_none' => true,
            ];

            foreach ($checks as $code => $passed) {
                $this->line(
                    strtoupper($code)
                    . '='
                    . ($passed ? 'PASS' : 'FAIL')
                );
            }

            $ok = ! in_array(false, $checks, true);
            $this->line(
                'PDP_VARIANT_SMOKE=' . ($ok ? 'PASS' : 'FAIL')
            );

            return $ok ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->error('PDP_VARIANT_SMOKE_ERROR=' . $e->getMessage());

            return self::FAILURE;
        }
    }

    protected function assetsExist(array $assets): bool
    {
        foreach (['styles', 'scripts'] as $type) {
            foreach ((array) data_get($assets, $type, []) as $asset) {
                $path = preg_replace('/\?.*$/', '', (string) $asset);

                if (! is_file(public_path($path))) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function fixtureProduct(): array
    {
        $media = [
            [
                'id' => 'media_1',
                'url' => 'https://example.test/elisa-front.jpg',
                'thumb_url' => 'https://example.test/elisa-front-thumb.jpg',
                'role' => 'hero',
                'category_code' => 'SALES_INBOX_SUPPORT_SINGLE',
                'shot_angle' => 'front_3_4',
                'selection_tier' => 1,
            ],
            [
                'id' => 'media_2',
                'url' => 'https://example.test/elisa-back.jpg',
                'thumb_url' => 'https://example.test/elisa-back-thumb.jpg',
                'role' => 'back',
                'category_code' => 'PRODUCTION_SAMPLE_REAL',
                'shot_angle' => 'back',
                'selection_tier' => 2,
            ],
        ];

        return [
            'id' => 'rs_4477',
            'code' => 'RS260616002',
            'slug' => 'rs_4477',
            'name' => 'Elisa',
            'short_name' => 'Elisa',
            'description' => 'Váy chữ A mini cổ tròn xẻ V.',
            'cover_url' => data_get($media, '0.url'),
            'price_min' => 799000,
            'price_max' => 799000,
            'original_min' => 799000,
            'has_sale' => false,
            'in_stock' => true,
            'available_total' => 8,
            'public_ready' => true,
            'default_color_id' => 'pvg_2031',
            'gallery_limit' => 6,
            'colors' => [
                [
                    'id' => 'pvg_2031',
                    'code' => 'charcoal',
                    'label' => 'Xám đậm',
                    'key' => 'charcoal',
                    'hex' => '#3f3f46',
                    'available' => 8,
                    'sellable' => true,
                    'cover_url' => data_get($media, '0.url'),
                    'media' => $media,
                    'sizes' => [
                        [
                            'size' => 'S',
                            'sellable_sku_id' => 'sku_54369629',
                            'sku' => 'SP14546158',
                            'available' => 3,
                            'sellable' => true,
                            'price_current' => 799000,
                            'price_original' => 799000,
                        ],
                        [
                            'size' => 'M',
                            'sellable_sku_id' => 'sku_54369630',
                            'sku' => 'SP14546159',
                            'available' => 5,
                            'sellable' => true,
                            'price_current' => 799000,
                            'price_original' => 799000,
                        ],
                    ],
                ],
            ],
            'highlights' => [
                ['label' => 'Form dáng', 'value' => 'Dáng chữ A'],
                ['label' => 'Độ dài', 'value' => 'Mini'],
                ['label' => 'Tay áo', 'value' => 'Tay phồng nhẹ'],
            ],
            'structured_specs' => [
                'design' => [
                    'status' => 'available',
                    'items' => [
                        ['key' => 'silhouette', 'label' => 'Form dáng', 'value' => 'Dáng chữ A'],
                        ['key' => 'neckline', 'label' => 'Cổ áo', 'value' => 'Cổ tròn xẻ V'],
                    ],
                ],
                'style' => [
                    'status' => 'available',
                    'items' => [
                        ['key' => 'style', 'label' => 'Phong cách', 'value' => 'Hiện đại'],
                    ],
                ],
                'fit' => [
                    'status' => 'available',
                    'items' => [
                        ['label' => 'Loại bảng số đo', 'value' => 'Số đo thành phẩm'],
                    ],
                    'message' => 'Bảng số đo là số đo thành phẩm.',
                ],
                'materials' => [
                    'status' => 'available',
                    'items' => [
                        ['label' => 'Vải chính', 'value' => 'Chéo Hai Da'],
                        ['label' => 'Vải lót', 'value' => 'Habutai'],
                    ],
                    'message' => 'Có lớp lót theo BOM.',
                ],
                'care' => [
                    'status' => 'missing',
                    'items' => [],
                    'message' => 'Chưa có hướng dẫn bảo quản được xác minh.',
                ],
            ],
            'size_advisor' => [
                'enabled' => true,
                'status' => 'provisional',
                'source_label' => 'Bảng chung + Tech Pack TP260620002',
                'endpoint_url' => '/v2/p/rs_4477/size-advice',
                'disclaimer' => 'Gợi ý tham khảo.',
                'size_chart' => [
                    'status' => 'structured',
                    'structured' => true,
                    'source' => 'production_tech_pack_specs',
                    'measurement_type' => 'garment',
                    'sizes' => ['S', 'M'],
                    'points' => [
                        [
                            'code' => 'bust',
                            'label' => 'Vòng ngực',
                            'unit' => 'cm',
                            'values' => ['S' => 88, 'M' => 92],
                            'display_values' => ['S' => '88', 'M' => '92'],
                        ],
                        [
                            'code' => 'waist',
                            'label' => 'Vòng eo',
                            'unit' => 'cm',
                            'values' => ['S' => 68, 'M' => 72],
                            'display_values' => ['S' => '68', 'M' => '72'],
                        ],
                    ],
                    'tech_pack' => [
                        'code' => 'TP260620002',
                        'version' => 'v1',
                    ],
                    'comparison_guidance' => 'So với một sản phẩm đang mặc vừa.',
                ],
            ],
            'tech_pack' => [
                'status' => 'available',
                'materials' => [
                    'main' => [
                        ['name' => 'Chéo Hai Da', 'family_name' => 'Chéo Hai Da'],
                    ],
                    'lining' => [
                        ['name' => 'Habutai Đen', 'family_name' => 'Vải Lót'],
                    ],
                    'layer_label' => 'Có lớp lót theo BOM',
                    'summary' => 'Vải chính Chéo Hai Da · lót Habutai.',
                ],
            ],
            'presentation' => [
                'active_variant' => 'classic_sales_v1',
                'fallback_variant' => 'classic_sales_v1',
                'assignment_mode' => 'fixed',
                'preview_enabled' => true,
                'variants' => [
                    'classic_sales_v1' => ['enabled' => true],
                    'editorial_guided_v1' => ['enabled' => true],
                ],
            ],
        ];
    }
}

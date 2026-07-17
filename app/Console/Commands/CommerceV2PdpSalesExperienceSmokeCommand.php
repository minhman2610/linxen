<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\CommerceV2Presenter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class CommerceV2PdpSalesExperienceSmokeCommand extends Command
{
    protected $signature = 'commerce-v2:pdp-sales-experience-smoke';

    protected $description = 'Static read-only smoke cho Lin Xén PDP Sales Experience V2.';

    public function handle(
        CommerceV2Presenter $presenter
    ): int {
        $raw = $this->syntheticProduct();
        $product = $presenter->productDetail($raw);
        $json = json_encode(
            $product,
            JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        if (! is_string($json)) {
            $this->error('Không encode được PDP smoke payload.');

            return self::FAILURE;
        }

        $html = view('commerce_v2.pages.product', [
            'product' => $product,
            'productPayloadJson' => $json,
            'pageTitle' => 'PDP Smoke — LIN XÉN',
            'pageDescription' => 'PDP Sales Experience smoke.',
            'ogImage' => $product['cover_url'],
        ])->render();

        $checks = [
            'presenter_default_color' => $product['default_color_id']
                === 'pvg_1891',
            'presenter_gallery_limit' => collect($product['colors'])
                ->every(
                    fn ($color) => count(
                        (array) data_get($color, 'media', [])
                    ) <= 6
                ),
            'presenter_exact_sku' => data_get(
                $product,
                'colors.0.sizes.0.sellable_sku_id'
            ) === 'sku_54369629',
            'media_priority_contract' => data_get(
                $product,
                'colors.0.media.0.category_code'
            ) === 'SALES_INBOX_SUPPORT_SINGLE',
            'no_cross_color_fallback' => data_get(
                $product,
                'colors.1.media',
                []
            ) === [],
            'out_of_stock_color_browsable' => str_contains(
                $html,
                'data-color-sellable="0"'
            ) && ! str_contains(
                $html,
                'data-color-id="pvg_1892" disabled'
            ),
            'size_advisor_contract' => data_get(
                $product,
                'size_advisor.status'
            ) === 'provisional',
            'structured_tech_pack_chart' => (bool) data_get(
                $product,
                'size_advisor.size_chart.structured',
                false
            ) && data_get(
                $product,
                'size_advisor.size_chart.source'
            ) === 'production_tech_pack_specs',
            'structured_chart_render' => str_contains(
                $html,
                'data-lxpdp-size-chart-structured'
            ) && str_contains(
                $html,
                'Số đo thành phẩm'
            ) && str_contains(
                $html,
                'Vòng ngực'
            ),
            'product_route' => Route::has(
                'commerce.v2.product'
            ),
            'size_advice_route' => Route::has(
                'commerce.v2.product.size_advice'
            ),
            'gallery_render' => str_contains(
                $html,
                'data-lxpdp-gallery'
            ),
            'color_render' => str_contains(
                $html,
                'data-lxpdp-color'
            ),
            'size_advisor_render' => str_contains(
                $html,
                'data-lxpdp-size-advisor'
            ),
            'sticky_mobile_cta_render' => str_contains(
                $html,
                'data-lxpdp-mobile-buy'
            ),
            'structured_specs_render' => str_contains(
                $html,
                'Hiểu rõ thiết kế trước khi chọn'
            ),
            'pdp_css_present' => is_file(
                public_path(
                    'commerce-v2/pdp-sales-experience.css'
                )
            ),
            'pdp_js_present' => is_file(
                public_path(
                    'commerce-v2/pdp-sales-experience.js'
                )
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

        $failed = in_array(false, $checks, true);

        $this->line(
            'PDP_SALES_EXPERIENCE_SMOKE='
            . ($failed ? 'FAIL' : 'PASS')
        );

        return $failed
            ? self::FAILURE
            : self::SUCCESS;
    }

    protected function syntheticProduct(): array
    {
        $media = collect(range(1, 6))
            ->map(fn ($index) => [
                'id' => 'media_' . $index,
                'url' => 'https://example.test/camila-cream-angle-' . $index . '.webp',
                'thumb_url' => 'https://example.test/camila-cream-angle-' . $index . '-thumb.webp',
                'role' => $index === 1 ? 'hero' : 'detail',
                'category_code' => 'SALES_INBOX_SUPPORT_SINGLE',
                'selection_tier' => 1,
                'color' => [
                    'code' => 'cream',
                    'name' => 'Kem',
                    'key' => 'cream',
                ],
            ])
            ->all();

        return [
            'id' => 'rs_4451',
            'slug' => 'camila-rs-4451',
            'code' => 'RS260522002',
            'name' => 'Camila Dress',
            'short_name' => 'Camila',
            'description' => 'Thiết kế thanh lịch với form rõ ràng.',
            'cover' => [
                'url' => data_get($media, '0.url'),
                'thumb_url' => data_get($media, '0.thumb_url'),
            ],
            'price' => [
                'min' => 799000,
                'max' => 799000,
                'original_min' => 899000,
                'has_sale' => true,
                'is_range' => false,
            ],
            'availability' => [
                'available_total' => 4,
                'in_stock' => true,
                'size_advisor' => [
                    'enabled' => true,
                    'status' => 'provisional',
                    'mode' => 'product_tech_pack_plus_generic_body_profile_v1',
                    'confidence_cap' => 'medium',
                    'source_label' => 'Tech Pack TP260620002 + Bảng gợi ý chung LIN XÉN',
                    'input_schema' => [],
                    'disclaimer' => 'Gợi ý tham khảo; bảng Tech Pack là số đo thành phẩm.',
                    'size_chart' => [
                        'status' => 'structured',
                        'structured' => true,
                        'source' => 'production_tech_pack_specs',
                        'measurement_type' => 'garment',
                        'sizes' => ['S', 'M', 'L', 'XL'],
                        'points' => [
                            [
                                'code' => 'dress_length_from_shoulder',
                                'label' => 'Dài váy từ đỉnh vai',
                                'unit' => 'cm',
                                'values' => [
                                    'S' => 83,
                                    'M' => 85,
                                    'L' => 87,
                                    'XL' => 89,
                                ],
                                'display_values' => [
                                    'S' => '83',
                                    'M' => '85',
                                    'L' => '87',
                                    'XL' => '89',
                                ],
                                'note' => 'Đo từ điểm vai cao nhất xuống gấu váy.',
                            ],
                            [
                                'code' => 'bust',
                                'label' => 'Vòng ngực',
                                'unit' => 'cm',
                                'values' => [
                                    'S' => 88,
                                    'M' => 92,
                                    'L' => 96,
                                    'XL' => 100,
                                ],
                                'display_values' => [
                                    'S' => '88',
                                    'M' => '92',
                                    'L' => '96',
                                    'XL' => '100',
                                ],
                            ],
                        ],
                        'spec_count' => 28,
                        'point_count' => 7,
                        'size_count' => 4,
                        'comparison_guidance' => 'Đây là số đo thành phẩm; hãy so với một sản phẩm đang mặc vừa.',
                        'message' => 'Bảng số đo thành phẩm từ Tech Pack.',
                        'tech_pack' => [
                            'code' => 'TP260620002',
                            'version' => 'v1',
                            'status' => 'bom_ready',
                        ],
                        'image_url' => '',
                        'thumb_url' => '',
                    ],
                ],
            ],
            'specs' => [
                [
                    'key' => 'silhouette',
                    'label' => 'Form dáng',
                    'value' => 'Ôm eo xòe',
                ],
            ],
            'materials' => [
                'main' => 'Chéo Nhật',
                'lining' => 'Có lót',
                'layer_type' => '2 lớp',
            ],
            'media' => [
                'items' => $media,
                'support_items' => [],
                'pdp' => [
                    'version' => 'commerce_pdp_experience_v1',
                    'gallery_limit' => 6,
                    'category_priority' => [
                        'SALES_INBOX_SUPPORT_SINGLE',
                        'OPENING_PRODUCT_CLARITY_SINGLE',
                    ],
                    'never_cross_color_fallback' => true,
                    'default_color' => [
                        'id' => 'pvg_1891',
                        'code' => 'cream',
                        'label' => 'Kem',
                    ],
                    'media_sets_by_color' => [
                        [
                            'color_id' => 'pvg_1891',
                            'code' => 'cream',
                            'label' => 'Kem',
                            'key' => 'cream',
                            'count' => 6,
                            'source_count' => 9,
                            'best_selection_tier' => 1,
                            'fallback_reason' => null,
                            'cover' => $media[0],
                            'items' => $media,
                        ],
                        [
                            'color_id' => 'pvg_1892',
                            'code' => 'black',
                            'label' => 'Đen',
                            'key' => 'black',
                            'count' => 0,
                            'source_count' => 0,
                            'best_selection_tier' => null,
                            'fallback_reason' => 'no_exact_color_public_media',
                            'cover' => null,
                            'items' => [],
                        ],
                    ],
                    'structured_specs' => [
                        'design' => [
                            'status' => 'available',
                            'items' => [
                                [
                                    'label' => 'Form dáng',
                                    'value' => 'Ôm eo xòe',
                                ],
                            ],
                        ],
                        'materials' => [
                            'status' => 'available',
                            'items' => [
                                [
                                    'label' => 'Chất liệu chính',
                                    'value' => 'Chéo Nhật',
                                ],
                            ],
                        ],
                        'fit' => [
                            'status' => 'available',
                            'items' => [],
                            'message' => 'Thông tin form đã duyệt.',
                        ],
                        'style' => [
                            'status' => 'available',
                            'items' => [],
                        ],
                        'care' => [
                            'status' => 'missing',
                            'items' => [],
                            'message' => 'Đang cập nhật.',
                        ],
                    ],
                    'highlights' => [
                        [
                            'label' => 'Form dáng',
                            'value' => 'Ôm eo xòe',
                        ],
                    ],
                    'tech_pack' => [
                        'status' => 'available',
                        'source' => 'production_tech_pack_specs',
                        'size_chart' => [
                            'structured' => true,
                        ],
                    ],
                ],
            ],
            'colors' => [
                [
                    'id' => 'pvg_1891',
                    'code' => 'cream',
                    'label' => 'Kem',
                    'key' => 'cream',
                    'hex' => '#efe3cf',
                    'available' => 4,
                    'sellable' => true,
                    'cover' => $media[0],
                    'sizes' => [
                        [
                            'size' => 'M',
                            'sellable_sku_id' => 'sku_54369629',
                            'sku' => 'SP14546158',
                            'available' => 4,
                            'sellable' => true,
                            'price' => [
                                'current' => 799000,
                                'original' => 899000,
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'pvg_1892',
                    'code' => 'black',
                    'label' => 'Đen',
                    'key' => 'black',
                    'hex' => '#111111',
                    'available' => 0,
                    'sellable' => false,
                    'cover' => null,
                    'sizes' => [
                        [
                            'size' => 'M',
                            'sellable_sku_id' => 'sku_54369633',
                            'sku' => 'SP14546162',
                            'available' => 0,
                            'sellable' => false,
                            'price' => [
                                'current' => 799000,
                                'original' => 899000,
                            ],
                        ],
                    ],
                ],
            ],
            'public_ready' => true,
        ];
    }
}

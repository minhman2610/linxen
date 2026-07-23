<?php

namespace App\Services\CommerceV2\Pdp;

use Illuminate\Support\Str;

final class PdpViewModelBuilder
{
    public const VERSION = 'linxen_pdp_view_model_v1';

    /* AI_PATCH_LINXEN_PDP_PRODUCT_STUDY_BUILDER_V1 */
    public function __construct(
        protected PdpProductStudyBuilder $productStudyBuilder
    ) {
    }

    public function build(array $product): array
    {
        $colors = collect((array) data_get($product, 'colors', []))
            ->values();
        $defaultColorId = (string) data_get(
            $product,
            'default_color_id',
            ''
        );
        $defaultColor = (array) (
            $colors->firstWhere('id', $defaultColorId)
            ?: $colors->firstWhere('sellable', true)
            ?: $colors->first()
            ?: []
        );
        $structured = (array) data_get(
            $product,
            'structured_specs',
            []
        );
        $sizeAdvisor = (array) data_get(
            $product,
            'size_advisor',
            []
        );
        $techPack = (array) data_get(
            $product,
            'tech_pack',
            []
        );
        $techMaterials = (array) data_get(
            $techPack,
            'materials',
            []
        );
        $allSelectedMedia = $colors
            ->flatMap(fn ($color) => (array) data_get(
                $color,
                'media',
                []
            ))
            ->unique('url')
            ->values();
        $supportMedia = collect((array) data_get(
            $product,
            'support_media',
            []
        ));
        $productionTruth = $allSelectedMedia
            ->concat($supportMedia)
            ->filter(fn ($media) => $this->isProductionTruth(
                (array) $media
            ))
            ->unique('url')
            ->take(8)
            ->values()
            ->all();
        $occasionItems = collect((array) data_get(
            $structured,
            'style.items',
            []
        ))
            ->filter(fn ($item) => in_array(
                (string) data_get($item, 'key'),
                ['style', 'season', 'category'],
                true
            ))
            ->values()
            ->all();

        $productStudyByColor = $this->productStudyBuilder
            ->build($colors->all());

        return [
            'version' => self::VERSION,
            'identity' => [
                'id' => (string) data_get($product, 'id'),
                'code' => (string) data_get($product, 'code'),
                'slug' => (string) data_get($product, 'slug'),
                'name' => (string) data_get($product, 'name'),
                'short_name' => (string) data_get(
                    $product,
                    'short_name'
                ),
                'description' => Str::squish((string) data_get(
                    $product,
                    'description'
                )),
            ],
            'commerce' => [
                'price' => [
                    'currency' => 'VND',
                    'min' => (float) data_get(
                        $product,
                        'price_min',
                        0
                    ),
                    'max' => (float) data_get(
                        $product,
                        'price_max',
                        0
                    ),
                    'original_min' => (float) data_get(
                        $product,
                        'original_min',
                        0
                    ),
                    'has_sale' => (bool) data_get(
                        $product,
                        'has_sale',
                        false
                    ),
                ],
                'availability' => [
                    'in_stock' => (bool) data_get(
                        $product,
                        'in_stock',
                        false
                    ),
                    'available_total' => (float) data_get(
                        $product,
                        'available_total',
                        0
                    ),
                ],
                'colors' => $colors->all(),
                'default_color' => $defaultColor,
                'default_color_id' => (string) data_get(
                    $defaultColor,
                    'id'
                ),
                'gallery_limit' => (int) data_get(
                    $product,
                    'gallery_limit',
                    6
                ),
                'cart_action' => route(
                    'commerce.v2.cart.items.store'
                ),
            ],
            'media' => [
                'gallery_by_color' => $colors
                    ->map(fn ($color) => [
                        'color_id' => (string) data_get(
                            $color,
                            'id'
                        ),
                        'color_code' => (string) data_get(
                            $color,
                            'code'
                        ),
                        'color_label' => (string) data_get(
                            $color,
                            'label'
                        ),
                        'items' => array_values((array) data_get(
                            $color,
                            'media',
                            []
                        )),
                    ])
                    ->values()
                    ->all(),
                /* AI_PATCH_LINXEN_PDP_PRODUCT_STUDY_MEDIA_V2 */
                'product_study_media_by_color' => $colors
                    ->map(fn ($color) => [
                        'color_id' => (string) data_get(
                            $color,
                            'id'
                        ),
                        'color_code' => (string) data_get(
                            $color,
                            'code'
                        ),
                        'color_label' => (string) data_get(
                            $color,
                            'label'
                        ),
                        'items' => array_values((array) data_get(
                            $color,
                            'study_media',
                            []
                        )),
                    ])
                    ->values()
                    ->all(),                'product_study_by_color' => $productStudyByColor,
                'production_truth' => $productionTruth,
                'cover_url' => (string) data_get(
                    $product,
                    'cover_url'
                ),
                'strategy' => (array) data_get(
                    $product,
                    'media_strategy',
                    []
                ),
            ],
            'fit' => [
                'advisor' => $sizeAdvisor,
                'garment_size_chart' => (array) data_get(
                    $sizeAdvisor,
                    'size_chart',
                    []
                ),
                'tech_pack_source' => (array) data_get(
                    $sizeAdvisor,
                    'size_chart.tech_pack',
                    []
                ),
                'fit_items' => array_values((array) data_get(
                    $structured,
                    'fit.items',
                    []
                )),
                'fit_message' => (string) data_get(
                    $structured,
                    'fit.message'
                ),
                'model_measurements' => (array) data_get(
                    $product,
                    'model_measurements',
                    []
                ),
            ],
            'product_truth' => [
                'highlights' => array_values((array) data_get(
                    $product,
                    'highlights',
                    []
                )),
                'design' => (array) data_get(
                    $structured,
                    'design',
                    []
                ),
                'style' => (array) data_get(
                    $structured,
                    'style',
                    []
                ),
                'materials' => [
                    'section' => (array) data_get(
                        $structured,
                        'materials',
                        []
                    ),
                    'main' => array_values((array) data_get(
                        $techMaterials,
                        'main',
                        []
                    )),
                    'lining' => array_values((array) data_get(
                        $techMaterials,
                        'lining',
                        []
                    )),
                    'layer_label' => (string) data_get(
                        $techMaterials,
                        'layer_label'
                    ),
                    'summary' => (string) data_get(
                        $techMaterials,
                        'summary'
                    ),
                ],
                'care' => (array) data_get(
                    $structured,
                    'care',
                    []
                ),
                'raw_specs' => array_values((array) data_get(
                    $product,
                    'specs',
                    []
                )),
            ],
            'policies' => [
                'cod' => [
                    'enabled' => true,
                    'label' => 'Thanh toán COD',
                ],
                'shipping' => [
                    'label' => 'Giao hàng toàn quốc',
                    'message' => 'Phí giao hàng và tổng COD được xác nhận ở bước thanh toán.',
                ],
                'exchange' => [
                    'label' => 'Hỗ trợ đổi size',
                    'message' => 'Liên hệ LIN XÉN để được kiểm tra điều kiện đổi size theo chính sách hiện hành.',
                ],
            ],
            'discovery' => [
                'occasion_items' => $occasionItems,
                'styling_suggestions' => array_values((array) data_get(
                    $product,
                    'styling_suggestions',
                    []
                )),
                'related_products' => array_values((array) data_get(
                    $product,
                    'related_products',
                    []
                )),
                'recently_viewed_enabled' => true,
            ],
            'presentation' => (array) data_get(
                $product,
                'presentation',
                []
            ),
            'runtime' => [
                'product_payload' => $product,
                'product_payload_version' => 'commerce_v2_presenter_product_detail_v1',
            ],
        ];
    }

    protected function isProductionTruth(array $media): bool
    {
        $blob = Str::upper(implode(' ', [
            (string) data_get($media, 'category_code'),
            (string) data_get($media, 'support_role'),
        ]));

        return Str::contains($blob, [
            'PRODUCTION_SAMPLE',
            'PRODUCT_CLARITY',
        ]);
    }
}

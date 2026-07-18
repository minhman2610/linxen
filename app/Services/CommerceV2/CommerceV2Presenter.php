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
        $pdp = (array) data_get(
            $product,
            'media.pdp',
            []
        );
        $sets = collect((array) data_get(
            $pdp,
            'media_sets_by_color',
            []
        ))->keyBy(
            fn ($set) => (string) data_get(
                $set,
                'color_id'
            )
        );        /* AI_PATCH_LINXEN_PDP_CLARITY_MEDIA_PRESENTER_V1 */
        $claritySets = collect((array) data_get(
            $pdp,
            'clarity_sets_by_color',
            []
        ))->keyBy(
            fn ($set) => (string) data_get(
                $set,
                'color_id'
            )
        );
        $fallbackMedia = collect((array) data_get(
            $product,
            'media.items',
            []
        ))
            ->map(fn ($item) => $this->presentMedia($item))
            ->filter(fn ($item) => $item['url'] !== '')
            ->take(6)
            ->values()
            ->all();

        $colors = collect((array) data_get(
            $product,
            'colors',
            []
        ))
            ->map(function ($color) use (
                $sets,
                $claritySets,
                $fallbackMedia
            ) {
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
                $colorId = (string) data_get($color, 'id');
                $set = (array) $sets->get($colorId, []);                $claritySet = (array) $claritySets->get(
                    $colorId,
                    []
                );
                $clarityMedia = collect((array) data_get(
                    $claritySet,
                    'items',
                    []
                ))
                    ->map(fn ($item) => $this->presentMedia(
                        $item
                    ))
                    ->filter(fn ($item) => $item['url'] !== '')
                    ->take(8)
                    ->values()
                    ->all();
                $media = collect((array) data_get(
                    $set,
                    'items',
                    []
                ))
                    ->map(fn ($item) => $this->presentMedia(
                        $item
                    ))
                    ->filter(fn ($item) => $item['url'] !== '')
                    ->take(6)
                    ->values()
                    ->all();

                /*
                 * Never substitute media from a different color. The product
                 * fallback is only allowed when the ERP extension is absent,
                 * preserving backward compatibility during staged deploy.
                 */
                if ($sets->isEmpty()) {
                    $media = collect((array) data_get(
                        $color,
                        'media',
                        []
                    ))
                        ->map(fn ($item) => $this->presentMedia(
                            $item
                        ))
                        ->filter(fn ($item) => $item['url'] !== '')
                        ->take(6)
                        ->values()
                        ->all();

                    if ($media === []) {
                        $media = $fallbackMedia;
                    }
                }

                return [
                    'id' => $colorId,
                    'code' => (string) data_get(
                        $color,
                        'code'
                    ),
                    'label' => (string) data_get(
                        $color,
                        'label'
                    ),
                    'key' => (string) data_get(
                        $color,
                        'key'
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
                    'cover_url' => (string) (
                        data_get($set, 'cover.url')
                        ?: data_get($color, 'cover.url')
                    ),
                    'media' => $media,
                    'clarity_media' => $clarityMedia,
                    'clarity_media_count' => count(
                        $clarityMedia
                    ),
                    'clarity_media_source_count' => (int) data_get(
                        $claritySet,
                        'source_count',
                        count($clarityMedia)
                    ),
                    'clarity_media_exact_color' => (bool) data_get(
                        $claritySet,
                        'exact_color_only',
                        true
                    ),
                    'media_count' => count($media),
                    'media_source_count' => (int) data_get(
                        $set,
                        'source_count',
                        count($media)
                    ),
                    'media_tier' => data_get(
                        $set,
                        'best_selection_tier'
                    ),
                    'media_fallback_reason' => data_get(
                        $set,
                        'fallback_reason'
                    ),
                    'exact_color_media' => $sets->isNotEmpty(),
                    'sizes' => $sizes,
                ];
            })
            ->values()
            ->all();
        $defaultColor = (array) data_get(
            $pdp,
            'default_color',
            []
        );
        $defaultColorId = (string) data_get(
            $defaultColor,
            'id'
        );

        if ($defaultColorId === '' && $colors !== []) {
            $defaultColorId = (string) data_get(
                $colors,
                '0.id'
            );
        }

        $supportMedia = collect((array) data_get(
            $product,
            'media.support_items',
            []
        ))
            ->map(fn ($item) => [
                'id' => (string) data_get($item, 'id'),
                'url' => (string) data_get($item, 'url'),
                'thumb_url' => (string) (
                    data_get($item, 'thumb_url')
                    ?: data_get($item, 'url')
                ),
                'support_role' => (string) data_get(
                    $item,
                    'support_role'
                ),
                'category_code' => (string) data_get(
                    $item,
                    'category'
                ),
            ])
            ->filter(fn ($item) => $item['url'] !== '')
            ->values()
            ->all();
        $sizeAdvisor = (array) data_get(
            $product,
            'availability.size_advisor',
            []
        );
        $sizeChart = (array) data_get(
            $sizeAdvisor,
            'size_chart',
            []
        );

        return array_merge($summary, [
            'description' => (string) data_get(
                $product,
                'description'
            ),
            'specs' => array_values(
                (array) data_get($product, 'specs', [])
            ),
            'structured_specs' => (array) data_get(
                $pdp,
                'structured_specs',
                []
            ),
            'highlights' => array_values(
                (array) data_get(
                    $pdp,
                    'highlights',
                    []
                )
            ),
            'materials' => (array) data_get(
                $product,
                'materials',
                []
            ),
            'tech_pack' => (array) data_get(
                $pdp,
                'tech_pack',
                []
            ),            /* AI_PATCH_LINXEN_PDP_PRESENTATION_ENGINE_V1 */
            'presentation' => (array) data_get(
                $pdp,
                'presentation',
                []
            ),
            'media' => collect($colors)
                ->flatMap(fn ($color) => (array) data_get(
                    $color,
                    'media',
                    []
                ))
                ->unique('url')
                ->values()
                ->all(),
            'support_media' => $supportMedia,
            'colors' => $colors,
            'default_color_id' => $defaultColorId,
            'gallery_limit' => (int) data_get(
                $pdp,
                'gallery_limit',
                6
            ),
            'media_strategy' => [
                'version' => (string) data_get(
                    $pdp,
                    'version',
                    'legacy'
                ),
                'category_priority' => array_values(
                    (array) data_get(
                        $pdp,
                        'category_priority',
                        []
                    )
                ),
                'never_cross_color_fallback' => (bool) data_get(
                    $pdp,
                    'never_cross_color_fallback',
                    true
                ),
            ],
            'size_advisor' => [
                'enabled' => (bool) data_get(
                    $sizeAdvisor,
                    'enabled',
                    false
                ),
                'status' => (string) data_get(
                    $sizeAdvisor,
                    'status',
                    'unavailable'
                ),
                'mode' => (string) data_get(
                    $sizeAdvisor,
                    'mode',
                    'unavailable'
                ),
                'confidence_cap' => (string) data_get(
                    $sizeAdvisor,
                    'confidence_cap',
                    'none'
                ),
                'source_label' => (string) data_get(
                    $sizeAdvisor,
                    'source_label',
                    ''
                ),
                'input_schema' => array_values(
                    (array) data_get(
                        $sizeAdvisor,
                        'input_schema',
                        []
                    )
                ),
                'disclaimer' => (string) data_get(
                    $sizeAdvisor,
                    'disclaimer',
                    ''
                ),
                'endpoint_url' => route(
                    'commerce.v2.product.size_advice',
                    [
                        'slug' => (string) data_get(
                            $summary,
                            'slug'
                        ),
                    ]
                ),
                'size_chart' => [
                    'status' => (string) data_get(
                        $sizeChart,
                        'status',
                        'missing'
                    ),
                    'structured' => (bool) data_get(
                        $sizeChart,
                        'structured',
                        false
                    ),
                    'source' => (string) data_get(
                        $sizeChart,
                        'source',
                        ''
                    ),
                    'measurement_type' => (string) data_get(
                        $sizeChart,
                        'measurement_type',
                        ''
                    ),
                    'sizes' => array_values((array) data_get(
                        $sizeChart,
                        'sizes',
                        []
                    )),
                    'points' => array_values((array) data_get(
                        $sizeChart,
                        'points',
                        data_get($sizeChart, 'measurement_rows', [])
                    )),
                    'spec_count' => (int) data_get(
                        $sizeChart,
                        'spec_count',
                        0
                    ),
                    'point_count' => (int) data_get(
                        $sizeChart,
                        'point_count',
                        0
                    ),
                    'size_count' => (int) data_get(
                        $sizeChart,
                        'size_count',
                        0
                    ),
                    'comparison_guidance' => (string) data_get(
                        $sizeChart,
                        'comparison_guidance',
                        ''
                    ),
                    'tech_pack' => (array) data_get(
                        $sizeChart,
                        'tech_pack',
                        []
                    ),
                    'image_url' => (string) data_get(
                        $sizeChart,
                        'image_url',
                        ''
                    ),
                    'thumb_url' => (string) data_get(
                        $sizeChart,
                        'thumb_url',
                        ''
                    ),
                    'message' => (string) data_get(
                        $sizeChart,
                        'message',
                        ''
                    ),
                ],
            ],
            'public_ready' => (bool) data_get(
                $product,
                'public_ready',
                false
            ),
        ]);
    }

    protected function presentMedia(mixed $item): array
    {
        return [
            'id' => (string) data_get($item, 'id'),
            'url' => (string) data_get($item, 'url'),
            'thumb_url' => (string) (
                data_get($item, 'thumb_url')
                ?: data_get($item, 'url')
            ),
            'role' => (string) data_get(
                $item,
                'role',
                'lifestyle'
            ),
            'role_source' => (string) data_get(
                $item,
                'role_source'
            ),
            'category_code' => (string) data_get(
                $item,
                'category_code',
                data_get($item, 'category')
            ),
            'shot_angle' => (string) data_get(
                $item,
                'shot_angle',
                data_get($item, 'shot')
            ),
            'selection_tier' => (int) data_get(
                $item,
                'selection_tier',
                9
            ),
            'fallback_reason' => data_get(
                $item,
                'fallback_reason'
            ),
            'color_code' => (string) data_get(
                $item,
                'color.code'
            ),
            'color_name' => (string) data_get(
                $item,
                'color.name'
            ),
        ];
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
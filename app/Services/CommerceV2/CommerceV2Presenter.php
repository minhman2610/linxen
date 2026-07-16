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

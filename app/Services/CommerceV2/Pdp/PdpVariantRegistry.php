<?php

namespace App\Services\CommerceV2\Pdp;

final class PdpVariantRegistry
{
    public const VERSION = 'linxen_pdp_variant_registry_v1';

    public function all(): array
    {
        return [
            'classic_sales_v1' => [
                'key' => 'classic_sales_v1',
                'label' => 'Classic Sales V1',
                'version' => '1.0.0',
                'renderer' => 'legacy',
                'view' => 'commerce_v2.pages.product',
                'view_model_version' => PdpViewModelBuilder::VERSION,
                'sections' => [],
                'assets' => [
                    'styles' => [],
                    'scripts' => [],
                ],
                'enabled' => true,
            ],
            'editorial_guided_v1' => [
                'key' => 'editorial_guided_v1',
                'label' => 'Editorial Guided V1',
                'version' => '1.0.0',
                'renderer' => 'sectioned',
                'view' => 'commerce_v2.pdp.page',
                'layout' => 'editorial_guided_v1',
                'view_model_version' => PdpViewModelBuilder::VERSION,
                'sections' => [
                    'editorial_hero_purchase',
                    'design_highlights',
                    'fit_and_scale',
                    'size_confidence',
                    'product_truth',
                    'materials_and_care',
                    'occasion_and_styling',
                    'related_products',
                    'recently_viewed',
                    'final_reassurance',
                ],
                'assets' => [
                    'styles' => [
                        'commerce-v2/pdp-sales-experience.css?v=3',
                        'commerce-v2/pdp/v1/core.css?v=1',
                        'commerce-v2/pdp/v1/variants/editorial-guided-v1.css?v=1',
                    ],
                    'scripts' => [
                        'commerce-v2/pdp/v1/variants/editorial-guided-v1.js?v=1',
                    ],
                ],
                'enabled' => true,
            ],
        ];
    }

    public function get(string $key): ?array
    {
        $key = strtolower(trim($key));

        return $this->all()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
}

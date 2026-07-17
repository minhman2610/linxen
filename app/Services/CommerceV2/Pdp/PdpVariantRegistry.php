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
            /* AI_PATCH_LINXEN_PDP_ATELIER_EDITORIAL_V1 */
            'atelier_editorial_v1' => [
                'key' => 'atelier_editorial_v1',
                'label' => 'Atelier Editorial V1',
                'version' => '1.0.0',
                'renderer' => 'sectioned',
                'view' => 'commerce_v2.pdp.page',
                'layout' => 'atelier_editorial_v1',
                'view_model_version' => PdpViewModelBuilder::VERSION,
                'sections' => [
                    'atelier_hero_purchase',
                    'atelier_image_ribbon',
                    'atelier_manifesto',
                    'atelier_design_gestures',
                    'atelier_fit_story',
                    'atelier_truth_mosaic',
                    'atelier_size_story',
                    'atelier_material_story',
                    'atelier_finale',
                ],
                'assets' => [
                    'styles' => [
                        'commerce-v2/pdp-sales-experience.css?v=3',
                        'commerce-v2/pdp/v1/core.css?v=1',
                        'commerce-v2/pdp/v1/variants/atelier-editorial-v1.css?v=1',
                    ],
                    'scripts' => [
                        'commerce-v2/pdp/v1/variants/atelier-editorial-v1.js?v=1',
                    ],
                ],
                'art_direction' => [
                    'concept' => 'atelier_editorial',
                    'tone' => 'modern_fashion_house',
                    'empty_sections' => 'hide',
                ],
                'enabled' => true,
            ],
            /* AI_PATCH_LINXEN_PDP_STUDIO_SIGNAL_V1 */
            'studio_signal_v1' => [
                'key' => 'studio_signal_v1',
                'label' => 'Studio Signal V1',
                'version' => '1.0.0',
                'renderer' => 'sectioned',
                'view' => 'commerce_v2.pdp.page',
                'layout' => 'studio_signal_v1',
                'view_model_version' => PdpViewModelBuilder::VERSION,
                'sections' => [
                    'studio_hero_purchase',
                    'studio_quick_read',
                    'studio_design_explorer',
                    'studio_benefit_grid',
                    'studio_media_lab',
                    'studio_size_studio',
                    'studio_material_feel',
                    'studio_confidence_strip',
                    'studio_complete_look',
                    'studio_recently_viewed',
                    'studio_final_cta',
                ],
                'assets' => [
                    'styles' => [
                        'commerce-v2/pdp-sales-experience.css?v=3',
                        'commerce-v2/pdp/v1/core.css?v=1',
                        'commerce-v2/pdp/v1/variants/studio-signal-v1.css?v=1',
                    ],
                    'scripts' => [
                        'commerce-v2/pdp/v1/variants/studio-signal-v1.js?v=1',
                    ],
                ],
                'art_direction' => [
                    'concept' => 'digital_fashion_studio',
                    'palette' => 'porcelain_graphite_signal_cherry',
                    'content_density' => 'visual_first_readable',
                    'mobile_navigation' => 'single_row_contextual_commerce_dock',
                    'empty_sections' => 'hide',
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

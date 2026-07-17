<?php

namespace App\Services\CommerceV2\Pdp;

final class PdpSectionRegistry
{
    public const VERSION = 'linxen_pdp_section_registry_v1';

    public function all(): array
    {
        return [
            'editorial_hero_purchase' => [
                'view' => 'commerce_v2.pdp.sections.editorial-hero-purchase',
                'required' => ['identity.id', 'commerce.colors'],
                'empty_behavior' => 'render',
            ],
            'design_highlights' => [
                'view' => 'commerce_v2.pdp.sections.design-highlights',
                'required_any' => [
                    'product_truth.highlights',
                    'product_truth.design.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'fit_and_scale' => [
                'view' => 'commerce_v2.pdp.sections.fit-and-scale',
                'required_any' => [
                    'fit.fit_items',
                    'fit.garment_size_chart.points',
                    'fit.model_measurements',
                ],
                'empty_behavior' => 'hide',
            ],
            'size_confidence' => [
                'view' => 'commerce_v2.pdp.sections.size-confidence',
                'required_any' => [
                    'fit.advisor.enabled',
                    'fit.garment_size_chart.points',
                    'fit.garment_size_chart.image_url',
                ],
                'empty_behavior' => 'hide',
            ],
            'product_truth' => [
                'view' => 'commerce_v2.pdp.sections.product-truth',
                'required_any' => [
                    'media.production_truth',
                    'product_truth.raw_specs',
                ],
                'empty_behavior' => 'hide',
            ],
            'materials_and_care' => [
                'view' => 'commerce_v2.pdp.sections.materials-and-care',
                'required_any' => [
                    'product_truth.materials.main',
                    'product_truth.materials.lining',
                    'product_truth.materials.section.items',
                    'product_truth.care.items',
                ],
                'empty_behavior' => 'hide',
            ],
            'occasion_and_styling' => [
                'view' => 'commerce_v2.pdp.sections.occasion-and-styling',
                'required_any' => [
                    'discovery.occasion_items',
                    'discovery.styling_suggestions',
                ],
                'empty_behavior' => 'hide',
            ],
            'related_products' => [
                'view' => 'commerce_v2.pdp.sections.related-products',
                'required' => ['discovery.related_products'],
                'empty_behavior' => 'hide',
            ],
            'recently_viewed' => [
                'view' => 'commerce_v2.pdp.sections.recently-viewed',
                'required' => ['discovery.recently_viewed_enabled'],
                'empty_behavior' => 'render',
            ],
            'final_reassurance' => [
                'view' => 'commerce_v2.pdp.sections.final-reassurance',
                'required' => ['policies.cod.enabled'],
                'empty_behavior' => 'render',
            ],
        ];
    }

    public function compose(array $variant, array $viewModel): array
    {
        $definitions = $this->all();

        return collect((array) data_get($variant, 'sections', []))
            ->map(function (string $key) use (
                $definitions,
                $viewModel
            ) {
                $definition = $definitions[$key] ?? null;

                if (! is_array($definition)) {
                    return null;
                }

                $visible = $this->visible(
                    $definition,
                    $viewModel
                );

                if (
                    ! $visible
                    && data_get($definition, 'empty_behavior') === 'hide'
                ) {
                    return null;
                }

                return array_merge($definition, [
                    'key' => $key,
                    'visible' => $visible,
                ]);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function visible(
        array $definition,
        array $viewModel
    ): bool {
        foreach ((array) data_get($definition, 'required', []) as $path) {
            if (! $this->filled(data_get($viewModel, $path))) {
                return false;
            }
        }

        $requiredAny = (array) data_get(
            $definition,
            'required_any',
            []
        );

        if ($requiredAny === []) {
            return true;
        }

        return collect($requiredAny)
            ->contains(fn ($path) => $this->filled(
                data_get($viewModel, $path)
            ));
    }

    protected function filled(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null && trim((string) $value) !== '';
    }
}

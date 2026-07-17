<?php

namespace App\Services\CommerceV2\Pdp;

use Illuminate\Http\Request;
use Throwable;

final class PdpPresentationResolver
{
    public const VERSION = 'linxen_pdp_presentation_resolver_v1';

    public function __construct(
        protected PdpVariantRegistry $registry
    ) {
    }

    public function resolve(
        Request $request,
        array $viewModel,
        ?string $forcedVariant = null
    ): array {
        $runtime = array_replace_recursive(
            $this->fallbackRuntime(),
            (array) data_get($viewModel, 'presentation', [])
        );
        $source = 'runtime_active_variant';
        $requested = trim((string) $forcedVariant);

        if ($requested !== '') {
            $source = 'signed_preview';
        } elseif (
            (string) data_get($runtime, 'assignment_mode')
            === 'experiment'
        ) {
            $requested = $this->experimentVariant(
                $request,
                $runtime
            );
            $source = 'experiment_assignment';
        } else {
            $requested = (string) data_get(
                $runtime,
                'active_variant',
                'classic_sales_v1'
            );
        }

        $fallback = (string) data_get(
            $runtime,
            'fallback_variant',
            'classic_sales_v1'
        );
        $resolved = $this->allowedDefinition(
            $requested,
            $runtime,
            $forcedVariant !== null
        );

        if (! $resolved) {
            $resolved = $this->allowedDefinition(
                $fallback,
                $runtime,
                false
            );
            $source = 'runtime_fallback_variant';
        }

        if (! $resolved) {
            $resolved = $this->registry->get(
                'classic_sales_v1'
            );
            $source = 'hard_fallback_classic';
        }

        return array_merge((array) $resolved, [
            'resolver_version' => self::VERSION,
            'resolved_source' => $source,
            'is_preview' => $forcedVariant !== null,
            'runtime' => $runtime,
        ]);
    }

    protected function allowedDefinition(
        string $key,
        array $runtime,
        bool $preview
    ): ?array {
        $definition = $this->registry->get($key);

        if (! $definition || ! data_get($definition, 'enabled')) {
            return null;
        }

        if (
            $preview
            && ! (bool) data_get(
                $runtime,
                'preview_enabled',
                false
            )
        ) {
            return null;
        }

        $runtimeVariant = (array) data_get(
            $runtime,
            'variants.' . $key,
            []
        );

        if (
            $runtimeVariant !== []
            && ! (bool) data_get(
                $runtimeVariant,
                'enabled',
                false
            )
        ) {
            return null;
        }

        return $definition;
    }

    protected function experimentVariant(
        Request $request,
        array $runtime
    ): string {
        $weights = collect((array) data_get(
            $runtime,
            'experiment.weights',
            []
        ))
            ->map(fn ($weight) => max(0, (int) $weight))
            ->filter(fn ($weight) => $weight > 0);

        if ($weights->isEmpty()) {
            return (string) data_get(
                $runtime,
                'active_variant',
                'classic_sales_v1'
            );
        }

        try {
            $seed = $request->hasSession()
                ? $request->session()->getId()
                : ($request->ip() . '|' . $request->userAgent());
        } catch (Throwable) {
            $seed = $request->ip() . '|' . $request->userAgent();
        }

        $total = (int) $weights->sum();
        $bucket = hexdec(substr(hash('sha256', $seed), 0, 8))
            % max(1, $total);
        $cursor = 0;

        foreach ($weights as $key => $weight) {
            $cursor += (int) $weight;

            if ($bucket < $cursor) {
                return (string) $key;
            }
        }

        return (string) $weights->keys()->first();
    }

    protected function fallbackRuntime(): array
    {
        return [
            'version' => 'linxen_pdp_presentation_v1',
            'active_variant' => 'classic_sales_v1',
            'fallback_variant' => 'classic_sales_v1',
            'assignment_mode' => 'fixed',
            'preview_enabled' => true,
            'variants' => [
                'classic_sales_v1' => ['enabled' => true],
                'editorial_guided_v1' => ['enabled' => true],
            ],
            'experiment' => [
                'weights' => [],
            ],
            'source' => [
                'type' => 'storefront_code_fallback',
            ],
        ];
    }
}

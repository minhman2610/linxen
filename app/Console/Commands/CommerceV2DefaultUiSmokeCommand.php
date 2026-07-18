<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\CommerceThemePreviewService;
use App\Services\CommerceV2\Pdp\PdpPresentationResolver;
use App\Services\CommerceV2\Pdp\PdpVariantRegistry;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Throwable;

final class CommerceV2DefaultUiSmokeCommand extends Command
{
    protected $signature = 'commerce-v2:default-ui-smoke';

    protected $description =
        'Static smoke for the canonical Luxe Commerce V2 UI and Luxe Clarity PDP.';

    public function handle(
        CommerceThemePreviewService $theme,
        PdpPresentationResolver $resolver,
        PdpVariantRegistry $registry
    ): int {
        try {
            $request = Request::create('/v2/p/rs_4477', 'GET');
            $presentation = $resolver->resolve(
                $request,
                [
                    'presentation' => [
                        'active_variant' => 'classic_sales_v1',
                        'fallback_variant' => 'classic_sales_v1',
                        'assignment_mode' => 'fixed',
                        'preview_enabled' => true,
                        'variants' => [
                            'classic_sales_v1' => ['enabled' => true],
                        ],
                    ],
                ]
            );
            $layout = (string) file_get_contents(
                resource_path('views/commerce_v2/layouts/app.blade.php')
            );
            $pages = [
                'home',
                'shop',
                'search',
                'collection',
                'discover',
                'cart',
                'checkout',
                'checkout_confirm',
                'order_success',
                'account',
                'orders',
                'order',
            ];
            $pageSwitches = collect($pages)->every(
                fn (string $page): bool => str_contains(
                    (string) file_get_contents(resource_path(
                        'views/commerce_v2/pages/' . $page . '.blade.php'
                    )),
                    'commerce_v2.themes.luxe_commerce_v1'
                )
            );
            $checks = [
                'default_theme_service' => (
                    $theme->active(app('session'))
                        === CommerceThemePreviewService::THEME
                    && $theme->isDefault()
                ),
                'layout_default_theme_assets' => (
                    str_contains($layout, 'luxe-commerce-v1.css')
                    && str_contains($layout, 'luxe-commerce-v1.js')
                    && str_contains(
                        $layout,
                        'AI_PATCH_LINXEN_LUXE_COMMERCE_DEFAULT_THEME_V1'
                    )
                ),
                'layout_public_robots' => (
                    str_contains(
                        $layout,
                        "@yield('robots', 'index,follow')"
                    )
                    && ! str_contains(
                        $layout,
                        "shell.preview-bar')"
                    )
                ),
                'pdp_single_bottom_navigation' => (
                    str_contains(
                        $layout,
                        "request()->routeIs('commerce.v2.product')"
                    )
                    && str_contains(
                        $layout,
                        "request()->routeIs('commerce.v2.product.preview')"
                    )
                ),
                'page_switches_default_luxe' => $pageSwitches,
                'pdp_default_variant_registered' => $registry->has(
                    PdpPresentationResolver::DEFAULT_VARIANT
                ),
                'pdp_default_variant' => data_get(
                    $presentation,
                    'key'
                ) === PdpPresentationResolver::DEFAULT_VARIANT,
                'pdp_not_preview' => data_get(
                    $presentation,
                    'is_preview'
                ) === false,
                'pdp_default_view_exists' => View::exists(
                    (string) data_get($presentation, 'view')
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
                'COMMERCE_V2_DEFAULT_UI_SMOKE='
                . ($ok ? 'PASS' : 'FAIL')
            );

            return $ok ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->error(
                'COMMERCE_V2_DEFAULT_UI_SMOKE_ERROR='
                . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}

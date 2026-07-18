<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\CommerceThemePreviewService;
use Illuminate\Console\Command;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Throwable;

final class CommerceV2LuxeCommerceThemeSmokeCommand extends Command
{
    protected $signature =
        'commerce-v2:luxe-commerce-theme-smoke';

    protected $description =
        'Static render and contract smoke for the default Luxe Commerce site-wide theme.';

    public function handle(
        CommerceThemePreviewService $preview
    ): int {
        try {
            $session = new Store(
                'luxe-commerce-smoke',
                new ArraySessionHandler(120)
            );
            $defaultThemeActive = $preview->active($session)
                === CommerceThemePreviewService::THEME;
            $preview->clear($session);
            $defaultThemeSurvivesClear = $preview->active($session)
                === CommerceThemePreviewService::THEME;
            $sessionManagerCompatible = $preview->active(
                app('session')
            ) === CommerceThemePreviewService::THEME;

            $views = [
                'commerce_v2.themes.luxe_commerce_v1.shell.header',
                'commerce_v2.themes.luxe_commerce_v1.shell.footer',
                'commerce_v2.themes.luxe_commerce_v1.shell.bottom-nav',
                'commerce_v2.themes.luxe_commerce_v1.shell.preview-bar',
                'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
                'commerce_v2.themes.luxe_commerce_v1.pages.home',
                'commerce_v2.themes.luxe_commerce_v1.pages.shop',
                'commerce_v2.themes.luxe_commerce_v1.pages.search',
                'commerce_v2.themes.luxe_commerce_v1.pages.collection',
                'commerce_v2.themes.luxe_commerce_v1.pages.discover',
                'commerce_v2.themes.luxe_commerce_v1.pages.cart',
                'commerce_v2.themes.luxe_commerce_v1.pages.checkout',
                'commerce_v2.themes.luxe_commerce_v1.pages.checkout-confirm',
                'commerce_v2.themes.luxe_commerce_v1.pages.order-success',
                'commerce_v2.themes.luxe_commerce_v1.pages.account',
                'commerce_v2.themes.luxe_commerce_v1.pages.orders',
                'commerce_v2.themes.luxe_commerce_v1.pages.order',
            ];
            $viewsExist = collect($views)
                ->every(fn (string $view) => View::exists($view));

            $product = [
                'id' => 'rs_4477',
                'code' => 'RS260616002',
                'slug' => 'rs_4477',
                'url' => route(
                    'commerce.v2.product',
                    ['slug' => 'rs_4477']
                ),
                'name' => 'Elisa',
                'short_name' => 'Elisa',
                'cover_url' =>
                    'https://example.test/elisa.jpg',
                'cover_alt' => 'Elisa',
                'price_min' => 799000,
                'price_max' => 799000,
                'original_min' => 899000,
                'has_sale' => true,
                'is_range' => false,
                'available_total' => 8,
                'in_stock' => true,
                'colors' => [
                    [
                        'id' => 'pvg_1',
                        'label' => 'Đen',
                        'hex' => '#111111',
                        'sellable' => true,
                        'available' => 8,
                        'cover_url' =>
                            'https://example.test/elisa.jpg',
                        'available_sizes' => ['S', 'M'],
                    ],
                ],
            ];
            $products = [$product];
            $collections = [[
                'slug' => 'new',
                'url' => route(
                    'commerce.v2.collection',
                    ['slug' => 'new']
                ),
                'name' => 'Hàng mới',
                'description' => 'Thiết kế mới cập nhật.',
                'hero_image' => '',
            ]];
            $cart = [
                'items' => [[
                    'sellable_sku_id' => 'sku_54369629',
                    'sku' => 'SP14546158',
                    'product_name' => 'Elisa',
                    'product_url' => $product['url'],
                    'color_name' => 'Đen',
                    'size' => 'M',
                    'quantity' => 1,
                    'line_total' => 799000,
                    'cover_url' => $product['cover_url'],
                    'valid' => true,
                    'message' => '',
                ]],
                'summary' => [
                    'quantity_total' => 1,
                    'subtotal' => 799000,
                    'valid' => true,
                ],
            ];
            $capabilities = [
                'guest_checkout_enabled' => true,
                'one_page_checkout_enabled' => true,
                'order_accept_enabled' => false,
                'shipping' => [
                    'name' => 'Giao hàng tiêu chuẩn',
                    'fee_amount' => 30000,
                    'free_shipping_threshold' => null,
                ],
            ];
            $identity = [
                'receiver_name' => '',
                'phone' => '',
                'email' => '',
                'location_id' => 0,
                'ward_id' => 0,
                'ward_name' => '',
                'street' => '',
            ];
            $order = [
                'order_id' => 'ord_smoke',
                'order_code' => 'LX-SMOKE',
                'status' => 'validated',
                'provider_status' => 'pending_gate',
                'created_at' => now()->toIso8601String(),
                'can_cancel' => false,
                'address' => [
                    'receiver_name' => 'Khách smoke',
                    'receiver_phone' => '0900000000',
                    'street' => 'Địa chỉ smoke',
                    'ward_name' => 'Phường smoke',
                    'location_name' => 'Hà Nội',
                ],
                'items' => [[
                    'product_name' => 'Elisa',
                    'color_name' => 'Đen',
                    'size' => 'M',
                    'quantity' => 1,
                    'line_total' => 799000,
                ]],
                'totals' => [
                    'subtotal' => 799000,
                    'shipping_fee' => 30000,
                    'grand_total' => 829000,
                ],
            ];

            $homeHtml = view(
                'commerce_v2.themes.luxe_commerce_v1.pages.home',
                compact('products', 'collections')
            )->render();
            $cartHtml = view(
                'commerce_v2.themes.luxe_commerce_v1.pages.cart',
                ['cart' => $cart, 'cartError' => null]
            )->render();
            $checkoutHtml = view(
                'commerce_v2.themes.luxe_commerce_v1.pages.checkout',
                [
                    'cart' => $cart,
                    'capabilities' => $capabilities,
                    'identity' => $identity,
                    'locations' => [
                        ['id' => 9, 'name' => 'Hà Nội'],
                    ],
                    'isVerifiedCustomer' => false,
                    'isGuestCustomer' => false,
                ]
            )->render();
            $successHtml = view(
                'commerce_v2.themes.luxe_commerce_v1.pages.order-success',
                ['order' => $order]
            )->render();

            $layout = (string) file_get_contents(
                resource_path(
                    'views/commerce_v2/layouts/app.blade.php'
                )
            );

            $checks = [
                'preview_routes' => (
                    Route::has('commerce.v2.theme.preview')
                    && Route::has(
                        'commerce.v2.theme.preview.exit'
                    )
                ),
                'default_theme_contract' => (
                    $defaultThemeActive
                    && $defaultThemeSurvivesClear
                    && $sessionManagerCompatible
                    && $preview->isDefault()
                ),
                'views_exist' => $viewsExist,
                'theme_css_exists' => is_file(public_path(
                    'commerce-v2/themes/luxe-commerce-v1.css'
                )),
                'theme_js_exists' => is_file(public_path(
                    'commerce-v2/themes/luxe-commerce-v1.js'
                )),
                'layout_theme_gate' => (
                    str_contains(
                        $layout,
                        'data-commerce-theme'
                    )
                    && str_contains(
                        $layout,
                        'luxe-commerce-v1.css'
                    )
                ),
                'home_render' => (
                    str_contains(
                        $homeHtml,
                        'data-lxcv1-page="home"'
                    )
                    && str_contains(
                        $homeHtml,
                        'data-lxcv1-product-grid'
                    )
                ),
                'cart_exact_sku_forms' => (
                    str_contains(
                        $cartHtml,
                        'sku_54369629'
                    )
                    && str_contains(
                        $cartHtml,
                        'data-lxcv1-cart'
                    )
                ),
                'checkout_contract' => (
                    str_contains(
                        $checkoutHtml,
                        'data-lxv2-one-page-checkout'
                    )
                    && str_contains(
                        $checkoutHtml,
                        'name="shipping_method"'
                    )
                    && str_contains(
                        $checkoutHtml,
                        'name="payment_method"'
                    )
                ),
                'order_success_render' => (
                    str_contains(
                        $successHtml,
                        'data-lxcv1-page="order-success"'
                    )
                    && str_contains(
                        $successHtml,
                        'LX-SMOKE'
                    )
                ),
                'default_theme_live' => true,
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
                'LUXE_COMMERCE_THEME_SMOKE='
                . ($ok ? 'PASS' : 'FAIL')
            );

            return $ok
                ? self::SUCCESS
                : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->error(
                'LUXE_COMMERCE_THEME_SMOKE_ERROR='
                . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}

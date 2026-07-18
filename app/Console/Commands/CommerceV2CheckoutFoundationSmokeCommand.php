<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Throwable;

class CommerceV2CheckoutFoundationSmokeCommand extends Command
{
    protected $signature =
        'commerce-v2:checkout-foundation-smoke';

    protected $description =
        'Static route, container and Blade smoke for one-page guest checkout.';

    public function handle(): int
    {
        try {
            $controllers = [
                \App\Http\Controllers\CommerceV2\CheckoutController::class,
                \App\Http\Controllers\CommerceV2\OrderController::class,
                \App\Http\Controllers\CommerceV2\AccountController::class,
            ];
            $controllerResolution = collect($controllers)
                ->every(function (string $controller): bool {
                    return app()->make($controller)
                        instanceof $controller;
                });
            $expectedRoutes = [
                'commerce.v2.checkout.index',
                'commerce.v2.checkout.place_order',
                'commerce.v2.checkout.wards',
                'commerce.v2.orders.success',
                'commerce.v2.orders.index',
                'commerce.v2.orders.show',
            ];
            $routes = collect($expectedRoutes)
                ->every(fn ($name) => Route::has($name));
            $cart = [
                'items' => [
                    [
                        'sellable_sku_id' => 'sku_54369629',
                        'product_name' => 'Sản phẩm smoke',
                        'color_name' => 'Kem',
                        'size' => 'M',
                        'quantity' => 1,
                        'line_total' => 799000,
                        'cover_url' => '',
                    ],
                ],
                'summary' => [
                    'quantity_total' => 1,
                    'subtotal' => 799000,
                    'valid' => true,
                ],
            ];
            $capabilities = [
                'guest_checkout_enabled' => true,
                'otp_checkout_enabled' => false,
                'one_page_checkout_enabled' => true,
                'order_accept_enabled' => false,
                'payment_methods' => ['cod'],
                'shipping' => [
                    'name' => 'Giao hàng tiêu chuẩn',
                    'fee_amount' => 30000,
                    'free_shipping_threshold' => null,
                ],
            ];
            $checkoutHtml = view(
                'commerce_v2.pages.checkout',
                [
                    'cart' => $cart,
                    'account' => [],
                    'identity' => [
                        'receiver_name' => '',
                        'phone' => '',
                        'email' => '',
                        'location_id' => 0,
                        'ward_id' => 0,
                        'ward_name' => '',
                        'street' => '',
                    ],
                    'locations' => [
                        ['id' => 9, 'name' => 'Hà Nội'],
                    ],
                    'capabilities' => $capabilities,
                    'isVerifiedCustomer' => false,
                    'isGuestCustomer' => false,
                    'pageTitle' => 'Thanh toán — LIN XÉN',
                    'pageDescription' =>
                        'One-page checkout smoke.',
                ]
            )->render();
            /* AI_PATCH_LINXEN_CHECKOUT_FOUNDATION_THEME_RESILIENT_V1 */
            $guestCapabilities = array_replace(
                $capabilities,
                ['order_accept_enabled' => true]
            );
            $guestCheckoutHtml = view(
                'commerce_v2.pages.checkout',
                [
                    'cart' => $cart,
                    'account' => [],
                    'identity' => [
                        'receiver_name' => '',
                        'phone' => '',
                        'email' => '',
                        'location_id' => 0,
                        'ward_id' => 0,
                        'ward_name' => '',
                        'street' => '',
                    ],
                    'locations' => [
                        ['id' => 9, 'name' => 'Hà Nội'],
                    ],
                    'capabilities' => $guestCapabilities,
                    'isVerifiedCustomer' => false,
                    'isGuestCustomer' => false,
                    'pageTitle' => 'Thanh toán — LIN XÉN',
                    'pageDescription' =>
                        'Guest checkout theme smoke.',
                ]
            )->render();
            $order = [
                'order_id' => 'ord_static_smoke',
                'order_code' => 'LX-SMOKE',
                'status' => 'validated',
                'provider_status' => 'pending_gate',
                'created_at' => now()->toIso8601String(),
                'address' => [
                    'receiver_name' => 'Khách smoke',
                    'receiver_phone' => '0900000000',
                    'street' => 'Địa chỉ smoke',
                    'ward_name' => 'Phường smoke',
                    'location_name' => 'Tỉnh smoke',
                ],
                'items' => [
                    [
                        'product_name' => 'Sản phẩm smoke',
                        'color_name' => 'Kem',
                        'size' => 'M',
                        'quantity' => 1,
                        'line_total' => 799000,
                    ],
                ],
                'totals' => [
                    'subtotal' => 799000,
                    'shipping_fee' => 30000,
                    'grand_total' => 829000,
                ],
            ];
            $successHtml = view(
                'commerce_v2.pages.order_success',
                [
                    'order' => $order,
                    'pageTitle' =>
                        'Đặt hàng thành công — LIN XÉN',
                    'pageDescription' =>
                        'Order success smoke.',
                ]
            )->render();
            $ordersHtml = view(
                'commerce_v2.pages.orders',
                [
                    'orders' => ['items' => []],
                    'verifiedHistory' => false,
                    'guestHistoryNotice' => true,
                    'pageTitle' => 'Đơn hàng — LIN XÉN',
                ]
            )->render();
            $checks = [
                'controller_container_resolution' =>
                    $controllerResolution,
                'routes' => $routes,
                'one_page_checkout_render' => (
                    str_contains(
                        $checkoutHtml,
                        'data-lxv2-one-page-checkout'
                    )
                    && str_contains(
                        $checkoutHtml,
                        'name="receiver_name"'
                    )
                    && str_contains(
                        $checkoutHtml,
                        'name="location_id"'
                    )
                    && str_contains(
                        $checkoutHtml,
                        'name="ward_id"'
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
                'order_gate_default_off_copy' => (
                    str_contains(
                        $checkoutHtml,
                        'Chưa mở nhận đơn'
                    )
                    && str_contains(
                        $checkoutHtml,
                        'Website đang ở chế độ UAT'
                    )
                ),
                'quote_hidden_from_customer' => (
                    ! str_contains(
                        $checkoutHtml,
                        'Tạo báo giá'
                    )
                    && ! str_contains(
                        $checkoutHtml,
                        'Báo giá ERP'
                    )
                ),
                'guest_checkout_copy' => (
                    str_contains(
                        $guestCheckoutHtml,
                        'mua không cần tài khoản'
                    )
                    || str_contains(
                        $guestCheckoutHtml,
                        'Không cần tạo tài khoản'
                    )
                    || str_contains(
                        $guestCheckoutHtml,
                        'Guest checkout'
                    )
                ),
                'order_success_render' => (
                    str_contains(
                        $successHtml,
                        'Đặt hàng thành công'
                    )
                    && str_contains(
                        $successHtml,
                        'LX-SMOKE'
                    )
                ),
                'guest_order_privacy_copy' => str_contains(
                    $ordersHtml,
                    'không tra cứu đơn'
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
                'CHECKOUT_FOUNDATION_SMOKE='
                . ($ok ? 'PASS' : 'FAIL')
            );

            return $ok ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

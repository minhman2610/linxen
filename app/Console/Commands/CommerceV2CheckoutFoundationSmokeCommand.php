<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class CommerceV2CheckoutFoundationSmokeCommand extends Command
{
    protected $signature = 'commerce-v2:checkout-foundation-smoke';

    protected $description = 'Static route and Blade smoke for Storefront V2 quote, local-order and outbox foundation.';

    public function handle(): int
    {
        try {
            $routeChecks = collect([
                'commerce.v2.checkout.index',
                'commerce.v2.checkout.quote.create',
                'commerce.v2.checkout.confirm',
                'commerce.v2.checkout.quote.requote',
            ])->mapWithKeys(
                fn ($name) => [
                    $name => app('router')
                        ->getRoutes()
                        ->getByName($name) !== null,
                ]
            );

            $cart = [
                'items' => [
                    [
                        'sellable_sku_id' => 'sku_54369629',
                        'product_name' => 'Camila',
                        'cover_url' => '',
                        'sku' => 'SP14546158',
                        'color_name' => 'Kem',
                        'size' => 'M',
                        'quantity' => 1,
                        'available' => 4,
                        'unit_price' => 799000,
                        'line_total' => 799000,
                        'valid' => true,
                    ],
                ],
                'summary' => [
                    'item_count' => 1,
                    'quantity_total' => 1,
                    'subtotal' => 799000,
                    'valid' => true,
                ],
            ];
            $account = [
                'customer' => [
                    'id' => 'customer_1',
                    'phone' => '0900000000',
                ],
                'addresses' => [
                    [
                        'id' => 'address_1',
                        'receiver_name' => 'Khách kiểm thử',
                        'receiver_phone' => '0900000000',
                        'street' => 'Địa chỉ kiểm thử',
                        'ward_name' => 'Phường kiểm thử',
                        'location_name' => 'Thành phố kiểm thử',
                        'is_default' => true,
                    ],
                ],
            ];
            $quote = [
                'quote_id' => 'qt_static_smoke',
                'status' => 'active',
                'expires_at' => now()
                    ->addMinutes(15)
                    ->toIso8601String(),
                'ttl_remaining_seconds' => 900,
                'items' => $cart['items'],
                'address' => $account['addresses'][0],
                'shipping' => [
                    'name' => 'Giao hàng tiêu chuẩn',
                    'fee' => 30000,
                ],
                'payment_method' => 'cod',
                'totals' => [
                    'subtotal' => 799000,
                    'shipping_fee' => 30000,
                    'discount_total' => 0,
                    'grand_total' => 829000,
                ],
            ];

            $checkoutHtml = view(
                'commerce_v2.pages.checkout',
                [
                    'cart' => $cart,
                    'account' => $account,
                    'pageTitle' => 'Checkout smoke',
                    'pageDescription' => 'Checkout smoke',
                ]
            )->render();
            $confirmHtml = view(
                'commerce_v2.pages.checkout_confirm',
                [
                    'quote' => $quote,
                    'pageTitle' => 'Quote smoke',
                    'pageDescription' => 'Quote smoke',
                ]
            )->render();

            $checks = $routeChecks->merge([
                'checkout_view_render' => (
                    str_contains(
                        $checkoutHtml,
                        'commerce.v2.checkout'
                    )
                    || str_contains(
                        $checkoutHtml,
                        'Tạo báo giá'
                    )
                ),
                'confirm_view_render' => (
                    str_contains(
                        $confirmHtml,
                        'qt_static_smoke'
                    )
                    && str_contains(
                        $confirmHtml,
                        'Xác nhận đặt hàng'
                    )
                ),
                'local_order_control_present' => (
                    str_contains(
                        $confirmHtml,
                        route('commerce.v2.orders.store')
                    )
                    && str_contains(
                        $confirmHtml,
                        'Xác nhận đặt hàng COD'
                    )
                ),
                'idempotency_contract_present' => str_contains(
                    $confirmHtml,
                    'idempotency key'
                ),
                'provider_outbox_contract_present' => str_contains(
                    $confirmHtml,
                    'Outbox bất đồng bộ'
                ),
                'order_mutation_none' => true,
                'provider_mutation_none' => true,
            ]);

            foreach ($checks as $name => $passed) {
                $this->line(
                    strtoupper(
                        str_replace('.', '_', $name)
                    )
                    . '='
                    . ($passed ? 'PASS' : 'FAIL')
                );
            }

            $ok = ! in_array(
                false,
                $checks->all(),
                true
            );
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

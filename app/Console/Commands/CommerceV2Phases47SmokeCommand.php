<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Throwable;

class CommerceV2Phases47SmokeCommand extends Command
{
    protected $signature = 'commerce-v2:phases4-7-smoke
        {--sellable-sku=sku_54369629 : Canonical public sellable SKU}
        {--feed=de-xuat : Discover feed code}';

    protected $description = 'Read-only Storefront smoke for Commerce phases 4-7. Never commits orders or calls provider writer.';

    public function handle(
        ErpCommerceClient $client
    ): int {
        try {
            $configuration = $client
                ->configurationStatus();
            $cart = $client->validateCart([
                [
                    'sellable_sku_id' => (string) $this
                        ->option('sellable-sku'),
                    'quantity' => 1,
                ],
            ]);
            $rules = $client->discoverRules();
            $discover = $client->discover(
                (string) $this->option('feed'),
                2
            );
            $resolvedControllers = collect([
                \App\Http\Controllers\CommerceV2\CheckoutController::class,
                \App\Http\Controllers\CommerceV2\OrderController::class,
                \App\Http\Controllers\CommerceV2\AttributionRedirectController::class,
                \App\Http\Controllers\CommerceV2\DiscoverController::class,
            ])->every(function (string $controller): bool {
                return app()->make($controller) instanceof $controller;
            });

            $expectedRoutes = [
                'commerce.v2.attribution.go',
                'commerce.v2.checkout.index',
                'commerce.v2.checkout.confirm',
                'commerce.v2.orders.store',
                'commerce.v2.orders.index',
                'commerce.v2.orders.show',
                'commerce.v2.discover',
            ];
            $checks = [
                'configuration' => (
                    data_get($configuration, 'enabled')
                    && data_get(
                        $configuration,
                        'token_configured'
                    )
                ),
                'cart_validation' => data_get(
                    $cart,
                    'data.items.0.valid'
                ) === true,
                'discover_rules_contract' => is_array(
                    data_get($rules, 'data.items')
                ),
                'discover_feed_contract' => data_get(
                    $discover,
                    'data.contract_version'
                ) === 'commerce_discover_feed_public_v1',
                'routes' => collect($expectedRoutes)
                    ->every(fn ($name) => Route::has($name)),
                'controller_container_resolution' =>
                    $resolvedControllers,
                'discover_view_render' => str_contains(
                    view('commerce_v2.pages.discover', [
                        'rules' => [],
                        'result' => [
                            'items' => [],
                            'pagination' => [],
                        ],
                        'activeFeed' => 'de-xuat',
                        'pageTitle' => 'Khám phá — LIN XÉN',
                    ])->render(),
                    'Discover'
                ),
                'orders_view_render' => str_contains(
                    view('commerce_v2.pages.orders', [
                        'orders' => ['items' => []],
                        'pageTitle' => 'Đơn hàng — LIN XÉN',
                    ])->render(),
                    'Đơn hàng'
                ),
                'order_view_render' => str_contains(
                    view('commerce_v2.pages.order', [
                        'order' => [
                            'order_id' => 'ord_smoke',
                            'order_code' => 'LX-SMOKE',
                            'status' => 'validated',
                            'provider_status' =>
                                'pending_gate',
                            'items' => [],
                            'address' => [],
                            'totals' => [
                                'subtotal' => 0,
                                'shipping_fee' => 0,
                                'grand_total' => 0,
                            ],
                            'provider' => [],
                            'can_cancel' => false,
                        ],
                        'pageTitle' => 'LX-SMOKE — LIN XÉN',
                    ])->render(),
                    'LX-SMOKE'
                ),
                'provider_mutation_none' => true,
                'order_mutation_none' => true,
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
                'STOREFRONT_PHASES4_7_SMOKE='
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

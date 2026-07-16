<?php

namespace App\Services\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use Illuminate\Contracts\Session\Session;

class SessionCartService
{
    public const SESSION_KEY = 'commerce_v2.cart.items';
    public const MAX_ITEMS = 20;
    public const MAX_QUANTITY = 20;

    public function __construct(
        protected ErpCommerceClient $client
    ) {
    }

    public function raw(Session $session): array
    {
        return collect((array) $session->get(
            self::SESSION_KEY,
            []
        ))
            ->map(fn ($row) => [
                'sellable_sku_id' => trim((string) data_get(
                    $row,
                    'sellable_sku_id'
                )),
                'quantity' => max(
                    1,
                    min(
                        self::MAX_QUANTITY,
                        (int) data_get($row, 'quantity', 1)
                    )
                ),
            ])
            ->filter(fn ($row) => preg_match(
                '/^sku_\d+$/',
                $row['sellable_sku_id']
            ) === 1)
            ->keyBy('sellable_sku_id')
            ->values()
            ->all();
    }

    public function add(
        Session $session,
        string $sellableSkuId,
        int $quantity
    ): void {
        $sellableSkuId = trim($sellableSkuId);
        $quantity = max(1, min(self::MAX_QUANTITY, $quantity));

        if (
            preg_match('/^sku_\d+$/', $sellableSkuId)
            !== 1
        ) {
            throw new CommerceV2ClientException(
                'Mã SKU trong giỏ không hợp lệ.',
                422,
                'storefront_cart_sku_invalid'
            );
        }

        $items = collect($this->raw($session))
            ->keyBy('sellable_sku_id');

        if (
            ! $items->has($sellableSkuId)
            && $items->count() >= self::MAX_ITEMS
        ) {
            throw new CommerceV2ClientException(
                'Giỏ hàng đã đạt giới hạn sản phẩm.',
                422,
                'storefront_cart_item_limit'
            );
        }

        $nextQuantity = min(
            self::MAX_QUANTITY,
            (int) data_get(
                $items->get($sellableSkuId),
                'quantity',
                0
            ) + $quantity
        );

        $validation = $this->client->validateCart([
            [
                'sellable_sku_id' => $sellableSkuId,
                'quantity' => $nextQuantity,
            ],
        ]);

        if (
            data_get($validation, 'data.items.0.valid')
            !== true
        ) {
            throw new CommerceV2ClientException(
                (string) data_get(
                    $validation,
                    'data.items.0.message',
                    'Sản phẩm hiện không thể thêm vào giỏ.'
                ),
                409,
                'storefront_cart_item_unavailable'
            );
        }

        $items->put($sellableSkuId, [
            'sellable_sku_id' => $sellableSkuId,
            'quantity' => $nextQuantity,
        ]);

        $this->persist($session, $items->values()->all());
    }

    public function update(
        Session $session,
        string $sellableSkuId,
        int $quantity
    ): void {
        if ($quantity <= 0) {
            $this->remove($session, $sellableSkuId);
            return;
        }

        $items = collect($this->raw($session))
            ->keyBy('sellable_sku_id');

        if (! $items->has($sellableSkuId)) {
            throw new CommerceV2ClientException(
                'Sản phẩm không còn trong giỏ.',
                404,
                'storefront_cart_item_not_found'
            );
        }

        $quantity = min(self::MAX_QUANTITY, $quantity);
        $validation = $this->client->validateCart([
            [
                'sellable_sku_id' => $sellableSkuId,
                'quantity' => $quantity,
            ],
        ]);

        if (
            data_get($validation, 'data.items.0.valid')
            !== true
        ) {
            throw new CommerceV2ClientException(
                (string) data_get(
                    $validation,
                    'data.items.0.message',
                    'Số lượng hiện không còn khả dụng.'
                ),
                409,
                'storefront_cart_quantity_unavailable'
            );
        }

        $items->put($sellableSkuId, [
            'sellable_sku_id' => $sellableSkuId,
            'quantity' => $quantity,
        ]);

        $this->persist($session, $items->values()->all());
    }

    public function remove(
        Session $session,
        string $sellableSkuId
    ): void {
        $this->persist(
            $session,
            collect($this->raw($session))
                ->reject(fn ($row) => data_get(
                    $row,
                    'sellable_sku_id'
                ) === $sellableSkuId)
                ->values()
                ->all()
        );
    }

    public function clear(Session $session): void
    {
        $session->forget(self::SESSION_KEY);
        $session->save();
    }

    public function validated(Session $session): array
    {
        $items = $this->raw($session);

        if ($items === []) {
            return [
                'contract_version' => 'commerce_cart_validation_public_v1',
                'items' => [],
                'summary' => [
                    'item_count' => 0,
                    'quantity_total' => 0,
                    'subtotal' => 0,
                    'valid' => true,
                ],
            ];
        }

        return (array) data_get(
            $this->client->validateCart($items),
            'data',
            []
        );
    }

    protected function persist(
        Session $session,
        array $items
    ): void {
        $session->put(
            self::SESSION_KEY,
            array_values($items)
        );
        $session->save();
    }
}

<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;

class OrderAccessSessionService
{
    public const ORDER_IDS_KEY =
        'commerce_v2.orders.session_access';

    public function grant(
        Session $session,
        string $orderId
    ): void {
        $orderId = trim($orderId);

        if (
            preg_match(
                '/^ord_[A-Za-z0-9]+$/',
                $orderId
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                'Order id không hợp lệ.'
            );
        }

        $ids = collect((array) $session->get(
            self::ORDER_IDS_KEY,
            []
        ))
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->prepend($orderId)
            ->unique()
            ->take(10)
            ->values()
            ->all();

        $session->put(self::ORDER_IDS_KEY, $ids);
        $session->save();
    }

    public function allows(
        Session $session,
        string $orderId
    ): bool {
        return in_array(
            trim($orderId),
            $this->ids($session),
            true
        );
    }

    public function ids(Session $session): array
    {
        return collect((array) $session->get(
            self::ORDER_IDS_KEY,
            []
        ))
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => preg_match(
                '/^ord_[A-Za-z0-9]+$/',
                $id
            ) === 1)
            ->unique()
            ->values()
            ->all();
    }
}

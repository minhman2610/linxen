<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

class OrderIdempotencySessionService
{
    public const PREFIX =
        'commerce_v2.order.idempotency.';

    public function key(
        Session $session,
        string $quoteId
    ): string {
        $quoteId = trim($quoteId);
        $sessionKey = self::PREFIX . hash(
            'sha256',
            $quoteId
        );
        $existing = trim((string) $session->get(
            $sessionKey,
            ''
        ));

        if ($existing !== '') {
            return $existing;
        }

        $key = 'ordreq_' . Str::lower(
            Str::random(40)
        );
        $session->put($sessionKey, $key);
        $session->save();

        return $key;
    }

    public function forget(
        Session $session,
        string $quoteId
    ): void {
        $session->forget(
            self::PREFIX . hash(
                'sha256',
                trim($quoteId)
            )
        );
        $session->save();
    }
}

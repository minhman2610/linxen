<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;

class OnePageCheckoutSessionService
{
    public const PIPELINE_KEY =
        'commerce_v2.checkout.one_page_pipeline';

    public function state(Session $session): array
    {
        return (array) $session->get(
            self::PIPELINE_KEY,
            []
        );
    }

    public function matches(
        Session $session,
        string $fingerprint
    ): bool {
        return hash_equals(
            (string) data_get(
                $this->state($session),
                'fingerprint',
                ''
            ),
            trim($fingerprint)
        );
    }

    public function start(
        Session $session,
        string $fingerprint
    ): array {
        if ($this->matches($session, $fingerprint)) {
            return $this->state($session);
        }

        $state = [
            'fingerprint' => trim($fingerprint),
            'quote_id' => null,
            'order_id' => null,
            'started_at' => now()->toIso8601String(),
        ];

        $session->put(self::PIPELINE_KEY, $state);
        $session->save();

        return $state;
    }

    public function putQuote(
        Session $session,
        string $quoteId
    ): void {
        $state = $this->state($session);
        $state['quote_id'] = trim($quoteId);
        $session->put(self::PIPELINE_KEY, $state);
        $session->save();
    }

    public function putOrder(
        Session $session,
        string $orderId
    ): void {
        $state = $this->state($session);
        $state['order_id'] = trim($orderId);
        $session->put(self::PIPELINE_KEY, $state);
        $session->save();
    }

    public function clear(Session $session): void
    {
        $session->forget(self::PIPELINE_KEY);
        $session->save();
    }
}

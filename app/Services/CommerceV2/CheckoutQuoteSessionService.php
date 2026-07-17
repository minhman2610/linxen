<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;

class CheckoutQuoteSessionService
{
    public const QUOTE_KEY = 'commerce_v2.checkout.quote_id';

    public function id(Session $session): string
    {
        return trim((string) $session->get(
            self::QUOTE_KEY,
            ''
        ));
    }

    public function put(
        Session $session,
        string $quoteId
    ): void {
        $session->put(
            self::QUOTE_KEY,
            trim($quoteId)
        );
        $session->save();
    }

    public function forget(Session $session): void
    {
        $session->forget(self::QUOTE_KEY);
        $session->save();
    }
}

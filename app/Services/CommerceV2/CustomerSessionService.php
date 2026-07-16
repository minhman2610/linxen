<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;

class CustomerSessionService
{
    public const TOKEN_KEY = 'commerce_v2.customer.token';
    public const ACCOUNT_KEY = 'commerce_v2.customer.account';

    public function __construct(
        protected ErpCommerceClient $client
    ) {
    }

    public function exchange(
        Session $session,
        string $ticket
    ): array {
        $result = $this->client->exchangeCustomerTicket(
            $ticket
        );
        $token = trim((string) data_get(
            $result,
            'data.customer_session_token'
        ));

        if ($token === '') {
            throw new \RuntimeException(
                'ERP không trả customer session token.'
            );
        }

        $account = (array) data_get(
            $result,
            'data.account',
            []
        );

        $session->put(self::TOKEN_KEY, $token);
        $session->put(self::ACCOUNT_KEY, $account);
        $session->migrate(true);
        $session->save();

        return $account;
    }

    public function token(Session $session): string
    {
        return trim((string) $session->get(
            self::TOKEN_KEY,
            ''
        ));
    }

    public function account(Session $session): array
    {
        $token = $this->token($session);

        if ($token === '') {
            return [];
        }

        $account = (array) data_get(
            $this->client->customerAccount($token),
            'data',
            []
        );
        $session->put(self::ACCOUNT_KEY, $account);

        return $account;
    }

    public function logout(Session $session): void
    {
        $token = $this->token($session);

        if ($token !== '') {
            try {
                $this->client->logoutCustomer($token);
            } catch (\Throwable) {
            }
        }

        $session->forget([
            self::TOKEN_KEY,
            self::ACCOUNT_KEY,
        ]);
        $session->migrate(true);
        $session->save();
    }
}

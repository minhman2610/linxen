<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;

class CustomerSessionService
{
    public const TOKEN_KEY = 'commerce_v2.customer.token';
    public const ACCOUNT_KEY = 'commerce_v2.customer.account';
    public const ASSURANCE_KEY =
        'commerce_v2.customer.assurance';
    public const CHECKOUT_IDENTITY_KEY =
        'commerce_v2.customer.checkout_identity';

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
        $assurance = (string) data_get(
            $result,
            'data.session.assurance',
            'verified_magic_link'
        );

        $this->store(
            $session,
            $token,
            $assurance,
            $account,
            []
        );

        return $account;
    }

    public function beginGuestCheckout(
        Session $session,
        array $identity
    ): array {
        $result = $this->client->beginGuestCheckout(
            $identity
        );
        $token = trim((string) data_get(
            $result,
            'data.customer_session_token'
        ));

        if ($token === '') {
            throw new \RuntimeException(
                'ERP không trả guest customer session token.'
            );
        }

        $checkoutIdentity = [
            'customer' => (array) data_get(
                $result,
                'data.customer',
                []
            ),
            'shipping_address' => (array) data_get(
                $result,
                'data.shipping_address',
                []
            ),
            'privacy' => (array) data_get(
                $result,
                'data.privacy',
                []
            ),
        ];
        $assurance = (string) data_get(
            $result,
            'data.session.assurance',
            'guest_checkout'
        );

        $this->store(
            $session,
            $token,
            $assurance,
            [],
            $checkoutIdentity
        );

        return $checkoutIdentity;
    }

    public function token(Session $session): string
    {
        return trim((string) $session->get(
            self::TOKEN_KEY,
            ''
        ));
    }

    public function assurance(Session $session): string
    {
        return trim((string) $session->get(
            self::ASSURANCE_KEY,
            ''
        ));
    }

    public function authenticated(Session $session): bool
    {
        return $this->token($session) !== '';
    }

    public function verified(Session $session): bool
    {
        return in_array(
            $this->assurance($session),
            [
                'verified_magic_link',
                'verified_otp',
                'member_login',
            ],
            true
        );
    }

    public function guest(Session $session): bool
    {
        return (
            $this->authenticated($session)
            && ! $this->verified($session)
        );
    }

    public function account(Session $session): array
    {
        if (! $this->verified($session)) {
            return [];
        }

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

    public function checkoutIdentity(Session $session): array
    {
        return (array) $session->get(
            self::CHECKOUT_IDENTITY_KEY,
            []
        );
    }

    public function replaceCheckoutIdentity(
        Session $session,
        array $identity
    ): void {
        $session->put(
            self::CHECKOUT_IDENTITY_KEY,
            $identity
        );
        $session->save();
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
            self::ASSURANCE_KEY,
            self::CHECKOUT_IDENTITY_KEY,
            CheckoutQuoteSessionService::QUOTE_KEY,
            OnePageCheckoutSessionService::PIPELINE_KEY,
            OrderAccessSessionService::ORDER_IDS_KEY,
        ]);
        $session->migrate(true);
        $session->save();
    }

    protected function store(
        Session $session,
        string $token,
        string $assurance,
        array $account,
        array $checkoutIdentity
    ): void {
        $session->put(self::TOKEN_KEY, $token);
        $session->put(
            self::ASSURANCE_KEY,
            trim($assurance)
        );
        $session->put(self::ACCOUNT_KEY, $account);
        $session->put(
            self::CHECKOUT_IDENTITY_KEY,
            $checkoutIdentity
        );
        $session->migrate(true);
        $session->save();
    }
}

<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;

class AttributionSessionService
{
    public const FIRST_KEY =
        'commerce_v2.attribution.first_touch_token';

    public const LAST_KEY =
        'commerce_v2.attribution.last_touch_token';

    public function capture(
        Session $session,
        string $token
    ): array {
        $token = trim($token);

        if ($token === '') {
            return $this->payload($session);
        }

        $firstCreated = false;

        if (trim((string) $session->get(
            self::FIRST_KEY,
            ''
        )) === '') {
            $session->put(self::FIRST_KEY, $token);
            $firstCreated = true;
        }

        $session->put(self::LAST_KEY, $token);
        $session->save();

        return [
            'first_created' => $firstCreated,
            'payload' => $this->payload($session),
        ];
    }

    public function payload(Session $session): array
    {
        return [
            'first_touch_token' => trim((string) $session->get(
                self::FIRST_KEY,
                ''
            )) ?: null,
            'last_touch_token' => trim((string) $session->get(
                self::LAST_KEY,
                ''
            )) ?: null,
        ];
    }

    public function forget(Session $session): void
    {
        $session->forget([
            self::FIRST_KEY,
            self::LAST_KEY,
        ]);
        $session->save();
    }
}

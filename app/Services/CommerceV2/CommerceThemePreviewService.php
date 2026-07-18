<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;
use Illuminate\Session\SessionManager;

final class CommerceThemePreviewService
{
    public const VERSION = 'linxen_luxe_commerce_theme_preview_v1_1';
    public const THEME = 'luxe_commerce_v1';
    public const SESSION_KEY = 'commerce_v2.preview_theme';

    public function active(
        Session|SessionManager $session
    ): ?string {
        $store = $this->store($session);
        $state = (array) $store->get(self::SESSION_KEY, []);
        $theme = trim((string) data_get($state, 'theme'));
        $expiresAt = (int) data_get($state, 'expires_at', 0);

        if (
            $theme !== self::THEME
            || $expiresAt <= time()
        ) {
            if ($state !== []) {
                $this->clear($store);
            }

            return null;
        }

        return $theme;
    }

    public function activate(
        Session|SessionManager $session,
        string $theme,
        int $expiresAt
    ): void {
        $store = $this->store($session);

        if ($theme !== self::THEME) {
            throw new \InvalidArgumentException(
                'Commerce preview theme không hợp lệ.'
            );
        }

        $store->put(self::SESSION_KEY, [
            'theme' => self::THEME,
            'expires_at' => max(time() + 300, $expiresAt),
            'activated_at' => now()->toIso8601String(),
            'version' => self::VERSION,
        ]);
        $store->save();
    }

    public function clear(
        Session|SessionManager $session
    ): void {
        $store = $this->store($session);
        $store->forget(self::SESSION_KEY);
        $store->save();
    }

    protected function store(
        Session|SessionManager $session
    ): Session {
        return $session instanceof SessionManager
            ? $session->driver()
            : $session;
    }
}

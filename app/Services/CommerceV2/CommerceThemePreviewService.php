<?php

namespace App\Services\CommerceV2;

use Illuminate\Contracts\Session\Session;
use Illuminate\Session\SessionManager;

/**
 * Commerce V2 default visual theme resolver.
 *
 * The preview-shaped API is retained for backward compatibility with the
 * already-deployed signed routes and commands, but Luxe Commerce is now the
 * canonical default for every /v2 request and no session activation is needed.
 */
final class CommerceThemePreviewService
{
    public const VERSION = 'linxen_luxe_commerce_default_theme_v1';
    public const THEME = 'luxe_commerce_v1';
    public const SESSION_KEY = 'commerce_v2.preview_theme';

    public function active(
        Session|SessionManager $session
    ): string {
        return self::THEME;
    }

    public function activate(
        Session|SessionManager $session,
        string $theme,
        int $expiresAt
    ): void {
        if ($theme !== self::THEME) {
            throw new \InvalidArgumentException(
                'Commerce theme không hợp lệ.'
            );
        }

        // Compatibility no-op: the theme is already globally active.
    }

    public function clear(
        Session|SessionManager $session
    ): void {
        // Compatibility no-op: the canonical /v2 theme cannot be cleared.
    }

    public function isDefault(): bool
    {
        return true;
    }
}

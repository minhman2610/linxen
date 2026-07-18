<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\CommerceThemePreviewService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

final class CommerceV2LuxeCommerceThemePreviewUrlCommand extends Command
{
    protected $signature = 'commerce-v2:luxe-commerce-preview-url
        {target=home : home|shop|search|discover|cart|checkout|account|orders}
        {--minutes=240}';

    protected $description =
        'Create a temporary signed activation URL for Luxe Commerce site-wide preview.';

    public function handle(): int
    {
        $target = strtolower(trim(
            (string) $this->argument('target')
        ));
        $allowed = [
            'home',
            'shop',
            'search',
            'discover',
            'cart',
            'checkout',
            'account',
            'orders',
        ];

        if (! in_array($target, $allowed, true)) {
            $this->error(
                'LUXE_COMMERCE_PREVIEW_TARGET_INVALID='
                . $target
            );

            return self::FAILURE;
        }

        $minutes = max(
            5,
            min(
                1440,
                (int) $this->option('minutes')
            )
        );
        $url = URL::temporarySignedRoute(
            'commerce.v2.theme.preview',
            now()->addMinutes($minutes),
            ['target' => $target]
        );

        $this->line(
            'LUXE_COMMERCE_PREVIEW_URL=' . $url
        );
        $this->line(
            'LUXE_COMMERCE_PREVIEW_THEME='
            . CommerceThemePreviewService::THEME
        );
        $this->line(
            'LUXE_COMMERCE_PREVIEW_TARGET=' . $target
        );
        $this->line(
            'LUXE_COMMERCE_PREVIEW_EXPIRES_MINUTES='
            . $minutes
        );
        $this->line(
            'LUXE_COMMERCE_PREVIEW_EXIT_URL='
            . route('commerce.v2.theme.preview.exit')
        );
        $this->line(
            'LUXE_COMMERCE_PREVIEW_URL_COMMAND=PASS'
        );

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\Pdp\PdpVariantRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class CommerceV2PdpPreviewUrlCommand extends Command
{
    protected $signature = 'commerce-v2:pdp-preview-url
        {variant}
        {product=rs_4477}
        {--minutes=120}';

    protected $description = 'Create a temporary signed PDP variant preview URL.';

    public function handle(PdpVariantRegistry $registry): int
    {
        $variant = strtolower(trim((string) $this->argument('variant')));
        $product = trim((string) $this->argument('product'));
        $minutes = max(5, min(1440, (int) $this->option('minutes')));

        if (! $registry->has($variant)) {
            $this->error('PDP_VARIANT_NOT_REGISTERED=' . $variant);

            return self::FAILURE;
        }

        $url = URL::temporarySignedRoute(
            'commerce.v2.product.preview',
            now()->addMinutes($minutes),
            [
                'variant' => $variant,
                'slug' => $product,
            ]
        );

        $this->line('PDP_PREVIEW_URL=' . $url);
        $this->line('PDP_PREVIEW_VARIANT=' . $variant);
        $this->line('PDP_PREVIEW_EXPIRES_MINUTES=' . $minutes);
        $this->line('PDP_PREVIEW_URL_COMMAND=PASS');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\CommerceV2\Pdp\PdpVariantRegistry;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\BufferedOutput;

class CommerceV2PdpVariantMatrixSmokeCommand extends Command
{
    protected $signature = 'commerce-v2:pdp-variant-matrix-smoke';

    protected $description = 'Run static smoke for every registered PDP variant.';

    public function handle(PdpVariantRegistry $registry): int
    {
        $ok = true;

        foreach (array_keys($registry->all()) as $variant) {
            $exit = $this->call(
                'commerce-v2:pdp-variant-smoke',
                ['--variant' => $variant]
            );
            $passed = $exit === self::SUCCESS;
            $this->line(
                'VARIANT_' . strtoupper($variant)
                . '=' . ($passed ? 'PASS' : 'FAIL')
            );
            $ok = $ok && $passed;
        }

        $this->line(
            'PDP_VARIANT_MATRIX_SMOKE=' . ($ok ? 'PASS' : 'FAIL')
        );

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}

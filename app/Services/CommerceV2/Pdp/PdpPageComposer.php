<?php

namespace App\Services\CommerceV2\Pdp;

final class PdpPageComposer
{
    public const VERSION = 'linxen_pdp_page_composer_v1';

    public function __construct(
        protected PdpSectionRegistry $sections
    ) {
    }

    public function compose(
        array $presentation,
        array $viewModel
    ): array {
        return array_merge($presentation, [
            'composer_version' => self::VERSION,
            'sections' => data_get(
                $presentation,
                'renderer'
            ) === 'sectioned'
                ? $this->sections->compose(
                    $presentation,
                    $viewModel
                )
                : [],
            'assets' => (array) data_get(
                $presentation,
                'assets',
                []
            ),
        ]);
    }
}

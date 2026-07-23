<?php

namespace App\Services\CommerceV2\Pdp;

use Illuminate\Support\Str;

final class PdpProductStudyBuilder
{
    public const VERSION = 'linxen_pdp_product_study_v2';

    public const SHARED_MEDIA_ANGLE_CONTRACT = 'v350_shared_media_shot_angles';

    public function build(array $colors): array
    {
        return collect($colors)
            ->map(function ($color) {
                $color = (array) $color;
                $items = $this->itemsForColor($color);
                $inspirationItems = $this->inspirationItemsForColor($color);

                return [
                    'version' => self::VERSION,
                    'angle_contract' => self::SHARED_MEDIA_ANGLE_CONTRACT,
                    'color_id' => (string) data_get($color, 'id'),
                    'color_code' => (string) data_get($color, 'code'),
                    'color_label' => (string) data_get($color, 'label'),
                    'exact_color_only' => (bool) data_get(
                        $color,
                        'clarity_media_exact_color',
                        true
                    ),
                    'source_count' => (int) data_get(
                        $color,
                        'study_media_count',
                        count((array) data_get($color, 'study_media', []))
                    ),
                    'items' => $items,
                    'item_count' => count($items),
                    'inspiration_items' => $inspirationItems,
                    'inspiration_item_count' => count($inspirationItems),
                ];
            })
            ->values()
            ->all();
    }

    protected function itemsForColor(array $color): array
    {
        /*
         * ERP owns both the image angle and display label. Keep its source
         * ordering, deduplicate only by stable media identity, and never
         * promote an unlabelled asset to a synthetic hero/angle.
         */
        return collect((array) data_get($color, 'study_media', []))
            ->filter(fn ($media) => trim((string) data_get($media, 'url')) !== '')
            ->filter(fn ($media) => $this->isApprovedSingleAsset((array) $media))
            ->unique(fn ($media) => $this->mediaIdentity((array) $media))
            ->values()
            ->map(function ($media, int $index) {
                $media = (array) $media;

                return [
                    'angle_key' => $this->angleKey($media),
                    'angle_label' => $this->angleLabel($media),
                    'angle_description' => '',
                    'sequence' => $index,
                    'hero' => $media,
                    'alternates' => [],
                    'source_count' => 1,
                ];
            })
            ->filter(fn (array $item) => $item['angle_label'] !== '')
            ->values()
            ->all();
    }

    protected function mediaIdentity(array $media): string
    {
        return Str::squish((string) (
            data_get($media, 'media_identity')
            ?: data_get($media, 'id')
            ?: data_get($media, 'media_id')
            ?: data_get($media, 'asset_id')
            ?: data_get($media, 'url')
        ));
    }

    /**
     * This remains a separate, customer-facing inspiration layer.
     * It keeps the exact-colour, approved ERP selection order and is never
     * promoted into the reviewed product-angle sequence above.
     */
    protected function inspirationItemsForColor(array $color): array
    {
        return collect((array) data_get(
            $color,
            'demand_stimulation_media',
            []
        ))
            ->filter(fn ($media) => trim((string) data_get($media, 'url')) !== '')
            ->filter(fn ($media) => $this->isApprovedSingleAsset((array) $media))
            ->unique(fn ($media) => $this->mediaIdentity((array) $media))
            ->map(fn ($media) => (array) $media)
            ->values()
            ->all();
    }

    protected function angleKey(array $media): string
    {
        return Str::squish((string) (
            data_get($media, 'angle_key')
            ?: data_get($media, 'shot_angle')
        ));
    }

    protected function angleLabel(array $media): string
    {
        return Str::squish((string) (
            data_get($media, 'angle_label')
            ?: data_get($media, 'shot_angle_label')
        ));
    }

    protected function isApprovedSingleAsset(array $media): bool
    {
        $approval = Str::upper(Str::squish((string) data_get(
            $media,
            'approval_status'
        )));
        $assetSignals = Str::upper(Str::squish(implode(' ', [
            (string) data_get($media, 'asset_kind'),
            (string) data_get($media, 'media_kind'),
            (string) data_get($media, 'artifact_type'),
            (string) data_get($media, 'source_type'),
            (string) data_get($media, 'category_code'),
        ])));

        if (
            array_key_exists('is_approved', $media)
            && $media['is_approved'] !== null
            && ! $media['is_approved']
        ) {
            return false;
        }

        return ! in_array($approval, ['DRAFT', 'PENDING', 'REJECTED'], true)
            && ! Str::contains($assetSignals, [
                'BOARD',
                'GARMENT_TECHNICAL_FIT_TRUTH',
                'FIT_PROXY',
                'REFERENCE',
            ]);
    }
}

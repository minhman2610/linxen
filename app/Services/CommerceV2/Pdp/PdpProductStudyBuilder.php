<?php

namespace App\Services\CommerceV2\Pdp;

use Illuminate\Support\Str;

final class PdpProductStudyBuilder
{
    public const VERSION = 'linxen_pdp_product_study_v1';

    public function build(array $colors): array
    {
        return collect($colors)
            ->map(function ($color) {
                $color = (array) $color;
                $items = $this->itemsForColor($color);

                return [
                    'version' => self::VERSION,
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
                ];
            })
            ->values()
            ->all();
    }

    protected function itemsForColor(array $color): array
    {
        $rows = collect((array) data_get($color, 'study_media', []))
            ->filter(fn ($item) => trim((string) data_get($item, 'url')) !== '')
            ->unique(fn ($item) => trim((string) data_get($item, 'url')))
            ->values()
            ->map(function ($item, int $index) {
                $item = (array) $item;
                $semantic = $this->semantic($item);
                $key = $semantic['key'];

                if ($key === 'view') {
                    $key .= '_' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                }

                return [
                    'angle_key' => $key,
                    'angle_label' => $semantic['label'],
                    'angle_description' => $semantic['description'],
                    'sequence' => $semantic['sequence'],
                    'source_index' => $index,
                    'media' => $item,
                ];
            })
            ->sortBy(fn ($row) => sprintf(
                '%03d-%03d-%03d',
                (int) data_get($row, 'sequence', 99),
                (int) data_get($row, 'media.selection_tier', 99),
                (int) data_get($row, 'source_index', 0)
            ))
            ->values();

        /*
         * Every approved image receives its own full-width moment. Grouping
         * alternate angles behind a thumbnail forced customers to choose what
         * to see and made model photography feel incomplete on mobile.
         */
        return $rows
            ->values()
            ->map(function ($row) {
                $row = (array) $row;

                return [
                    'angle_key' => (string) data_get($row, 'angle_key'),
                    'angle_label' => (string) data_get($row, 'angle_label'),
                    'angle_description' => (string) data_get(
                        $row,
                        'angle_description'
                    ),
                    'sequence' => (int) data_get($row, 'sequence', 99),
                    'hero' => (array) data_get($row, 'media', []),
                    'alternates' => [],
                    'source_count' => 1,
                ];
            })
            ->values()
            ->all();
    }

    protected function semantic(array $media): array
    {
        $blob = Str::upper(Str::squish(implode(' ', [
            (string) data_get($media, 'shot_angle'),
            (string) data_get($media, 'role'),
            (string) data_get($media, 'category_code'),
        ])));

        if (Str::contains($blob, [
            'FRONT_3Q',
            'FRONT 3Q',
            'FRONT_3_4',
            'FRONT 3/4',
            '3/4 FRONT',
            'THREE_QUARTER_FRONT',
        ])) {
            return $this->definition(
                'front_three_quarter',
                'Góc trước 3/4',
                'Quan sát độ nổi khối, đường eo và cách phom chuyển từ thân trước sang thân bên.',
                20
            );
        }

        if (Str::contains($blob, [
            'BACK_3Q',
            'BACK 3Q',
            'BACK_3_4',
            'BACK 3/4',
            '3/4 BACK',
            'THREE_QUARTER_BACK',
        ])) {
            return $this->definition(
                'back_three_quarter',
                'Góc sau 3/4',
                'Nhìn rõ chuyển tiếp từ lưng, hông đến gấu váy ở góc chéo.',
                50
            );
        }

        if (Str::contains($blob, [
            'FULL_FRONT',
            'PRODUCT_FRONT',
            'FRONT_VIEW',
            'FRONT',
        ])) {
            return $this->definition(
                'front',
                'Mặt trước',
                'Toàn cảnh phom dáng, tỷ lệ và các chi tiết chính ở mặt trước.',
                10
            );
        }

        if (Str::contains($blob, [
            'FULL_BACK',
            'PRODUCT_BACK',
            'BACK_VIEW',
            'BACK',
        ])) {
            return $this->definition(
                'back',
                'Mặt sau',
                'Kiểm tra phom lưng, khóa và độ rơi của sản phẩm từ phía sau.',
                40
            );
        }

        if (Str::contains($blob, [
            'LEFT_SIDE',
            'RIGHT_SIDE',
            'PRODUCT_SIDE',
            'SIDE_VIEW',
            'PROFILE',
            'SIDE',
        ])) {
            return $this->definition(
                'side',
                'Góc nghiêng',
                'Đánh giá chiều sâu, độ dày và đường cong tự nhiên của phom.',
                30
            );
        }

        if (Str::contains($blob, [
            'DETAIL',
            'CLOSE_UP',
            'CLOSEUP',
            'MACRO',
            'TEXTURE',
        ])) {
            return $this->definition(
                'detail',
                'Chi tiết thiết kế',
                'Nhìn gần chất liệu, đường may và điểm nhấn quan trọng của sản phẩm.',
                60
            );
        }

        if (Str::contains($blob, [
            'LIFESTYLE',
            'ON_MODEL',
            'MODEL',
        ])) {
            return $this->definition(
                'on_model',
                'Trên người mẫu',
                'Hình dung tỷ lệ và chuyển động của sản phẩm khi mặc.',
                70
            );
        }

        return $this->definition(
            'view',
            'Góc nhìn sản phẩm',
            'Một góc ảnh đã được duyệt để làm rõ sản phẩm.',
            90
        );
    }

    protected function definition(
        string $key,
        string $label,
        string $description,
        int $sequence
    ): array {
        return compact('key', 'label', 'description', 'sequence');
    }
}

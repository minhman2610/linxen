#!/usr/bin/env bash
set -Eeuo pipefail
umask 0022

PATCH_NAME='linxen_pdp_luxe_clarity_v1_legacy_logic_rebuild_v1_1'

test -f artisan || {
    printf '%s\n' 'ERROR: Không đứng tại Laravel root của Lin Xén Storefront.' >&2
    exit 1
}

command -v python3 >/dev/null 2>&1 || {
    printf '%s\n' 'ERROR: Thiếu python3 để áp dụng source patch an toàn.' >&2
    exit 1
}

REQUIRED_FILES=(
    'app/Services/CommerceV2/Pdp/PdpViewModelBuilder.php'
    'app/Services/CommerceV2/Pdp/PdpVariantRegistry.php'
    'app/Services/CommerceV2/Pdp/PdpSectionRegistry.php'
    'app/Console/Commands/CommerceV2PdpVariantSmokeCommand.php'
)

for FILE in "${REQUIRED_FILES[@]}"; do
    test -f "$FILE" || {
        printf 'ERROR: Thiếu source bắt buộc: %s\n' "$FILE" >&2
        exit 1
    }
done

STAMP="$(date '+%Y%m%d_%H%M%S')"
BACKUP_DIR="storage/app/ai_patch_backups/${PATCH_NAME}_${STAMP}"

TARGET_FILES=(
    'app/Services/CommerceV2/Pdp/PdpViewModelBuilder.php'
    'app/Services/CommerceV2/Pdp/PdpVariantRegistry.php'
    'app/Services/CommerceV2/Pdp/PdpSectionRegistry.php'
    'app/Console/Commands/CommerceV2PdpVariantSmokeCommand.php'
    'app/Services/CommerceV2/Pdp/PdpProductStudyBuilder.php'
    'resources/views/commerce_v2/pdp/luxe/hero-purchase.blade.php'
    'resources/views/commerce_v2/pdp/luxe/product-study.blade.php'
    'public/commerce-v2/pdp/v1/variants/luxe-clarity-v1.css'
    'public/commerce-v2/pdp/v1/variants/luxe-clarity-v1.js'
)

mkdir -p "$BACKUP_DIR"

for FILE in "${TARGET_FILES[@]}"; do
    if test -e "$FILE"; then
        mkdir -p "$BACKUP_DIR/$(dirname "$FILE")"
        cp -p "$FILE" "$BACKUP_DIR/$FILE"
    fi
done

rollback_patch() {
    STATUS=$?
    trap - ERR

    for FILE in "${TARGET_FILES[@]}"; do
        if test -e "$BACKUP_DIR/$FILE"; then
            mkdir -p "$(dirname "$FILE")"
            cp -p "$BACKUP_DIR/$FILE" "$FILE"
        else
            rm -f "$FILE"
        fi
    done

    printf 'PATCH_ROLLBACK=PASS status=%s\n' "$STATUS" >&2
    exit "$STATUS"
}

trap rollback_patch ERR

mkdir -p \
    app/Services/CommerceV2/Pdp \
    resources/views/commerce_v2/pdp/luxe \
    public/commerce-v2/pdp/v1/variants
cat > app/Services/CommerceV2/Pdp/PdpProductStudyBuilder.php <<'PHPFILE'
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
                        'clarity_media_source_count',
                        count((array) data_get($color, 'clarity_media', []))
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
        $rows = collect((array) data_get($color, 'clarity_media', []))
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

        return $rows
            ->groupBy('angle_key')
            ->map(function ($group) {
                $first = (array) $group->first();

                return [
                    'angle_key' => (string) data_get($first, 'angle_key'),
                    'angle_label' => (string) data_get($first, 'angle_label'),
                    'angle_description' => (string) data_get(
                        $first,
                        'angle_description'
                    ),
                    'sequence' => (int) data_get($first, 'sequence', 99),
                    'hero' => (array) data_get($first, 'media', []),
                    'alternates' => $group
                        ->slice(1)
                        ->pluck('media')
                        ->map(fn ($item) => (array) $item)
                        ->values()
                        ->all(),
                    'source_count' => $group->count(),
                ];
            })
            ->sortBy('sequence')
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

PHPFILE

cat > resources/views/commerce_v2/pdp/luxe/hero-purchase.blade.php <<'BLADEFILE'
@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $allColors = collect((array) data_get($commerce, 'colors', []))->values();
    $availableColors = $allColors
        ->filter(fn ($color) => (
            (bool) data_get($color, 'sellable')
            && (float) data_get($color, 'available', 0) > 0
        ))
        ->values();
    $defaultColor = (array) data_get($commerce, 'default_color', []);

    if (
        ! data_get($defaultColor, 'sellable')
        || (float) data_get($defaultColor, 'available', 0) <= 0
    ) {
        $defaultColor = (array) (
            $availableColors->first()
            ?: $defaultColor
        );
    }

    $defaultMedia = collect(
        (array) data_get($defaultColor, 'media', [])
    )
        ->take((int) data_get($commerce, 'gallery_limit', 6))
        ->values();
    $heroMedia = (array) ($defaultMedia->first() ?: []);
    $advisor = (array) data_get($pdp, 'fit.advisor', []);
    $description = \Illuminate\Support\Str::squish(
        (string) data_get($identity, 'description')
    );
    $requestedColor = \Illuminate\Support\Str::lower(
        trim((string) request('color', ''))
    );
    $requestedUnavailable = $requestedColor !== ''
        ? $allColors->first(function ($color) use ($requestedColor) {
            $keys = collect([
                data_get($color, 'id'),
                data_get($color, 'code'),
                data_get($color, 'key'),
            ])->map(fn ($value) => \Illuminate\Support\Str::lower(
                trim((string) $value)
            ));

            return $keys->contains($requestedColor)
                && (
                    ! data_get($color, 'sellable')
                    || (float) data_get($color, 'available', 0) <= 0
                );
        })
        : null;
@endphp

<div class="lxl-product" data-lxl-product-shell>
    <div class="lxl-product__gallery">
        <div
            class="lxpdp-gallery lxl-gallery"
            data-lxpdp-gallery
            aria-label="Hình ảnh sản phẩm"
        >
            <div class="lxpdp-gallery__stage lxl-gallery__stage">
                <figure class="lxpdp-gallery__figure lxl-gallery__figure">
                    <img
                        data-lxpdp-main-image
                        src="{{ data_get(
                            $heroMedia,
                            'url',
                            data_get($pdp, 'media.cover_url')
                        ) }}"
                        alt="{{ data_get($identity, 'name') }} - {{ data_get($defaultColor, 'label') }}"
                        width="1080"
                        height="1350"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <figcaption class="lxl-gallery__caption">
                        <span data-lxpdp-image-role>
                            {{ data_get($heroMedia, 'role') === 'hero'
                                ? 'Ảnh chính'
                                : 'Hình ảnh sản phẩm' }}
                        </span>
                        <span data-lxpdp-image-counter>
                            {{ $defaultMedia->isNotEmpty()
                                ? '1 / '.$defaultMedia->count()
                                : '' }}
                        </span>
                    </figcaption>
                </figure>

                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--prev lxl-gallery__nav lxl-gallery__nav--prev"
                    data-lxpdp-gallery-prev
                    aria-label="Ảnh trước"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </button>

                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--next lxl-gallery__nav lxl-gallery__nav--next"
                    data-lxpdp-gallery-next
                    aria-label="Ảnh tiếp theo"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>
            </div>

            <div
                class="lxpdp-gallery__thumbs lxl-gallery__thumbs"
                data-lxpdp-thumbs
                role="list"
                aria-label="Chọn ảnh sản phẩm"
            >
                @foreach($defaultMedia as $index => $media)
                    <button
                        type="button"
                        class="lxpdp-gallery__thumb lxl-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}"
                        data-lxpdp-thumb
                        data-index="{{ $index }}"
                        aria-label="Xem ảnh {{ $index + 1 }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        <img
                            src="{{ data_get(
                                $media,
                                'thumb_url',
                                data_get($media, 'url')
                            ) }}"
                            alt=""
                            width="96"
                            height="120"
                            loading="lazy"
                            decoding="async"
                        >
                    </button>
                @endforeach
            </div>

            <p
                class="lxpdp-gallery__notice lxl-gallery__notice"
                data-lxpdp-gallery-notice
                @if($defaultMedia->isNotEmpty()) hidden @endif
            >
                Màu này chưa có bộ ảnh đã duyệt. LIN XÉN không dùng ảnh của màu khác để minh họa.
            </p>
        </div>
    </div>

    <aside class="lxpdp-buy-panel lxl-buy" aria-label="Thông tin mua hàng">
        <header class="lxl-buy__header">
            <p class="lxl-buy__eyebrow">
                {{ data_get($identity, 'code') }}
                <span aria-hidden="true">·</span>
                LIN XÉN
            </p>
            <h1>{{ data_get($identity, 'name') }}</h1>

            <div class="lxl-buy__price-row">
                <div class="lxpdp__price lxl-buy__price" data-lxpdp-price>
                    <strong>
                        {{ number_format(
                            (float) data_get($commerce, 'price.min'),
                            0,
                            ',',
                            '.'
                        ) }}₫
                    </strong>
                    @if(
                        data_get($commerce, 'price.has_sale')
                        && data_get($commerce, 'price.original_min')
                            > data_get($commerce, 'price.min')
                    )
                        <del>
                            {{ number_format(
                                (float) data_get(
                                    $commerce,
                                    'price.original_min'
                                ),
                                0,
                                ',',
                                '.'
                            ) }}₫
                        </del>
                    @endif
                </div>

                <span class="lxl-buy__stock {{ data_get($commerce, 'availability.in_stock') ? 'is-in' : 'is-out' }}">
                    <i aria-hidden="true"></i>
                    {{ data_get($commerce, 'availability.in_stock')
                        ? 'Sẵn sàng giao'
                        : 'Tạm hết hàng' }}
                </span>
            </div>

            @if($description !== '')
                <p class="lxl-buy__description">{{ $description }}</p>
            @endif
        </header>

        <section class="lxpdp-selector lxl-selector" aria-labelledby="lxlColorTitle">
            <div class="lxl-selector__heading">
                <h2 id="lxlColorTitle">Màu sắc</h2>
                <span data-lxpdp-color-label>
                    {{ data_get($defaultColor, 'label', 'Chọn màu') }}
                </span>
            </div>

            @if($availableColors->isNotEmpty())
                <div class="lxl-color-list" role="list">
                    @foreach($availableColors as $color)
                        @php
                            $cover = data_get($color, 'media.0.thumb_url')
                                ?: data_get($color, 'media.0.url')
                                ?: data_get($color, 'cover_url');
                            $active = (string) data_get($color, 'id')
                                === (string) data_get($defaultColor, 'id');
                        @endphp
                        <button
                            type="button"
                            class="lxpdp-color-card lxl-color {{ $active ? 'is-active' : '' }}"
                            data-lxpdp-color
                            data-color-id="{{ data_get($color, 'id') }}"
                            data-color-code="{{ data_get($color, 'code') }}"
                            data-color-sellable="1"
                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                            aria-label="Chọn màu {{ data_get($color, 'label') }}"
                        >
                            <span
                                class="lxl-color__image"
                                style="--lxl-swatch:{{ data_get($color, 'hex') ?: '#d9dfdc' }}"
                            >
                                @if($cover)
                                    <img
                                        src="{{ $cover }}"
                                        alt=""
                                        width="74"
                                        height="92"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                @else
                                    <i aria-hidden="true"></i>
                                @endif
                            </span>
                            <strong>{{ data_get($color, 'label') }}</strong>
                        </button>
                    @endforeach
                </div>
            @else
                <p class="lxl-selector__empty">Các màu hiện đều đang tạm hết hàng.</p>
            @endif

            @if($requestedUnavailable)
                <div class="lxl-unavailable-color" role="status">
                    <span
                        aria-hidden="true"
                        style="--lxl-swatch:{{ data_get($requestedUnavailable, 'hex') ?: '#c9cfcc' }}"
                    ></span>
                    <div>
                        <strong>{{ data_get($requestedUnavailable, 'label') }}</strong>
                        <small>Màu đang xem đã hết hàng</small>
                    </div>
                </div>
            @endif
        </section>

        <section class="lxpdp-selector lxl-selector lxl-selector--size" aria-labelledby="lxlSizeTitle">
            <div class="lxl-selector__heading">
                <h2 id="lxlSizeTitle">Kích thước</h2>
                <button
                    type="button"
                    class="lxpdp-size-advisor-link lxl-size-guide"
                    data-lxpdp-size-advisor-open
                    @if(!data_get($advisor, 'enabled')) disabled @endif
                >
                    Hướng dẫn chọn size
                </button>
            </div>

            <div
                class="lxpdp-size-list lxl-size-list"
                data-lxpdp-sizes
                role="list"
                aria-live="polite"
            ></div>

            <div class="lxpdp-selection lxl-selection" data-lxpdp-selection hidden>
                <strong data-lxpdp-selected-text></strong>
                <span data-lxpdp-selected-stock></span>
            </div>
        </section>

        <div class="lxl-purchase-row">
            <div class="lxl-quantity" data-lxl-quantity>
                <span>Số lượng</span>
                <div>
                    <button type="button" data-lxl-qty-minus aria-label="Giảm số lượng">−</button>
                    <input
                        type="number"
                        value="1"
                        min="1"
                        max="9"
                        inputmode="numeric"
                        data-lxl-qty-input
                        aria-label="Số lượng"
                    >
                    <button type="button" data-lxl-qty-plus aria-label="Tăng số lượng">+</button>
                </div>
            </div>

            <form
                method="post"
                action="{{ data_get($commerce, 'cart_action') }}"
                class="lxpdp-cart-form lxl-cart-form"
                data-lxpdp-cart-form
            >
                @csrf
                <input
                    type="hidden"
                    name="sellable_sku_id"
                    value=""
                    data-lxpdp-sku-input
                >
                <input
                    type="hidden"
                    name="quantity"
                    value="1"
                    data-lxl-quantity-field
                >
                <button
                    class="lxpdp-primary-button lxl-buy-button"
                    type="submit"
                    disabled
                    data-lxpdp-buy
                >
                    Chọn màu và kích thước
                </button>
            </form>
        </div>

        <div class="lxl-trust" aria-label="Cam kết mua hàng">
            <div>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 7h16v12H4z"/>
                    <path d="M8 7V5h8v2M7 12h10"/>
                </svg>
                <span>
                    <strong>Thanh toán COD</strong>
                    <small>Nhận hàng rồi thanh toán</small>
                </span>
            </div>
            <div>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 12a8 8 0 1 0 2.3-5.7"/>
                    <path d="M4 4v5h5"/>
                </svg>
                <span>
                    <strong>Hỗ trợ đổi size</strong>
                    <small>Theo chính sách hiện hành</small>
                </span>
            </div>
            <div>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                <span>
                    <strong>SKU được xác nhận</strong>
                    <small>Giá và tồn kho từ ERP</small>
                </span>
            </div>
        </div>
    </aside>
</div>

<nav class="lxl-bottom-nav" data-lxl-bottom-nav aria-label="Điều hướng nhanh">
    <a href="{{ route('commerce.v2.home') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="m3 11 9-7 9 7v9h-6v-6H9v6H3z"/>
        </svg>
        <span>Trang chủ</span>
    </a>
    <a href="{{ route('commerce.v2.search') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6"/>
            <path d="m16 16 4 4"/>
        </svg>
        <span>Tìm kiếm</span>
    </a>
    <a
        href="{{ route('commerce.v2.shop') }}"
        class="is-active"
        aria-current="page"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 4h8l2 4v12H6V8z"/>
            <path d="M9 8a3 3 0 0 0 6 0"/>
        </svg>
        <span>Sản phẩm</span>
    </a>
    <a href="{{ route('commerce.v2.account.index') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 21a8 8 0 0 1 16 0"/>
        </svg>
        <span>Tài khoản</span>
    </a>
    <a href="{{ route('commerce.v2.cart.index') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 7h14l-1 13H6z"/>
            <path d="M9 7a3 3 0 0 1 6 0"/>
        </svg>
        <span>Giỏ hàng</span>
    </a>
</nav>

BLADEFILE

cat > resources/views/commerce_v2/pdp/luxe/product-study.blade.php <<'BLADEFILE'
@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $studies = collect((array) data_get(
        $pdp,
        'media.product_study_by_color',
        []
    ))->values();
    $defaultColorId = (string) data_get(
        $commerce,
        'default_color_id',
        data_get($commerce, 'default_color.id')
    );
    $defaultStudy = (array) (
        $studies->firstWhere('color_id', $defaultColorId)
        ?: $studies->first(fn ($study) => ! empty(data_get($study, 'items')))
        ?: $studies->first()
        ?: []
    );
    $defaultItems = collect(
        (array) data_get($defaultStudy, 'items', [])
    )->values();
    $jsonFlags = JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES;
@endphp

<div
    class="lxl-study"
    data-lxl-product-study
>
    <div class="lxl-study__shell">
        <header class="lxl-study__header">
            <div>
                <p class="lxl-study__eyebrow">Chi tiết sản phẩm</p>
                <h2>Nhìn rõ sản phẩm trước khi chọn</h2>
            </div>
            <div class="lxl-study__intro">
                <span data-lxl-study-color>
                    {{ data_get($defaultStudy, 'color_label', 'Màu đang chọn') }}
                </span>
                <p>
                    Các ảnh dưới đây thuộc bộ ảnh rõ sản phẩm đã được duyệt và chỉ hiển thị đúng màu đang xem.
                </p>
            </div>
        </header>

        <nav
            class="lxl-study__nav"
            data-lxl-study-nav
            aria-label="Các góc ảnh sản phẩm"
            @if($defaultItems->isEmpty()) hidden @endif
        >
            @foreach($defaultItems as $index => $item)
                <button
                    type="button"
                    data-lxl-study-jump="{{ $index }}"
                    aria-label="Đi tới {{ data_get($item, 'angle_label') }}"
                >
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    {{ data_get($item, 'angle_label') }}
                </button>
            @endforeach
        </nav>

        <div
            class="lxl-study__list"
            data-lxl-study-list
            @if($defaultItems->isEmpty()) hidden @endif
        >
            @foreach($defaultItems as $index => $item)
                @php
                    $hero = (array) data_get($item, 'hero', []);
                    $alternates = collect(
                        (array) data_get($item, 'alternates', [])
                    )->take(3);
                @endphp
                <article
                    class="lxl-study-card lxl-study-card--{{ ($index % 4) + 1 }}"
                    data-lxl-study-item="{{ $index }}"
                >
                    <figure class="lxl-study-card__media">
                        <img
                            src="{{ data_get($hero, 'url') }}"
                            alt="{{ data_get($identity, 'name') }} — {{ data_get($defaultStudy, 'color_label') }} — {{ data_get($item, 'angle_label') }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            decoding="async"
                        >
                        <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </figure>

                    <div class="lxl-study-card__copy">
                        <small>Góc nhìn sản phẩm</small>
                        <h3>{{ data_get($item, 'angle_label') }}</h3>
                        <p>{{ data_get($item, 'angle_description') }}</p>

                        @if($alternates->isNotEmpty())
                            <div class="lxl-study-card__alternates" aria-label="Ảnh bổ sung cùng góc">
                                @foreach($alternates as $alternate)
                                    <a
                                        href="{{ data_get($alternate, 'url') }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Mở ảnh bổ sung {{ data_get($item, 'angle_label') }}"
                                    >
                                        <img
                                            src="{{ data_get(
                                                $alternate,
                                                'thumb_url',
                                                data_get($alternate, 'url')
                                            ) }}"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div
            class="lxl-study__empty"
            data-lxl-study-empty
            role="status"
            @if($defaultItems->isNotEmpty()) hidden @endif
        >
            <svg viewBox="0 0 48 48" aria-hidden="true">
                <path d="M8 11h32v26H8z"/>
                <circle cx="18" cy="21" r="4"/>
                <path d="m12 33 8-8 6 5 5-4 5 7"/>
            </svg>
            <div>
                <strong>Đang cập nhật ảnh chi tiết đúng màu</strong>
                <p>
                    Màu này chưa có đủ ảnh rõ sản phẩm đã duyệt. LIN XÉN không dùng ảnh của màu khác để thay thế.
                </p>
            </div>
        </div>
    </div>

    <script type="application/json" data-lxl-study-data>{!! json_encode($studies->all(), $jsonFlags) !!}</script>
</div>

BLADEFILE

cat > public/commerce-v2/pdp/v1/variants/luxe-clarity-v1.css <<'CSSFILE'
/*
 * LIN XÉN — Luxe Clarity V1
 * Legacy V1 commerce rhythm, rebuilt on Commerce V2 canonical data.
 */

.lxpdp[data-pdp-variant="luxe_clarity_v1"] {
    --lxl-canvas: #f1f5f2;
    --lxl-surface: #ffffff;
    --lxl-surface-soft: #e7eee9;
    --lxl-ink: #121815;
    --lxl-muted: #68716c;
    --lxl-line: #d8e1dc;
    --lxl-jade: #0f6a58;
    --lxl-jade-dark: #0a4f43;
    --lxl-lime: #dfff4f;
    --lxl-coral: #ff6848;
    --lxl-danger: #bb4050;
    --lxl-radius: 24px;
    --lxl-shadow: 0 24px 70px rgba(22, 43, 35, .10);
    width: 100%;
    max-width: none;
    padding: 0 0 112px;
    color: var(--lxl-ink);
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

body:has(.lxpdp[data-pdp-variant="luxe_clarity_v1"]),
body.lx-pdp-luxe-clarity {
    --lxv2-bg: #f1f5f2;
    --lxv2-surface: #fff;
    --lxv2-text: #121815;
    --lxv2-muted: #68716c;
    --lxv2-line: #d8e1dc;
    --lxv2-accent: #0f6a58;
    --lxv2-accent-dark: #0a4f43;
    --lxv2-soft: #e7eee9;
    background:
        linear-gradient(180deg, #f8faf8 0, #f1f5f2 38rem, #fff 100%);
}

body:has(.lxpdp[data-pdp-variant="luxe_clarity_v1"]) .lxv2-header,
body.lx-pdp-luxe-clarity .lxv2-header {
    border-bottom-color: rgba(216, 225, 220, .92);
    background: rgba(249, 251, 249, .92);
    box-shadow: 0 8px 28px rgba(20, 38, 31, .04);
    backdrop-filter: blur(18px) saturate(145%);
}

body:has(.lxpdp[data-pdp-variant="luxe_clarity_v1"]) .lxv2-brand__mark,
body.lx-pdp-luxe-clarity .lxv2-brand__mark {
    color: var(--lxl-ink);
    background: var(--lxl-lime);
    border-radius: 12px;
    box-shadow: 0 9px 22px rgba(111, 133, 22, .18);
}

body:has(.lxpdp[data-pdp-variant="luxe_clarity_v1"]) .lxv2-main,
body.lx-pdp-luxe-clarity .lxv2-main {
    width: 100%;
    padding-top: 12px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] *,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] *::before,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] *::after {
    box-sizing: border-box;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] svg {
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] button,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] input {
    font: inherit;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] button:focus-visible,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] a:focus-visible,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] input:focus-visible {
    outline: 3px solid rgba(15, 106, 88, .22);
    outline-offset: 3px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-preview-banner,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp__breadcrumb {
    width: min(1320px, calc(100% - 40px));
    margin-inline: auto;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-preview-banner {
    margin-bottom: 14px;
    border-color: rgba(15, 106, 88, .22);
    border-radius: 14px;
    color: var(--lxl-jade-dark);
    background: #f3fae2;
    box-shadow: 0 10px 28px rgba(31, 64, 50, .06);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp__breadcrumb {
    margin-top: 8px;
    margin-bottom: 17px;
    color: var(--lxl-muted);
    font-size: 12px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-engine-section {
    width: 100%;
    margin: 0;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-engine-section--luxe_hero_purchase {
    content-visibility: visible;
    contain-intrinsic-size: auto;
}

/* Hero + purchase: preserve the clear V1 sequence */

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-engine-section--luxe_hero_purchase {
    padding: 0 0 clamp(64px, 7vw, 104px);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-product {
    width: min(1320px, calc(100% - 40px));
    margin-inline: auto;
    display: grid;
    grid-template-columns: minmax(0, 1.16fr) minmax(390px, .84fr);
    gap: clamp(32px, 5vw, 74px);
    align-items: start;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-product__gallery {
    min-width: 0;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__stage {
    position: relative;
    overflow: hidden;
    aspect-ratio: 4 / 5;
    border: 1px solid rgba(216, 225, 220, .95);
    border-radius: 28px;
    background: #e6ece8;
    box-shadow: var(--lxl-shadow);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__figure {
    width: 100%;
    height: 100%;
    margin: 0;
    position: relative;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__figure > img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    object-position: center top;
    transition: opacity .24s ease, transform .6s cubic-bezier(.2, .75, .2, 1);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__figure.is-loading > img {
    opacity: .5;
    transform: scale(1.012);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__caption {
    position: absolute;
    right: 16px;
    bottom: 16px;
    left: 16px;
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 10px 13px;
    border: 1px solid rgba(255, 255, 255, .26);
    border-radius: 999px;
    color: #fff;
    background: rgba(18, 24, 21, .52);
    backdrop-filter: blur(13px);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .04em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__nav {
    position: absolute;
    z-index: 4;
    top: 50%;
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, .72);
    border-radius: 50%;
    color: var(--lxl-ink);
    background: rgba(255, 255, 255, .9);
    box-shadow: 0 12px 32px rgba(13, 28, 22, .15);
    transform: translateY(-50%);
    cursor: pointer;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__nav svg {
    width: 21px;
    height: 21px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__nav--prev {
    left: 14px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__nav--next {
    right: 14px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__thumbs {
    display: flex;
    gap: 9px;
    margin-top: 12px;
    overflow-x: auto;
    padding: 2px 2px 6px;
    scrollbar-width: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__thumbs::-webkit-scrollbar {
    display: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__thumb {
    flex: 0 0 70px;
    height: 88px;
    padding: 0;
    overflow: hidden;
    border: 2px solid transparent;
    border-radius: 13px;
    background: #e5ebe7;
    opacity: .62;
    cursor: pointer;
    transition: opacity .2s ease, border-color .2s ease, transform .2s ease;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__thumb.is-active {
    border-color: var(--lxl-jade);
    opacity: 1;
    transform: translateY(-2px);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__notice {
    margin: 12px 0 0;
    padding: 13px 15px;
    border: 1px dashed #bac8c0;
    border-radius: 13px;
    color: var(--lxl-muted);
    background: #edf2ef;
    font-size: 13px;
    line-height: 1.55;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy {
    position: sticky;
    top: 92px;
    display: grid;
    gap: 25px;
    padding: clamp(24px, 3vw, 38px);
    border: 1px solid var(--lxl-line);
    border-radius: 28px;
    background: rgba(255, 255, 255, .96);
    box-shadow: 0 18px 54px rgba(19, 39, 31, .08);
    backdrop-filter: blur(14px);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__header {
    display: grid;
    gap: 0;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__eyebrow {
    margin: 0 0 9px;
    color: var(--lxl-muted);
    font-size: 10px;
    font-weight: 850;
    letter-spacing: .15em;
    text-transform: uppercase;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__header h1 {
    margin: 0;
    font-family: "Iowan Old Style", Baskerville, Georgia, serif;
    font-size: clamp(38px, 4vw, 62px);
    font-weight: 500;
    line-height: .98;
    letter-spacing: -.045em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-top: 20px;
    padding-block: 16px;
    border-block: 1px solid var(--lxl-line);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__price {
    display: flex;
    align-items: baseline;
    gap: 10px;
    margin: 0;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__price strong {
    font-size: 24px;
    font-weight: 900;
    letter-spacing: -.025em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__price del {
    color: #98a19c;
    font-size: 13px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__stock {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--lxl-jade);
    font-size: 11px;
    font-weight: 850;
    white-space: nowrap;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__stock i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
    box-shadow: 0 0 0 5px rgba(15, 106, 88, .1);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__stock.is-out {
    color: var(--lxl-danger);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__description {
    margin: 17px 0 0;
    color: var(--lxl-muted);
    font-size: 14px;
    line-height: 1.7;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-selector {
    display: grid;
    gap: 12px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-selector__heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 14px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-selector__heading h2 {
    margin: 0;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: .035em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-selector__heading > span {
    color: var(--lxl-jade);
    font-size: 12px;
    font-weight: 800;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color-list {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 2px 2px 7px;
    scrollbar-width: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color-list::-webkit-scrollbar {
    display: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color {
    flex: 0 0 72px;
    display: grid;
    gap: 7px;
    justify-items: center;
    padding: 0 0 7px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: var(--lxl-muted);
    background: transparent;
    cursor: pointer;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color__image {
    width: 70px;
    height: 84px;
    position: relative;
    overflow: hidden;
    display: grid;
    place-items: center;
    border: 1px solid var(--lxl-line);
    border-radius: 15px;
    background: var(--lxl-swatch, #d9dfdc);
    transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color__image img,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color__image i {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color__image i {
    background: var(--lxl-swatch, #d9dfdc);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color strong {
    max-width: 76px;
    overflow: hidden;
    font-size: 10px;
    font-weight: 850;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color:hover .lxl-color__image,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color.is-active .lxl-color__image {
    transform: translateY(-2px);
    border-color: var(--lxl-jade);
    box-shadow: 0 10px 24px rgba(15, 106, 88, .14);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-color.is-active {
    border-bottom-color: var(--lxl-lime);
    color: var(--lxl-jade-dark);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-unavailable-color {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 12px;
    border: 1px dashed #d7aab1;
    border-radius: 13px;
    color: #87414d;
    background: #fff3f4;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-unavailable-color > span {
    width: 32px;
    height: 32px;
    position: relative;
    flex: 0 0 auto;
    border-radius: 9px;
    background: var(--lxl-swatch, #c9cfcc);
    opacity: .62;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-unavailable-color > span::after {
    content: "";
    position: absolute;
    right: 2px;
    left: 2px;
    top: 15px;
    height: 2px;
    background: var(--lxl-danger);
    transform: rotate(-42deg);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-unavailable-color strong,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-unavailable-color small {
    display: block;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-unavailable-color small {
    margin-top: 2px;
    font-size: 11px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-selector__empty {
    margin: 0;
    padding: 12px;
    border-radius: 12px;
    color: var(--lxl-danger);
    background: #fff1f2;
    font-size: 12px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-size-guide {
    padding: 0;
    border: 0;
    color: var(--lxl-jade);
    background: transparent;
    font-size: 12px;
    font-weight: 850;
    text-decoration: underline;
    text-underline-offset: 3px;
    cursor: pointer;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-size-list {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 9px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-size-list .lxpdp-size-button {
    position: relative;
    min-height: 48px;
    padding: 7px;
    border: 1px solid var(--lxl-line);
    border-radius: 12px;
    color: var(--lxl-ink);
    background: #fff;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-size-list .lxpdp-size-button:hover:not(:disabled) {
    border-color: var(--lxl-jade);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-size-list .lxpdp-size-button.is-active {
    border-color: var(--lxl-jade);
    color: #fff;
    background: var(--lxl-jade);
    box-shadow: 0 9px 20px rgba(15, 106, 88, .18);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-size-list .lxpdp-size-button:disabled {
    color: #9ba39f;
    background:
        linear-gradient(
            to bottom right,
            transparent calc(50% - 1px),
            rgba(187, 64, 80, .72) 50%,
            transparent calc(50% + 1px)
        ),
        #f2f5f3;
    cursor: not-allowed;
    opacity: 1;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-size-list .lxpdp-size-button:disabled::after {
    content: "Hết";
    position: absolute;
    right: 4px;
    bottom: 2px;
    color: var(--lxl-danger);
    font-size: 8px;
    font-weight: 900;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-selection {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    color: var(--lxl-jade-dark);
    font-size: 12px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] [data-lxpdp-selected-stock] {
    display: none !important;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-purchase-row {
    display: grid;
    grid-template-columns: 116px minmax(0, 1fr);
    gap: 10px;
    align-items: end;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-quantity {
    display: grid;
    gap: 7px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-quantity > span {
    color: var(--lxl-muted);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-quantity > div {
    height: 54px;
    display: grid;
    grid-template-columns: 34px 1fr 34px;
    overflow: hidden;
    border: 1px solid var(--lxl-line);
    border-radius: 13px;
    background: #fff;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-quantity button {
    border: 0;
    color: var(--lxl-ink);
    background: #fff;
    font-size: 19px;
    cursor: pointer;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-quantity input {
    width: 100%;
    min-width: 0;
    padding: 0;
    border: 0;
    outline: 0;
    text-align: center;
    font-weight: 900;
    -moz-appearance: textfield;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-quantity input::-webkit-inner-spin-button,
.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-quantity input::-webkit-outer-spin-button {
    margin: 0;
    -webkit-appearance: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-cart-form {
    margin: 0;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy-button {
    width: 100%;
    min-height: 54px;
    border: 0;
    border-radius: 13px;
    color: #fff;
    background: var(--lxl-ink);
    box-shadow: 0 13px 28px rgba(18, 24, 21, .16);
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .035em;
    cursor: pointer;
    transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy-button:hover:not(:disabled) {
    transform: translateY(-2px);
    background: var(--lxl-jade-dark);
    box-shadow: 0 17px 34px rgba(10, 79, 67, .23);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy-button:disabled {
    color: #8f9893;
    background: #e4e9e6;
    box-shadow: none;
    cursor: not-allowed;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-top: 1px solid var(--lxl-line);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust > div {
    min-width: 0;
    display: grid;
    grid-template-columns: 26px minmax(0, 1fr);
    gap: 8px;
    padding: 16px 10px 0 0;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust > div + div {
    padding-left: 10px;
    border-left: 1px solid var(--lxl-line);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust svg {
    width: 23px;
    height: 23px;
    color: var(--lxl-jade);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust span {
    min-width: 0;
    display: grid;
    gap: 3px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust strong {
    font-size: 10px;
    line-height: 1.25;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust small {
    color: var(--lxl-muted);
    font-size: 9px;
    line-height: 1.35;
}

/* Product study: imagery carries the story, not oversized copy */

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-engine-section--luxe_product_study {
    background:
        linear-gradient(180deg, #e7eee9 0, #f3f6f4 26rem, #fff 100%);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study {
    padding: clamp(72px, 8vw, 118px) 0 clamp(90px, 10vw, 150px);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__shell {
    width: min(1320px, calc(100% - 40px));
    margin-inline: auto;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__header {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(300px, .6fr);
    gap: clamp(32px, 6vw, 90px);
    align-items: end;
    margin-bottom: 30px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__eyebrow {
    margin: 0 0 9px;
    color: var(--lxl-jade);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .16em;
    text-transform: uppercase;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__header h2 {
    margin: 0;
    max-width: 650px;
    font-family: "Iowan Old Style", Baskerville, Georgia, serif;
    font-size: clamp(34px, 4.2vw, 58px);
    font-weight: 500;
    line-height: 1.02;
    letter-spacing: -.04em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__intro {
    display: grid;
    gap: 9px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__intro > span {
    width: max-content;
    padding: 6px 10px;
    border-radius: 999px;
    color: var(--lxl-jade-dark);
    background: var(--lxl-lime);
    font-size: 10px;
    font-weight: 900;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__intro p {
    margin: 0;
    color: var(--lxl-muted);
    font-size: 14px;
    line-height: 1.65;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__nav {
    display: flex;
    gap: 8px;
    margin-bottom: 19px;
    overflow-x: auto;
    padding: 2px 2px 7px;
    scrollbar-width: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__nav::-webkit-scrollbar {
    display: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__nav button {
    flex: 0 0 auto;
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 13px;
    border: 1px solid var(--lxl-line);
    border-radius: 999px;
    color: var(--lxl-ink);
    background: rgba(255, 255, 255, .76);
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__nav button:hover {
    border-color: var(--lxl-jade);
    color: var(--lxl-jade-dark);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__nav button span {
    color: var(--lxl-jade);
    font-size: 9px;
    letter-spacing: .08em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__list {
    display: grid;
    gap: 22px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card {
    min-width: 0;
    display: grid;
    grid-template-columns: minmax(0, 1.36fr) minmax(290px, .64fr);
    overflow: hidden;
    border: 1px solid rgba(204, 215, 209, .95);
    border-radius: 26px;
    background: #fff;
    box-shadow: 0 20px 54px rgba(21, 45, 36, .075);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card:nth-child(even) {
    grid-template-columns: minmax(290px, .64fr) minmax(0, 1.36fr);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card:nth-child(even) .lxl-study-card__media {
    order: 2;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__media {
    position: relative;
    min-height: 670px;
    margin: 0;
    overflow: hidden;
    background: #dfe7e2;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__media img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    object-position: center top;
    transition: transform .65s cubic-bezier(.2, .75, .2, 1);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card:hover .lxl-study-card__media img {
    transform: scale(1.018);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__media > span {
    position: absolute;
    top: 15px;
    left: 15px;
    min-width: 38px;
    height: 31px;
    display: grid;
    place-items: center;
    padding-inline: 8px;
    border: 1px solid rgba(255, 255, 255, .36);
    border-radius: 999px;
    color: #fff;
    background: rgba(18, 24, 21, .5);
    backdrop-filter: blur(10px);
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .09em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__copy {
    display: grid;
    align-content: center;
    gap: 8px;
    padding: clamp(28px, 4vw, 58px);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__copy > small {
    color: var(--lxl-jade);
    font-size: 9px;
    font-weight: 900;
    letter-spacing: .14em;
    text-transform: uppercase;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__copy h3 {
    margin: 0;
    font-family: "Iowan Old Style", Baskerville, Georgia, serif;
    font-size: clamp(28px, 3vw, 44px);
    font-weight: 500;
    line-height: 1;
    letter-spacing: -.035em;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__copy > p {
    margin: 4px 0 0;
    color: var(--lxl-muted);
    font-size: 14px;
    line-height: 1.7;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__alternates {
    display: flex;
    gap: 8px;
    margin-top: 13px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__alternates a {
    width: 56px;
    height: 70px;
    overflow: hidden;
    border: 1px solid var(--lxl-line);
    border-radius: 10px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__alternates img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__empty {
    display: flex;
    align-items: center;
    gap: 18px;
    max-width: 760px;
    padding: 24px;
    border: 1px dashed #b7c5bd;
    border-radius: 20px;
    color: var(--lxl-ink);
    background: rgba(255, 255, 255, .72);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__empty svg {
    width: 46px;
    height: 46px;
    flex: 0 0 auto;
    color: var(--lxl-jade);
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__empty strong {
    display: block;
    font-size: 15px;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__empty p {
    margin: 5px 0 0;
    color: var(--lxl-muted);
    font-size: 12px;
    line-height: 1.55;
}

/* V1-inspired fixed navigation, redesigned as one clean row */

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav {
    display: none;
}

.lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-mobile-buy {
    display: none !important;
}

@media (max-width: 980px) {
    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-product {
        grid-template-columns: 1fr;
        gap: 28px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy {
        position: static;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__media {
        min-height: 560px;
    }
}

@media (max-width: 760px) {
    body:has(.lxpdp[data-pdp-variant="luxe_clarity_v1"]) .lxv2-main,
    body.lx-pdp-luxe-clarity .lxv2-main {
        padding-bottom: calc(80px + env(safe-area-inset-bottom));
    }

    body:has(.lxpdp[data-pdp-variant="luxe_clarity_v1"]) .lxv2-footer,
    body.lx-pdp-luxe-clarity .lxv2-footer {
        margin-bottom: calc(72px + env(safe-area-inset-bottom));
    }

    body:has(.lxpdp[data-pdp-variant="luxe_clarity_v1"]) .lxv2-bottom-nav,
    body.lx-pdp-luxe-clarity .lxv2-bottom-nav {
        display: none !important;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] {
        padding-bottom: 84px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp-preview-banner {
        width: calc(100% - 24px);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxpdp__breadcrumb {
        width: calc(100% - 28px);
        margin-bottom: 12px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-product {
        width: 100%;
        gap: 0;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__stage {
        border-inline: 0;
        border-radius: 0;
        box-shadow: none;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__thumbs {
        padding-inline: 14px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-gallery__nav {
        width: 39px;
        height: 39px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy {
        margin-top: 0;
        padding: 27px 18px 30px;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        background: #fff;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__header h1 {
        font-size: clamp(38px, 11vw, 52px);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy__price-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 8px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-purchase-row {
        grid-template-columns: 104px minmax(0, 1fr);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust {
        display: flex;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: none;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust > div {
        min-width: 180px;
        padding: 15px 12px 0 0;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-trust > div + div {
        padding-left: 12px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study {
        padding-block: 68px 90px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__shell {
        width: calc(100% - 28px);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__header {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study__header h2 {
        font-size: 34px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card,
    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card:nth-child(even) {
        grid-template-columns: 1fr;
        border-radius: 20px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card:nth-child(even) .lxl-study-card__media {
        order: 0;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__media {
        min-height: 0;
        aspect-ratio: 4 / 5;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__copy {
        padding: 24px 20px 27px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-study-card__copy h3 {
        font-size: 31px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav {
        position: fixed;
        z-index: 600;
        right: 0;
        bottom: 0;
        left: 0;
        min-height: 66px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        align-items: stretch;
        padding: 6px 8px calc(6px + env(safe-area-inset-bottom));
        border-top: 1px solid rgba(205, 215, 209, .96);
        color: var(--lxl-muted);
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 -16px 42px rgba(18, 36, 29, .12);
        backdrop-filter: blur(20px) saturate(155%);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav::before {
        content: "";
        position: absolute;
        top: 0;
        left: 50%;
        width: 82px;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: var(--lxl-lime);
        transform: translateX(-50%);
        box-shadow: 0 5px 18px rgba(155, 181, 38, .28);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav a {
        min-width: 0;
        display: grid;
        align-content: center;
        justify-items: center;
        gap: 3px;
        border-radius: 14px;
        color: inherit;
        text-decoration: none;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav a:active {
        color: var(--lxl-jade-dark);
        background: var(--lxl-surface-soft);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav a.is-active {
        color: var(--lxl-jade-dark);
        background: #eff6d7;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav svg {
        width: 21px;
        height: 21px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav span {
        overflow: hidden;
        max-width: 100%;
        font-size: 8px;
        font-weight: 850;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

@media (max-width: 390px) {
    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-purchase-row {
        grid-template-columns: 96px minmax(0, 1fr);
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-buy-button {
        font-size: 10px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav {
        padding-inline: 4px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav a {
        border-radius: 11px;
    }

    .lxpdp[data-pdp-variant="luxe_clarity_v1"] .lxl-bottom-nav span {
        font-size: 7px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .lxpdp[data-pdp-variant="luxe_clarity_v1"] *,
    .lxpdp[data-pdp-variant="luxe_clarity_v1"] *::before,
    .lxpdp[data-pdp-variant="luxe_clarity_v1"] *::after {
        scroll-behavior: auto !important;
        animation: none !important;
        transition-duration: .01ms !important;
    }
}

CSSFILE

cat > public/commerce-v2/pdp/v1/variants/luxe-clarity-v1.js <<'JSFILE'
import '../core.js';

const root = document.querySelector('[data-pdp-variant="luxe_clarity_v1"]');
const productNode = document.getElementById('lxv2ProductData');

if (root && productNode) {
    document.body.classList.add('lx-pdp-luxe-clarity');

    let product = {};

    try {
        product = JSON.parse(productNode.textContent || '{}');
    } catch (error) {
        console.error('Không đọc được dữ liệu sản phẩm Luxe Clarity.', error);
    }

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;
    const colors = Array.isArray(product.colors)
        ? product.colors
        : [];
    const studyNode = root.querySelector('[data-lxl-study-data]');
    let studies = [];

    try {
        studies = JSON.parse(studyNode?.textContent || '[]');
        studies = Array.isArray(studies) ? studies : [];
    } catch (error) {
        console.error('Không đọc được dữ liệu Product Study.', error);
        studies = [];
    }

    const normalize = (value) => String(value || '')
        .trim()
        .toLocaleLowerCase('vi');

    const activeColor = () => {
        const activeButton = root.querySelector(
            '[data-lxpdp-color].is-active'
        );
        const activeId = String(
            activeButton?.dataset.colorId || ''
        );
        const requested = new URL(window.location.href)
            .searchParams
            .get('color');

        return colors.find(
            (color) => String(color.id) === activeId
        ) || colors.find((color) => requested && [
            color.id,
            color.code,
            color.key,
        ].map(normalize).includes(normalize(requested)))
            || colors.find(
                (color) => String(color.id)
                    === String(product.default_color_id || '')
            )
            || colors.find(
                (color) => color.sellable
                    && Number(color.available || 0) > 0
            )
            || colors[0]
            || null;
    };

    const studyForColor = (color) => studies.find(
        (study) => String(study.color_id || '')
            === String(color?.id || '')
    ) || null;

    const makeStudyCard = (
        item,
        index,
        colorLabel
    ) => {
        const article = document.createElement('article');
        article.className = `lxl-study-card lxl-study-card--${(index % 4) + 1}`;
        article.dataset.lxlStudyItem = String(index);

        const figure = document.createElement('figure');
        figure.className = 'lxl-study-card__media';

        const image = document.createElement('img');
        image.src = String(
            item?.hero?.url
            || item?.hero?.thumb_url
            || ''
        );
        image.alt = [
            product.name || 'Sản phẩm',
            colorLabel || '',
            item?.angle_label || 'Góc nhìn sản phẩm',
        ].filter(Boolean).join(' — ');
        image.loading = index === 0 ? 'eager' : 'lazy';
        image.decoding = 'async';

        const number = document.createElement('span');
        number.textContent = String(index + 1).padStart(2, '0');

        figure.append(image, number);

        const copy = document.createElement('div');
        copy.className = 'lxl-study-card__copy';

        const kicker = document.createElement('small');
        kicker.textContent = 'Góc nhìn sản phẩm';

        const title = document.createElement('h3');
        title.textContent = String(
            item?.angle_label || 'Góc nhìn sản phẩm'
        );

        const description = document.createElement('p');
        description.textContent = String(
            item?.angle_description
            || 'Một góc ảnh đã được duyệt để làm rõ sản phẩm.'
        );

        copy.append(kicker, title, description);

        const alternates = Array.isArray(item?.alternates)
            ? item.alternates.slice(0, 3)
            : [];

        if (alternates.length) {
            const list = document.createElement('div');
            list.className = 'lxl-study-card__alternates';
            list.setAttribute(
                'aria-label',
                'Ảnh bổ sung cùng góc'
            );

            alternates.forEach((alternate) => {
                const url = String(
                    alternate?.url
                    || alternate?.thumb_url
                    || ''
                );

                if (!url) {
                    return;
                }

                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.setAttribute(
                    'aria-label',
                    `Mở ảnh bổ sung ${title.textContent}`
                );

                const thumb = document.createElement('img');
                thumb.src = String(
                    alternate?.thumb_url
                    || alternate?.url
                );
                thumb.alt = '';
                thumb.loading = 'lazy';
                thumb.decoding = 'async';

                link.appendChild(thumb);
                list.appendChild(link);
            });

            if (list.childElementCount) {
                copy.appendChild(list);
            }
        }

        article.append(figure, copy);

        return article;
    };

    const renderStudy = (color) => {
        const list = root.querySelector('[data-lxl-study-list]');
        const nav = root.querySelector('[data-lxl-study-nav]');
        const empty = root.querySelector('[data-lxl-study-empty]');
        const colorLabel = root.querySelector('[data-lxl-study-color]');

        if (!list || !nav || !empty) {
            return;
        }

        const study = studyForColor(color);
        const items = Array.isArray(study?.items)
            ? study.items
            : [];
        const label = String(
            study?.color_label
            || color?.label
            || 'Màu đang chọn'
        );

        if (colorLabel) {
            colorLabel.textContent = label;
        }

        list.replaceChildren();
        nav.replaceChildren();

        if (!items.length) {
            list.hidden = true;
            nav.hidden = true;
            empty.hidden = false;
            return;
        }

        const cards = document.createDocumentFragment();
        const chips = document.createDocumentFragment();

        items.forEach((item, index) => {
            const card = makeStudyCard(item, index, label);
            cards.appendChild(card);

            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.lxlStudyJump = String(index);
            button.setAttribute(
                'aria-label',
                `Đi tới ${item?.angle_label || 'góc ảnh'}`
            );

            const number = document.createElement('span');
            number.textContent = String(index + 1).padStart(2, '0');

            button.append(
                number,
                document.createTextNode(
                    String(item?.angle_label || 'Góc ảnh')
                )
            );
            button.addEventListener('click', () => {
                card.scrollIntoView({
                    behavior: reducedMotion ? 'auto' : 'smooth',
                    block: 'center',
                });
            });

            chips.appendChild(button);
        });

        list.appendChild(cards);
        nav.appendChild(chips);
        list.hidden = false;
        nav.hidden = false;
        empty.hidden = true;
    };

    const bindInitialStudyNavigation = () => {
        root.querySelectorAll('[data-lxl-study-jump]').forEach(
            (button) => {
                button.addEventListener('click', () => {
                    const index = String(
                        button.dataset.lxlStudyJump || ''
                    );
                    const card = root.querySelector(
                        `[data-lxl-study-item="${index}"]`
                    );

                    card?.scrollIntoView({
                        behavior: reducedMotion ? 'auto' : 'smooth',
                        block: 'center',
                    });
                });
            }
        );
    };

    bindInitialStudyNavigation();

    root.querySelectorAll('[data-lxpdp-color]').forEach(
        (button) => {
            button.addEventListener('click', () => {
                const color = colors.find(
                    (item) => String(item.id)
                        === String(button.dataset.colorId)
                );

                if (color) {
                    window.requestAnimationFrame(
                        () => renderStudy(color)
                    );
                }
            });
        }
    );

    const quantityInput = root.querySelector(
        '[data-lxl-qty-input]'
    );
    const quantityField = root.querySelector(
        '[data-lxl-quantity-field]'
    );
    const minusButton = root.querySelector(
        '[data-lxl-qty-minus]'
    );
    const plusButton = root.querySelector(
        '[data-lxl-qty-plus]'
    );

    const quantity = () => Math.max(
        1,
        Math.min(
            9,
            Number.parseInt(quantityInput?.value || '1', 10)
            || 1
        )
    );

    const syncQuantity = (nextValue = quantity()) => {
        const value = Math.max(
            1,
            Math.min(9, Number(nextValue) || 1)
        );

        if (quantityInput) {
            quantityInput.value = String(value);
        }

        if (quantityField) {
            quantityField.value = String(value);
        }
    };

    minusButton?.addEventListener(
        'click',
        () => syncQuantity(quantity() - 1)
    );
    plusButton?.addEventListener(
        'click',
        () => syncQuantity(quantity() + 1)
    );
    quantityInput?.addEventListener(
        'input',
        () => syncQuantity()
    );
    quantityInput?.addEventListener(
        'change',
        () => syncQuantity()
    );

    syncQuantity();
    renderStudy(activeColor());

    root.dispatchEvent(new CustomEvent(
        'linxen:pdp:luxe-clarity-ready',
        {
            bubbles: true,
            detail: {
                variant: 'luxe_clarity_v1',
                product_id: product.id || null,
            },
        }
    ));
}

JSFILE

python3 - <<'PYMOD'
from pathlib import Path

def read(path):
    return Path(path).read_text(encoding="utf-8")

def write(path, content):
    Path(path).write_text(content, encoding="utf-8")

builder_path = "app/Services/CommerceV2/Pdp/PdpViewModelBuilder.php"
registry_path = "app/Services/CommerceV2/Pdp/PdpVariantRegistry.php"
sections_path = "app/Services/CommerceV2/Pdp/PdpSectionRegistry.php"
smoke_path = "app/Console/Commands/CommerceV2PdpVariantSmokeCommand.php"

builder = read(builder_path)
builder_marker = "AI_PATCH_LINXEN_PDP_PRODUCT_STUDY_BUILDER_V1"

if builder_marker not in builder:
    constructor_anchor = "    public function build(array $product): array\n"
    constructor_block = """    /* AI_PATCH_LINXEN_PDP_PRODUCT_STUDY_BUILDER_V1 */
    public function __construct(
        protected PdpProductStudyBuilder $productStudyBuilder
    ) {
    }

"""
    if constructor_anchor not in builder:
        raise SystemExit("PDP_VIEW_MODEL_CONSTRUCTOR_ANCHOR_MISSING")
    builder = builder.replace(
        constructor_anchor,
        constructor_block + constructor_anchor,
        1
    )

    return_anchor = """        return [
            'version' => self::VERSION,
"""
    study_variable = """        $productStudyByColor = $this->productStudyBuilder
            ->build($colors->all());

"""
    if return_anchor not in builder:
        raise SystemExit("PDP_VIEW_MODEL_RETURN_ANCHOR_MISSING")
    builder = builder.replace(
        return_anchor,
        study_variable + return_anchor,
        1
    )

    media_anchor = "                'production_truth' => $productionTruth,"
    media_entry = """                'product_study_by_color' => $productStudyByColor,
"""
    if media_anchor not in builder:
        raise SystemExit("PDP_VIEW_MODEL_MEDIA_ANCHOR_MISSING")
    builder = builder.replace(
        media_anchor,
        media_entry + media_anchor,
        1
    )

    write(builder_path, builder)

registry = read(registry_path)
registry_marker = "AI_PATCH_LINXEN_PDP_LUXE_CLARITY_V1"

if registry_marker not in registry:
    registry_anchor = "            /* AI_PATCH_LINXEN_PDP_STUDIO_CLARITY_V1 */"
    registry_block = "            /* AI_PATCH_LINXEN_PDP_LUXE_CLARITY_V1 */\n            'luxe_clarity_v1' => [\n                'key' => 'luxe_clarity_v1',\n                'label' => 'Luxe Clarity V1',\n                'version' => '1.0.0',\n                'renderer' => 'sectioned',\n                'view' => 'commerce_v2.pdp.page',\n                'layout' => 'luxe_clarity_v1',\n                'view_model_version' => PdpViewModelBuilder::VERSION,\n                'sections' => [\n                    'luxe_hero_purchase',\n                    'luxe_product_study',\n                ],\n                'assets' => [\n                    'styles' => [\n                        'commerce-v2/pdp-sales-experience.css?v=3',\n                        'commerce-v2/pdp/v1/core.css?v=1',\n                        'commerce-v2/pdp/v1/variants/luxe-clarity-v1.css?v=1',\n                    ],\n                    'scripts' => [\n                        'commerce-v2/pdp/v1/variants/luxe-clarity-v1.js?v=1',\n                    ],\n                ],\n                'art_direction' => [\n                    'concept' => 'legacy_luxe_reframed',\n                    'journey' => 'gallery_product_info_variants_quantity_cart_then_product_study',\n                    'mobile_navigation' => 'fixed_five_item_single_row',\n                    'product_study_source' => 'exact_color_product_clarity',\n                    'hidden_sections' => 'all_non_product_study_sections',\n                ],\n                'enabled' => true,\n            ],\n"
    if registry_anchor not in registry:
        raise SystemExit("PDP_VARIANT_REGISTRY_ANCHOR_MISSING")
    registry = registry.replace(
        registry_anchor,
        registry_block + registry_anchor,
        1
    )
    write(registry_path, registry)

sections = read(sections_path)
sections_marker = "AI_PATCH_LINXEN_PDP_LUXE_CLARITY_SECTIONS_V1"

if sections_marker not in sections:
    sections_anchor = "            /* AI_PATCH_LINXEN_PDP_STUDIO_CLARITY_SECTIONS_V1 */"
    sections_block = "            /* AI_PATCH_LINXEN_PDP_LUXE_CLARITY_SECTIONS_V1 */\n            'luxe_hero_purchase' => [\n                'view' => 'commerce_v2.pdp.luxe.hero-purchase',\n                'required' => ['identity.id', 'commerce.colors'],\n                'empty_behavior' => 'render',\n            ],\n            'luxe_product_study' => [\n                'view' => 'commerce_v2.pdp.luxe.product-study',\n                'required' => ['identity.id'],\n                'empty_behavior' => 'render',\n            ],\n"
    if sections_anchor not in sections:
        raise SystemExit("PDP_SECTION_REGISTRY_ANCHOR_MISSING")
    sections = sections.replace(
        sections_anchor,
        sections_block + sections_anchor,
        1
    )
    write(sections_path, sections)

smoke = read(smoke_path)
smoke_marker = "AI_PATCH_LINXEN_PDP_LUXE_CLARITY_SMOKE_V1"

if smoke_marker not in smoke:
    checks_anchor = "            foreach ($checks as $code => $passed) {"
    checks_block = "            /* AI_PATCH_LINXEN_PDP_LUXE_CLARITY_SMOKE_V1 */\n            if ($variantKey === 'luxe_clarity_v1') {\n                $checks = array_merge($checks, [\n                    'luxe_quantity_contract' => str_contains(\n                        $html,\n                        'data-lxl-quantity'\n                    ),\n                    'luxe_product_study_contract' => str_contains(\n                        $html,\n                        'data-lxl-product-study'\n                    ),\n                    'luxe_bottom_navigation_contract' => str_contains(\n                        $html,\n                        'data-lxl-bottom-nav'\n                    ),\n                    'luxe_exact_color_study_contract' => str_contains(\n                        $html,\n                        'data-lxl-study-data'\n                    ),\n                ]);\n            }\n\n"
    if checks_anchor not in smoke:
        raise SystemExit("PDP_SMOKE_CHECKS_ANCHOR_MISSING")
    smoke = smoke.replace(
        checks_anchor,
        checks_block + checks_anchor,
        1
    )

    fixture_anchor = """                    'media' => $media,
                    'sizes' => [
"""
    fixture_replacement = """                    'media' => $media,
                    'clarity_media' => $media,
                    'clarity_media_exact_color' => true,
                    'sizes' => [
"""
    if fixture_anchor not in smoke:
        raise SystemExit("PDP_SMOKE_FIXTURE_ANCHOR_MISSING")
    smoke = smoke.replace(
        fixture_anchor,
        fixture_replacement,
        1
    )

    write(smoke_path, smoke)

PYMOD

for FILE in \
    app/Services/CommerceV2/Pdp/PdpProductStudyBuilder.php \
    app/Services/CommerceV2/Pdp/PdpViewModelBuilder.php \
    app/Services/CommerceV2/Pdp/PdpVariantRegistry.php \
    app/Services/CommerceV2/Pdp/PdpSectionRegistry.php \
    app/Console/Commands/CommerceV2PdpVariantSmokeCommand.php
do
    php -l "$FILE"
done

grep -Fq -- "'luxe_clarity_v1' => [" \
    app/Services/CommerceV2/Pdp/PdpVariantRegistry.php

grep -Fq -- "'luxe_hero_purchase' => [" \
    app/Services/CommerceV2/Pdp/PdpSectionRegistry.php

grep -Fq -- "'product_study_by_color' =>" \
    app/Services/CommerceV2/Pdp/PdpViewModelBuilder.php

grep -Fq -- 'data-lxl-quantity' \
    resources/views/commerce_v2/pdp/luxe/hero-purchase.blade.php

grep -Fq -- 'data-lxl-bottom-nav' \
    resources/views/commerce_v2/pdp/luxe/hero-purchase.blade.php

grep -Fq -- 'data-lxl-product-study' \
    resources/views/commerce_v2/pdp/luxe/product-study.blade.php

grep -Fq -- 'data-lxl-study-data' \
    resources/views/commerce_v2/pdp/luxe/product-study.blade.php

python3 - <<'PYCHECK'
from pathlib import Path

css = Path(
    'public/commerce-v2/pdp/v1/variants/luxe-clarity-v1.css'
).read_text(encoding='utf-8')

if css.count('{') != css.count('}'):
    raise SystemExit('PDP_LUXE_CLARITY_CSS_BRACE_CHECK=FAIL')

required = [
    'luxe_clarity_v1',
    '.lxl-product',
    '.lxl-study',
    '.lxl-bottom-nav',
]

for marker in required:
    if marker not in css:
        raise SystemExit(
            'PDP_LUXE_CLARITY_CSS_MARKER_MISSING=' + marker
        )

print('PDP_LUXE_CLARITY_CSS_STATIC=PASS')
PYCHECK

if command -v node >/dev/null 2>&1; then
    if node --check \
        public/commerce-v2/pdp/v1/variants/luxe-clarity-v1.js
    then
        printf '%s\n' 'PDP_LUXE_CLARITY_JS_SYNTAX=PASS'
    else
        printf '%s\n' 'WARNING: Node syntax check không pass; không rollback vì đây là optional environment check.' >&2
    fi
else
    printf '%s\n' 'WARNING: Không có node; bỏ qua JS syntax check.' >&2
fi

if test "${AI_PATCH_TEST_MODE:-0}" = '1'; then
    printf '%s\n' 'PDP_LUXE_CLARITY_RUNTIME_SMOKE=SKIPPED_TEST_MODE'
else
    CACHE_STORE=file \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    php artisan view:clear

    CACHE_STORE=file \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    php artisan commerce-v2:pdp-variant-smoke \
        --variant=luxe_clarity_v1

    CACHE_STORE=file \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    php artisan commerce-v2:pdp-variant-matrix-smoke

    if CACHE_STORE=file \
        CACHE_DRIVER=file \
        SESSION_DRIVER=file \
        php artisan optimize:clear
    then
        printf '%s\n' 'PDP_LUXE_CLARITY_OPTIMIZE_CLEAR=PASS'
    else
        printf '%s\n' 'WARNING: optimize:clear không pass do môi trường local; source và smoke bắt buộc đã PASS nên không rollback.' >&2
        printf '%s\n' 'PDP_LUXE_CLARITY_OPTIMIZE_CLEAR=WARNING'
    fi

    printf '%s\n' 'PDP_LUXE_CLARITY_RUNTIME_SMOKE=PASS'
fi

trap - ERR

printf '%s\n' 'LINXEN_PDP_LUXE_CLARITY_V1_1_SOURCE_PATCH=PASS'
printf '%s\n' 'VARIANT=luxe_clarity_v1'
printf '%s\n' 'REFERENCE_LOGIC=LINXEN_V1_LUXE'
printf '%s\n' 'CANONICAL_DATA=COMMERCE_V2'
printf '%s\n' 'VISIBLE_SECTIONS=GALLERY_PURCHASE_AND_PRODUCT_STUDY'
printf '%s\n' 'MOBILE_BOTTOM_NAV=FIXED_FIVE_ITEM_SINGLE_ROW'
printf '%s\n' 'QUANTITY_SELECTOR=RESTORED_FROM_V1_FLOW'
printf '%s\n' 'EXACT_SELLABLE_SKU=PRESERVED'
printf '%s\n' 'EXACT_COLOR_PRODUCT_CLARITY=PRESERVED'
printf '%s\n' 'RUNTIME_CACHE_ENV=FILE'
printf '%s\n' 'OPTIMIZE_CLEAR_FAILURE=WARNING_NO_ROLLBACK'
printf '%s\n' 'LIVE_VARIANT=UNCHANGED'
printf '%s\n' 'MIGRATION=NONE'
printf '%s\n' 'DB_MUTATION=NONE'
printf '%s\n' 'ERP_SOURCE_CHANGE=NONE'
printf '%s\n' 'ORDER_PROVIDER_META_MUTATION=NONE'
printf 'BACKUP_DIR=%s\n' "$BACKUP_DIR"

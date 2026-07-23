@php
    $canonicalSizes = collect(['XS', 'S', 'M', 'L', 'XL']);
    $normalizeColorLabel = fn ($value) => mb_strtolower(trim((string) $value));
    $allColors = collect((array) data_get($product, 'colors', []))
        ->values();
    $availableSizesForColor = function ($color) use ($canonicalSizes) {
        $sizeOptions = collect((array) data_get(
            $color,
            'size_options',
            []
        ));

        if ($sizeOptions->isNotEmpty()) {
            return $sizeOptions
                ->filter(fn ($option) => (bool) data_get(
                    $option,
                    'in_stock',
                    false
                ))
                ->map(fn ($option) => strtoupper(trim((string) data_get(
                    $option,
                    'size'
                ))))
                ->filter(fn ($size) => $canonicalSizes->contains($size))
                ->unique()
                ->values();
        }

        return collect((array) data_get($color, 'available_sizes', []))
            ->map(fn ($size) => strtoupper(trim((string) $size)))
            ->filter(fn ($size) => $canonicalSizes->contains($size))
            ->unique()
            ->values();
    };
    $colors = $allColors
        ->filter(fn ($color) => (bool) data_get($color, 'sellable'))
        ->values();
    $productAvailableSizes = $colors
        ->flatMap(fn ($color) => $availableSizesForColor($color))
        ->unique()
        ->values();
    $sizesByColorId = $allColors
        ->filter(fn ($color) => (string) data_get($color, 'id') !== '')
        ->mapWithKeys(fn ($color) => [
            (string) data_get($color, 'id') => $availableSizesForColor(
                $color
            )->all(),
        ]);
    $sizesByColorLabel = $allColors
        ->filter(fn ($color) => trim((string) data_get($color, 'label')) !== '')
        ->mapWithKeys(fn ($color) => [
            $normalizeColorLabel(data_get($color, 'label')) => $availableSizesForColor(
                $color
            )->all(),
        ]);
    $availableSizesFor = function ($option) use (
        $normalizeColorLabel,
        $productAvailableSizes,
        $sizesByColorId,
        $sizesByColorLabel
    ) {
        $colorId = (string) data_get($option, 'color_id');
        $colorLabel = $normalizeColorLabel(data_get($option, 'label'));

        return collect(
            $sizesByColorId->get(
                $colorId,
                $sizesByColorLabel->get(
                    $colorLabel,
                    $productAvailableSizes->all()
                )
            )
        );
    };
    $strictListingMedia = request()->routeIs(
        'commerce.v2.home',
        'commerce.v2.home.products'
    );
    $isSalesInboxMedia = fn ($option) => str_contains(
        strtoupper((string) data_get($option, 'job_category')),
        'SALES_INBOX'
    );
    $mediaOptions = collect((array) data_get($product, 'listing_media', []))
        ->filter(fn ($option) => (string) data_get($option, 'url') !== '')
        ->filter($isSalesInboxMedia)
        ->unique('url')
        ->unique(fn ($option) => $normalizeColorLabel(
            data_get($option, 'color_id')
                ?: data_get($option, 'label')
                ?: data_get($option, 'url')
        ))
        ->take(4)
        ->values();
    if ($mediaOptions->isEmpty() && !$strictListingMedia) {
        $fallbackImage = (string) data_get($product, 'cover_url');
        $mediaOptions = collect([[
            'url' => $fallbackImage,
            'label' => 'Ảnh chính',
            'hex' => '#ead8cf',
        ]])
            ->merge($colors->map(fn ($color) => [
                'url' => data_get($color, 'cover_url'),
                'label' => data_get($color, 'label', 'Màu sản phẩm'),
                'hex' => data_get($color, 'hex', '#ead8cf'),
            ]))
            ->filter(fn ($option) => (string) data_get($option, 'url') !== '')
            ->unique('url')
            ->take(4)
            ->values();
    }
    $reelSalesInboxMedia = collect((array) data_get(
        $product,
        'listing_media',
        []
    ))
        ->filter(fn ($option) => (string) data_get($option, 'url') !== '')
        ->filter($isSalesInboxMedia)
        ->map(fn ($option) => [
            'color_id' => (string) data_get($option, 'color_id'),
            'label' => (string) data_get($option, 'label', 'Màu sản phẩm'),
            'hex' => (string) data_get($option, 'hex', '#ead8cf'),
            'url' => (string) (
                data_get($option, 'full_url')
                ?: data_get($option, 'url')
            ),
            'thumb_url' => (string) data_get($option, 'url'),
            'job_category' => (string) data_get(
                $option,
                'job_category'
            ),
        ])
        ->filter(fn ($option) => $option['url'] !== '')
        ->unique('url')
        ->take(24)
        ->values()
        ->all();
    $reelSalesInboxMediaJson = json_encode(
        $reelSalesInboxMedia,
        JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    );
    $defaultImage = (string) data_get($mediaOptions->first(), 'url');
    $defaultAvailableSizes = $availableSizesFor(
        (array) ($mediaOptions->first() ?? [])
    );
    $eagerImage = (bool) ($eager ?? false);
@endphp

<article
    class="lxcv1-product-card"
    data-lxcv1-product-card
    data-lxreel-product
    data-lxreel-url="{{ data_get($product, 'url') }}"
    data-lxreel-name="{{ data_get($product, 'name') }}"
    data-lxreel-price="{{ number_format((float) data_get($product, 'price_min'), 0, ',', '.') }}₫"
    data-lxreel-original-price="{{ data_get($product, 'has_sale') && data_get($product, 'original_min') > data_get($product, 'price_min') ? number_format((float) data_get($product, 'original_min'), 0, ',', '.').'₫' : '' }}"
    data-lxreel-sale-inbox-media="{{ $reelSalesInboxMediaJson ?: '[]' }}"
    data-lxcv1-product-stock="{{ data_get($product, 'in_stock') ? '1' : '0' }}"
    data-lxcv1-product-sizes="{{ $productAvailableSizes->implode('|') }}"
    data-lxcv1-product-colors="{{ $colors->pluck('label')->filter()->implode('|') }}"
    @if($mediaOptions->count() > 1) data-lxcv1-auto-media @endif
>
    <div class="lxcv1-product-card__media-shell">
        <a
            class="lxcv1-product-card__media"
            href="{{ data_get($product, 'url') }}"
            aria-label="Xem {{ data_get($product, 'name') }}"
        >
            <img
                src="{{ $defaultImage }}"
                alt="{{ data_get($product, 'cover_alt', data_get($product, 'name')) }}"
                width="480"
                height="600"
                sizes="(max-width: 720px) 50vw, (max-width: 1100px) 33vw, 25vw"
                @if($eagerImage)
                    loading="eager"
                    fetchpriority="high"
                @else
                    loading="lazy"
                @endif
                decoding="async"
                data-lxcv1-product-image
            >
        </a>
    </div>

    <div class="lxcv1-product-card__body">
        <div class="lxcv1-product-card__meta">
            <span
                @class([
                    'lxcv1-product-card__status',
                    'is-sale' => data_get($product, 'has_sale'),
                    'is-stock' => !data_get($product, 'has_sale') && data_get($product, 'in_stock'),
                ])
            >
                {{ data_get($product, 'has_sale') ? 'Sale' : (data_get($product, 'in_stock') ? 'Sẵn hàng' : 'Liên hệ') }}
            </span>
            <span class="lxcv1-product-card__code">
                {{ data_get($product, 'code') }}
            </span>
        </div>

        <a href="{{ data_get($product, 'url') }}">
            <h3>{{ data_get($product, 'name') }}</h3>
        </a>

        <div class="lxcv1-product-card__price">
            <strong>
                {{ number_format((float) data_get($product, 'price_min'), 0, ',', '.') }}₫
            </strong>
            @if(
                data_get($product, 'has_sale')
                && data_get($product, 'original_min') > data_get($product, 'price_min')
            )
                <del>
                    {{ number_format((float) data_get($product, 'original_min'), 0, ',', '.') }}₫
                </del>
            @endif
        </div>

        @if($mediaOptions->isNotEmpty())
            <div class="lxcv1-product-card__color-row">
                <div class="lxcv1-product-card__colors" aria-label="Đổi ảnh theo màu">
                    @foreach($mediaOptions as $index => $option)
                        @php($optionSizes = $availableSizesFor($option))
                        <button
                            type="button"
                            title="{{ data_get($option, 'label') }}"
                            aria-label="Xem {{ data_get($option, 'label') }}"
                            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                            @class(['is-active' => $index === 0])
                            style="--lxcv1-swatch:{{ data_get($option, 'hex') ?: '#ead8cf' }}"
                            data-lxcv1-color-image
                            data-color-id="{{ data_get($option, 'color_id') }}"
                            data-color-hex="{{ data_get($option, 'hex') ?: '#ead8cf' }}"
                            data-image="{{ data_get($option, 'url') }}"
                            data-job-category="{{ data_get($option, 'job_category') }}"
                            data-label="{{ data_get($option, 'label') }}"
                            data-sizes="{{ $optionSizes->implode(',') }}"
                        ></button>
                    @endforeach
                </div>
                <small data-lxcv1-color-label>
                    {{ data_get($mediaOptions->first(), 'label') }}
                </small>
            </div>
        @endif

        <div class="lxcv1-product-card__sizes" aria-label="Tình trạng kích thước theo màu">
            <span>Kích thước</span>
            <div>
                @foreach($canonicalSizes as $size)
                    @php($isAvailable = $defaultAvailableSizes->contains($size))
                    <small
                        data-lxcv1-size
                        data-size="{{ $size }}"
                        @class(['is-unavailable' => !$isAvailable])
                        aria-disabled="{{ $isAvailable ? 'false' : 'true' }}"
                        title="Size {{ $size }} {{ $isAvailable ? 'còn hàng' : 'đã hết' }}"
                    >{{ $size }}</small>
                @endforeach
            </div>
        </div>
    </div>
</article>

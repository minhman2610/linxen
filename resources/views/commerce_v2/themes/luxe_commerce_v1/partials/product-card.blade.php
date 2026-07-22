@php
    $colors = collect((array) data_get($product, 'colors', []))
        ->filter(fn ($color) => (
            (bool) data_get($color, 'sellable')
            && (string) data_get($color, 'cover_url') !== ''
        ))
        ->values();
    $sizes = $colors
        ->flatMap(fn ($color) => (array) data_get($color, 'available_sizes', []))
        ->filter()
        ->unique()
        ->values();
    $defaultImage = (string) data_get($product, 'cover_url');
    $matchingColor = $colors->first(
        fn ($color) => data_get($color, 'cover_url') === $defaultImage
    );
    $mediaOptions = collect([[
        'url' => $defaultImage,
        'label' => data_get($matchingColor, 'label', 'Ảnh chính'),
        'hex' => data_get($matchingColor, 'hex', '#d7ddd9'),
    ]])
        ->merge(
            $colors
                ->reject(fn ($color) => data_get($color, 'cover_url') === $defaultImage)
                ->map(fn ($color) => [
                    'url' => data_get($color, 'cover_url'),
                    'label' => data_get($color, 'label', 'Màu sản phẩm'),
                    'hex' => data_get($color, 'hex', '#d7ddd9'),
                ])
        )
        ->filter(fn ($option) => (string) data_get($option, 'url') !== '')
        ->unique('url')
        ->take(8)
        ->values();
    $eagerImage = (bool) ($eager ?? false);
@endphp

<article class="lxcv1-product-card" data-lxcv1-product-card>
    <div class="lxcv1-product-card__media-shell">
        <a
            class="lxcv1-product-card__media"
            href="{{ data_get($product, 'url') }}"
            aria-label="Xem {{ data_get($product, 'name') }}"
        >
            <img
                src="{{ $defaultImage }}"
                alt="{{ data_get($product, 'cover_alt', data_get($product, 'name')) }}"
                width="720"
                height="900"
                @if($eagerImage)
                    loading="eager"
                    fetchpriority="high"
                @else
                    loading="lazy"
                @endif
                decoding="async"
                data-lxcv1-product-image
            >

            @if(data_get($product, 'has_sale'))
                <span class="lxcv1-product-card__badge">Sale</span>
            @elseif(data_get($product, 'in_stock'))
                <span class="lxcv1-product-card__badge lxcv1-product-card__badge--soft">Sẵn hàng</span>
            @endif

            <span class="lxcv1-product-card__open" aria-hidden="true">Xem thiết kế ↗</span>
        </a>

        @if($mediaOptions->count() > 1)
            <button
                class="lxcv1-product-card__slide lxcv1-product-card__slide--prev"
                type="button"
                data-lxcv1-color-step="-1"
                aria-label="Xem màu trước"
            >
                ‹
            </button>
            <button
                class="lxcv1-product-card__slide lxcv1-product-card__slide--next"
                type="button"
                data-lxcv1-color-step="1"
                aria-label="Xem màu tiếp theo"
            >
                ›
            </button>
        @endif
    </div>

    <div class="lxcv1-product-card__body">
        <div class="lxcv1-product-card__meta">
            <span>{{ data_get($product, 'code') }}</span>
            @if($sizes->isNotEmpty())
                <span>{{ $sizes->join(' · ') }}</span>
            @endif
        </div>

        <a href="{{ data_get($product, 'url') }}">
            <h3>{{ data_get($product, 'short_name') ?: data_get($product, 'name') }}</h3>
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
                        <button
                            type="button"
                            title="{{ data_get($option, 'label') }}"
                            aria-label="Xem màu {{ data_get($option, 'label') }}"
                            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                            @class(['is-active' => $index === 0])
                            style="--lxcv1-swatch:{{ data_get($option, 'hex') ?: '#d7ddd9' }}"
                            data-lxcv1-color-image
                            data-image="{{ data_get($option, 'url') }}"
                            data-label="{{ data_get($option, 'label') }}"
                        ></button>
                    @endforeach
                </div>
                <small data-lxcv1-color-label>
                    {{ data_get($mediaOptions->first(), 'label') }}
                </small>
            </div>
        @endif
    </div>
</article>

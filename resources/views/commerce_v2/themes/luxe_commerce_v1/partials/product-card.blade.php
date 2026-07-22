@php
    $colors = collect((array) data_get($product, 'colors', []))
        ->filter(fn ($color) => (bool) data_get($color, 'sellable'))
        ->values();
    $sizes = $colors
        ->flatMap(fn ($color) => (array) data_get($color, 'available_sizes', []))
        ->filter()
        ->unique()
        ->values();
    $strictListingMedia = request()->routeIs(
        'commerce.v2.home',
        'commerce.v2.home.products'
    );
    $mediaOptions = collect((array) data_get($product, 'listing_media', []))
        ->filter(fn ($option) => (string) data_get($option, 'url') !== '')
        ->unique('url')
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
    $defaultImage = (string) data_get($mediaOptions->first(), 'url');
    $eagerImage = (bool) ($eager ?? false);
@endphp

<article
    class="lxcv1-product-card"
    data-lxcv1-product-card
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
                        <button
                            type="button"
                            title="{{ data_get($option, 'label') }}"
                            aria-label="Xem {{ data_get($option, 'label') }}"
                            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                            @class(['is-active' => $index === 0])
                            style="--lxcv1-swatch:{{ data_get($option, 'hex') ?: '#ead8cf' }}"
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

        @if($sizes->isNotEmpty())
            <div class="lxcv1-product-card__sizes" aria-label="Kích thước có sẵn">
                <span>Kích thước</span>
                <div>
                    @foreach($sizes->take(5) as $size)
                        <small>{{ $size }}</small>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</article>

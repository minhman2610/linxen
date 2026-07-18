@php
    $colors = collect((array) data_get($product, 'colors', []))
        ->filter(fn ($color) => (bool) data_get($color, 'sellable'))
        ->values();
    $sizes = $colors
        ->flatMap(fn ($color) => (array) data_get($color, 'available_sizes', []))
        ->filter()
        ->unique()
        ->values();
@endphp

<article class="lxcv1-product-card" data-lxcv1-product-card>
    <a class="lxcv1-product-card__media" href="{{ data_get($product, 'url') }}">
        <img
            src="{{ data_get($product, 'cover_url') }}"
            alt="{{ data_get($product, 'cover_alt', data_get($product, 'name')) }}"
            width="720"
            height="900"
            loading="lazy"
            decoding="async"
        >

        @if(data_get($product, 'has_sale'))
            <span class="lxcv1-product-card__badge">Sale</span>
        @elseif(data_get($product, 'in_stock'))
            <span class="lxcv1-product-card__badge lxcv1-product-card__badge--soft">Sẵn hàng</span>
        @endif

        <span class="lxcv1-product-card__open" aria-hidden="true">Xem thiết kế ↗</span>
    </a>

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

        <div class="lxcv1-product-card__colors" aria-label="Màu đang có">
            @foreach($colors->take(5) as $color)
                <span
                    title="{{ data_get($color, 'label') }}"
                    style="--lxcv1-swatch:{{ data_get($color, 'hex') ?: '#d7ddd9' }}"
                ></span>
            @endforeach
            @if($colors->count() > 5)
                <small>+{{ $colors->count() - 5 }}</small>
            @elseif($colors->isNotEmpty())
                <small>{{ $colors->count() }} màu</small>
            @endif
        </div>
    </div>
</article>

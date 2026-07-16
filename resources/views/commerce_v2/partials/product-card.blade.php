<article class="lxv2-card">
    <a class="lxv2-card__media" href="{{ $product['url'] }}">
        <img
            src="{{ $product['cover_url'] }}"
            alt="{{ $product['cover_alt'] }}"
            loading="lazy"
            width="720"
            height="900"
        >
        @if($product['has_sale'])
            <span class="lxv2-card__badge">Ưu đãi</span>
        @endif
    </a>

    <div class="lxv2-card__body">
        <div class="lxv2-card__meta">
            <span>{{ $product['code'] }}</span>
            <span>{{ $product['in_stock'] ? 'Còn hàng' : 'Tạm hết' }}</span>
        </div>

        <h3><a href="{{ $product['url'] }}">{{ $product['name'] }}</a></h3>

        <div class="lxv2-price">
            <strong>{{ number_format($product['price_min'], 0, ',', '.') }}₫</strong>
            @if($product['has_sale'] && $product['original_min'] > $product['price_min'])
                <del>{{ number_format($product['original_min'], 0, ',', '.') }}₫</del>
            @endif
        </div>

        @if(!empty($product['colors']))
            <div class="lxv2-swatches" aria-label="Màu sản phẩm">
                @foreach(array_slice($product['colors'], 0, 6) as $color)
                    <span
                        class="lxv2-swatch"
                        title="{{ $color['label'] }}"
                        style="--swatch: {{ $color['hex'] ?: '#d8d0ca' }}"
                    ></span>
                @endforeach
                @if(count($product['colors']) > 6)
                    <small>+{{ count($product['colors']) - 6 }}</small>
                @endif
            </div>
        @endif
    </div>
</article>

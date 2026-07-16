@extends('commerce_v2.layouts.app')

@section('og_type', 'product')

@section('content')
<section class="lxv2-pdp" data-lxv2-product>
    <div class="lxv2-gallery">
        <div class="lxv2-gallery__main">
            <img
                data-lxv2-main-image
                src="{{ $product['cover_url'] }}"
                alt="{{ $product['name'] }}"
                width="900"
                height="1125"
            >
        </div>

        @if(!empty($product['media']))
            <div class="lxv2-gallery__thumbs">
                @foreach(array_slice($product['media'], 0, 12) as $index => $media)
                    <button
                        type="button"
                        class="{{ $index === 0 ? 'active' : '' }}"
                        data-lxv2-thumb
                        data-image="{{ $media['url'] }}"
                        data-color="{{ $media['color_code'] }}"
                        aria-label="Xem ảnh {{ $index + 1 }}"
                    >
                        <img
                            src="{{ $media['thumb_url'] }}"
                            alt=""
                            loading="lazy"
                        >
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="lxv2-pdp__info">
        <p class="lxv2-eyebrow">{{ $product['code'] }}</p>
        <h1>{{ $product['name'] }}</h1>

        <div class="lxv2-pdp__price" data-lxv2-price>
            <strong>{{ number_format($product['price_min'], 0, ',', '.') }}₫</strong>
            @if(
                $product['has_sale']
                && $product['original_min'] > $product['price_min']
            )
                <del>{{ number_format($product['original_min'], 0, ',', '.') }}₫</del>
            @endif
        </div>

        <p class="lxv2-stock">
            {{ $product['in_stock'] ? 'Đang có hàng' : 'Tạm hết hàng' }}
            @if($product['available_total'] > 0)
                · {{ (int) $product['available_total'] }} sản phẩm khả dụng
            @endif
        </p>

        @if(!empty($product['description']))
            <div class="lxv2-description">
                {{ $product['description'] }}
            </div>
        @endif

        <div class="lxv2-selector">
            <div class="lxv2-selector__label">
                <strong>Màu sắc</strong>
                <span data-lxv2-color-label>Chọn màu</span>
            </div>

            <div class="lxv2-color-options">
                @foreach($product['colors'] as $colorIndex => $color)
                    <button
                        type="button"
                        class="lxv2-color-option"
                        data-lxv2-color
                        data-color-index="{{ $colorIndex }}"
                        data-code="{{ $color['code'] }}"
                        data-label="{{ $color['label'] }}"
                        data-cover="{{ $color['cover_url'] }}"
                        {{ $color['sellable'] ? '' : 'disabled' }}
                    >
                        <span
                            style="--swatch:{{ $color['hex'] ?: '#d8d0ca' }}"
                        ></span>
                        <small>{{ $color['label'] }}</small>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="lxv2-selector">
            <div class="lxv2-selector__label">
                <strong>Kích thước</strong>
                <span data-lxv2-size-label>Chọn màu trước</span>
            </div>
            <div
                class="lxv2-size-options"
                data-lxv2-sizes
            ></div>
        </div>

        <div
            class="lxv2-selection-summary"
            data-lxv2-selection
            hidden
        >
            <span data-lxv2-selected-text></span>
            <small data-lxv2-selected-stock></small>
        </div>

        <button
            class="lxv2-button lxv2-button--wide"
            type="button"
            disabled
            data-lxv2-buy
        >
            Chọn màu và kích thước
        </button>

        <p class="lxv2-next-phase-note">
            Giỏ hàng và thanh toán an toàn sẽ được mở ở giai đoạn tiếp theo.
        </p>

        @if(!empty($product['specs']))
            <div class="lxv2-specs">
                <h2>Thông tin thiết kế</h2>
                <dl>
                    @foreach($product['specs'] as $spec)
                        <div>
                            <dt>{{ data_get($spec, 'label') }}</dt>
                            <dd>{{ data_get($spec, 'value') }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        @if(!empty($product['support_media']))
            <div class="lxv2-support-media">
                <h2>Thông tin hỗ trợ</h2>
                @foreach($product['support_media'] as $media)
                    <a
                        href="{{ $media['url'] }}"
                        target="_blank"
                        rel="noopener"
                    >
                        {{
                            $media['support_role'] === 'size_chart'
                                ? 'Xem bảng kích thước'
                                : 'Xem hướng dẫn'
                        }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

<script
    type="application/json"
    id="lxv2ProductData"
>{!! $productPayloadJson !!}</script>
@endsection


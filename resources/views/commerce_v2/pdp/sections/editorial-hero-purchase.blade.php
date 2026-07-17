@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $colors = collect((array) data_get($commerce, 'colors', []));
    $defaultColor = (array) data_get($commerce, 'default_color', []);
    $defaultMedia = collect((array) data_get($defaultColor, 'media', []));
    $heroMedia = (array) ($defaultMedia->first() ?: []);
    $advisor = (array) data_get($pdp, 'fit.advisor', []);
    $highlights = collect((array) data_get($pdp, 'product_truth.highlights', []))->take(3);
@endphp

<div class="lxpdp-editorial-hero">
    <div class="lxpdp-editorial-hero__gallery">
        <div class="lxpdp-editorial-eyebrow">
            <span>LIN XÉN EDIT</span>
            <small>{{ data_get($identity, 'code') }}</small>
        </div>

        <div class="lxpdp-gallery" data-lxpdp-gallery aria-label="Hình ảnh sản phẩm">
            <div class="lxpdp-gallery__stage">
                <button type="button" class="lxpdp-gallery__nav lxpdp-gallery__nav--prev" data-lxpdp-gallery-prev aria-label="Ảnh trước">‹</button>
                <figure class="lxpdp-gallery__figure">
                    <img
                        data-lxpdp-main-image
                        src="{{ data_get($heroMedia, 'url', data_get($pdp, 'media.cover_url')) }}"
                        alt="{{ data_get($identity, 'name') }} - {{ data_get($defaultColor, 'label') }}"
                        width="1040"
                        height="1300"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <figcaption class="lxpdp-gallery__caption">
                        <span data-lxpdp-image-role>{{ data_get($heroMedia, 'role') === 'hero' ? 'Ảnh chính' : 'Hình ảnh sản phẩm' }}</span>
                        <span data-lxpdp-image-counter>{{ $defaultMedia->isNotEmpty() ? '1 / '.$defaultMedia->count() : '' }}</span>
                    </figcaption>
                </figure>
                <button type="button" class="lxpdp-gallery__nav lxpdp-gallery__nav--next" data-lxpdp-gallery-next aria-label="Ảnh tiếp theo">›</button>
            </div>

            <div class="lxpdp-gallery__thumbs" data-lxpdp-thumbs role="list" aria-label="Chọn ảnh sản phẩm">
                @foreach($defaultMedia as $index => $media)
                    <button
                        type="button"
                        class="lxpdp-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}"
                        data-lxpdp-thumb
                        data-index="{{ $index }}"
                        aria-label="Xem ảnh {{ $index + 1 }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        <img src="{{ data_get($media, 'thumb_url', data_get($media, 'url')) }}" alt="" width="96" height="120" loading="lazy" decoding="async">
                    </button>
                @endforeach
            </div>

            <p class="lxpdp-gallery__notice" data-lxpdp-gallery-notice @if($defaultMedia->isNotEmpty()) hidden @endif>
                Màu này chưa có bộ ảnh đã duyệt. LIN XÉN không dùng ảnh của màu khác để minh họa.
            </p>
        </div>
    </div>

    <aside class="lxpdp-buy-panel lxpdp-editorial-buy" aria-label="Thông tin mua hàng">
        <div class="lxpdp-editorial-buy__intro">
            <p class="lxpdp-kicker">Thiết kế dành cho khoảnh khắc của bạn</p>
            <h1>{{ data_get($identity, 'name') }}</h1>
            @if(data_get($identity, 'description'))
                <p class="lxpdp-editorial-buy__description">{{ data_get($identity, 'description') }}</p>
            @endif
        </div>

        <div class="lxpdp__price" data-lxpdp-price>
            <strong>{{ number_format((float) data_get($commerce, 'price.min'), 0, ',', '.') }}₫</strong>
            @if(data_get($commerce, 'price.has_sale') && data_get($commerce, 'price.original_min') > data_get($commerce, 'price.min'))
                <del>{{ number_format((float) data_get($commerce, 'price.original_min'), 0, ',', '.') }}₫</del>
            @endif
        </div>

        <p class="lxpdp__availability">
            <span class="{{ data_get($commerce, 'availability.in_stock') ? 'is-in-stock' : 'is-out-of-stock' }}">
                {{ data_get($commerce, 'availability.in_stock') ? 'Đang có hàng' : 'Tạm hết hàng' }}
            </span>
            @if(data_get($commerce, 'availability.available_total') > 0)
                <span>· {{ (int) data_get($commerce, 'availability.available_total') }} sản phẩm khả dụng</span>
            @endif
        </p>

        @if($highlights->isNotEmpty())
            <ul class="lxpdp-editorial-facts" aria-label="Thông tin nhanh">
                @foreach($highlights as $highlight)
                    <li><span>{{ data_get($highlight, 'label') }}</span><strong>{{ data_get($highlight, 'value') }}</strong></li>
                @endforeach
            </ul>
        @endif

        <section class="lxpdp-selector" aria-labelledby="lxpdpColorTitleEditorial">
            <div class="lxpdp-selector__heading">
                <h2 id="lxpdpColorTitleEditorial">Chọn màu</h2>
                <span data-lxpdp-color-label>{{ data_get($defaultColor, 'label', 'Chọn màu') }}</span>
            </div>
            <div class="lxpdp-color-list lxpdp-color-list--editorial" role="list">
                @foreach($colors as $color)
                    @php
                        $cover = data_get($color, 'media.0.thumb_url') ?: data_get($color, 'cover_url');
                        $active = data_get($color, 'id') === data_get($defaultColor, 'id');
                    @endphp
                    <button
                        type="button"
                        class="lxpdp-color-card {{ $active ? 'is-active' : '' }}"
                        data-lxpdp-color
                        data-color-id="{{ data_get($color, 'id') }}"
                        data-color-code="{{ data_get($color, 'code') }}"
                        data-color-sellable="{{ data_get($color, 'sellable') ? '1' : '0' }}"
                        aria-pressed="{{ $active ? 'true' : 'false' }}"
                    >
                        <span class="lxpdp-color-card__visual">
                            @if($cover)
                                <img src="{{ $cover }}" alt="" width="64" height="80" loading="lazy">
                            @else
                                <i style="--lxpdp-swatch:{{ data_get($color, 'hex') ?: '#d9d1cb' }}"></i>
                            @endif
                        </span>
                        <span><strong>{{ data_get($color, 'label') }}</strong><small>{{ data_get($color, 'sellable') ? (int) data_get($color, 'available').' còn hàng' : 'Tạm hết' }}</small></span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="lxpdp-selector" aria-labelledby="lxpdpSizeTitleEditorial">
            <div class="lxpdp-selector__heading">
                <h2 id="lxpdpSizeTitleEditorial">Chọn kích thước</h2>
                <button type="button" class="lxpdp-size-advisor-link" data-lxpdp-size-advisor-open @if(!data_get($advisor, 'enabled')) disabled @endif>Tìm size phù hợp</button>
            </div>
            <div class="lxpdp-size-list" data-lxpdp-sizes role="list" aria-live="polite"></div>
            <div class="lxpdp-selection" data-lxpdp-selection hidden>
                <strong data-lxpdp-selected-text></strong>
                <span data-lxpdp-selected-stock></span>
            </div>
        </section>

        <form method="post" action="{{ data_get($commerce, 'cart_action') }}" class="lxpdp-cart-form" data-lxpdp-cart-form>
            @csrf
            <input type="hidden" name="sellable_sku_id" value="" data-lxpdp-sku-input>
            <input type="hidden" name="quantity" value="1">
            <button class="lxpdp-primary-button lxpdp-editorial-buy__cta" type="submit" disabled data-lxpdp-buy>Chọn màu và kích thước</button>
        </form>

        <div class="lxpdp-editorial-reassurance">
            <span>COD</span>
            <span>Tồn kho ERP</span>
            <span>Hỗ trợ chọn size</span>
        </div>
    </aside>
</div>

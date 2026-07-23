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
                <figure class="lxpdp-gallery__figure lxl-gallery__figure {{ $defaultMedia->isEmpty() ? 'is-empty' : '' }}">
                    <img
                        data-lxpdp-main-image
                        @if(data_get($heroMedia, 'url'))
                            src="{{ data_get($heroMedia, 'url') }}"
                        @endif
                        alt="{{ data_get($identity, 'name') }} - {{ data_get($defaultColor, 'label') }}"
                        width="1080"
                        height="1350"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <div
                        class="lxl-gallery__empty-state"
                        data-lxl-gallery-empty
                        @if($defaultMedia->isNotEmpty()) hidden @endif
                    >
                        <span
                            aria-hidden="true"
                            style="--lxl-swatch:{{ data_get($defaultColor, 'hex') ?: '#ead8cf' }}"
                        ></span>
                        <strong data-lxl-gallery-empty-title>
                            Ảnh màu {{ data_get($defaultColor, 'label', 'này') }} đang được hoàn thiện
                        </strong>
                        <p data-lxl-gallery-empty-copy>
                            Bạn vẫn có thể xem thông tin, chọn size và đặt hàng. LIN XÉN không dùng ảnh của màu khác để thay thế.
                        </p>
                    </div>
                </figure>
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
                Màu này chưa có ảnh riêng. LIN XÉN giữ trải nghiệm trung thực và không thay bằng ảnh của màu khác.
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

@include('commerce_v2.themes.luxe_commerce_v1.shell.bottom-nav')

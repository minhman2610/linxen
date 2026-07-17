@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $colors = collect((array) data_get($commerce, 'colors', []));
    $defaultColor = (array) data_get($commerce, 'default_color', []);
    $defaultMedia = collect((array) data_get($defaultColor, 'media', []))->take(6);
    $heroMedia = (array) ($defaultMedia->first() ?: []);
    $advisor = (array) data_get($pdp, 'fit.advisor', []);
    $facts = collect((array) data_get($pdp, 'product_truth.highlights', []))->take(3);
    $shortName = trim((string) (data_get($identity, 'short_name') ?: data_get($identity, 'name')));
    $fullName = trim((string) data_get($identity, 'name'));
    $descriptor = trim((string) preg_replace(
        '/^'.preg_quote($shortName, '/').'\s*[–—\-:]?\s*/u',
        '',
        $fullName
    ));
    $description = \Illuminate\Support\Str::limit(
        (string) data_get($identity, 'description'),
        210,
        '…'
    );
@endphp

<div class="lxa-hero" data-lxa-reveal>
    <div class="lxa-hero__media">
        <div class="lxa-hero__issue" aria-hidden="true">
            <span>LIN XÉN / THE EDIT</span>
            <strong>01</strong>
        </div>

        <div class="lxpdp-gallery lxa-gallery" data-lxpdp-gallery aria-label="Hình ảnh sản phẩm">
            <div class="lxpdp-gallery__stage lxa-gallery__stage">
                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--prev lxa-gallery__nav lxa-gallery__nav--prev"
                    data-lxpdp-gallery-prev
                    aria-label="Ảnh trước"
                >‹</button>

                <figure class="lxpdp-gallery__figure lxa-gallery__figure">
                    <img
                        data-lxpdp-main-image
                        src="{{ data_get($heroMedia, 'url', data_get($pdp, 'media.cover_url')) }}"
                        alt="{{ $fullName }} - {{ data_get($defaultColor, 'label') }}"
                        width="1200"
                        height="1500"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <figcaption class="lxa-gallery__caption">
                        <span data-lxpdp-image-role>
                            {{ data_get($heroMedia, 'role') === 'hero' ? 'Ảnh chính' : 'Hình ảnh sản phẩm' }}
                        </span>
                        <span data-lxpdp-image-counter>
                            {{ $defaultMedia->isNotEmpty() ? '01 — '.str_pad((string) $defaultMedia->count(), 2, '0', STR_PAD_LEFT) : '' }}
                        </span>
                    </figcaption>
                </figure>

                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--next lxa-gallery__nav lxa-gallery__nav--next"
                    data-lxpdp-gallery-next
                    aria-label="Ảnh tiếp theo"
                >›</button>
            </div>

            <div class="lxpdp-gallery__thumbs lxa-gallery__thumbs" data-lxpdp-thumbs role="list" aria-label="Chọn ảnh sản phẩm">
                @foreach($defaultMedia as $index => $media)
                    <button
                        type="button"
                        class="lxpdp-gallery__thumb lxa-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}"
                        data-lxpdp-thumb
                        data-index="{{ $index }}"
                        aria-label="Xem ảnh {{ $index + 1 }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        <img
                            src="{{ data_get($media, 'thumb_url', data_get($media, 'url')) }}"
                            alt=""
                            width="96"
                            height="120"
                            loading="lazy"
                            decoding="async"
                        >
                    </button>
                @endforeach
            </div>

            <p class="lxpdp-gallery__notice lxa-gallery__notice" data-lxpdp-gallery-notice @if($defaultMedia->isNotEmpty()) hidden @endif>
                Màu này đang chờ bộ ảnh được duyệt. LIN XÉN không dùng ảnh của màu khác để minh họa.
            </p>
        </div>
    </div>

    <aside class="lxpdp-buy-panel lxa-buy" aria-label="Thông tin mua hàng" id="lxaPurchasePanel">
        <div class="lxa-buy__head">
            <p class="lxa-kicker">New season · The LIN XÉN edit</p>
            <h1>{{ $shortName }}</h1>
            @if($descriptor !== '')
                <p class="lxa-buy__descriptor">{{ $descriptor }}</p>
            @endif
            @if($description !== '')
                <p class="lxa-buy__deck">{{ $description }}</p>
            @endif
        </div>

        <div class="lxa-price-row">
            <div class="lxpdp__price" data-lxpdp-price>
                <strong>{{ number_format((float) data_get($commerce, 'price.min'), 0, ',', '.') }}₫</strong>
                @if(data_get($commerce, 'price.has_sale') && data_get($commerce, 'price.original_min') > data_get($commerce, 'price.min'))
                    <del>{{ number_format((float) data_get($commerce, 'price.original_min'), 0, ',', '.') }}₫</del>
                @endif
            </div>
            <p class="lxa-stock {{ data_get($commerce, 'availability.in_stock') ? 'is-in' : 'is-out' }}">
                <i aria-hidden="true"></i>
                <span>{{ data_get($commerce, 'availability.in_stock') ? 'Sẵn sàng giao' : 'Tạm hết hàng' }}</span>
            </p>
        </div>

        <section class="lxpdp-selector lxa-selector" aria-labelledby="lxaColorTitle">
            <div class="lxa-selector__head">
                <h2 id="lxaColorTitle">Màu sắc</h2>
                <span data-lxpdp-color-label>{{ data_get($defaultColor, 'label', 'Chọn màu') }}</span>
            </div>

            <div class="lxa-color-list" role="list">
                @foreach($colors as $color)
                    @php
                        $cover = data_get($color, 'media.0.thumb_url') ?: data_get($color, 'cover_url');
                        $active = (string) data_get($color, 'id') === (string) data_get($defaultColor, 'id');
                    @endphp
                    <button
                        type="button"
                        class="lxpdp-color-card lxa-color {{ $active ? 'is-active' : '' }}"
                        data-lxpdp-color
                        data-color-id="{{ data_get($color, 'id') }}"
                        data-color-code="{{ data_get($color, 'code') }}"
                        data-color-sellable="{{ data_get($color, 'sellable') ? '1' : '0' }}"
                        aria-pressed="{{ $active ? 'true' : 'false' }}"
                        aria-label="{{ data_get($color, 'label') }}{{ data_get($color, 'sellable') ? '' : ', tạm hết hàng' }}"
                    >
                        <span class="lxa-color__visual">
                            @if($cover)
                                <img src="{{ $cover }}" alt="" width="72" height="90" loading="lazy" decoding="async">
                            @else
                                <i style="--lxa-swatch:{{ data_get($color, 'hex') ?: '#d9d1cb' }}"></i>
                            @endif
                        </span>
                        <span class="lxa-color__copy">
                            <strong>{{ data_get($color, 'label') }}</strong>
                            <small>{{ data_get($color, 'sellable') ? 'Còn '.(int) data_get($color, 'available') : 'Tạm hết' }}</small>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="lxpdp-selector lxa-selector lxa-selector--sizes" aria-labelledby="lxaSizeTitle">
            <div class="lxa-selector__head">
                <h2 id="lxaSizeTitle">Kích thước</h2>
                <button
                    type="button"
                    class="lxpdp-size-advisor-link lxa-fit-link"
                    data-lxpdp-size-advisor-open
                    @if(!data_get($advisor, 'enabled')) disabled @endif
                >Tìm size của bạn</button>
            </div>
            <div class="lxpdp-size-list lxa-size-list" data-lxpdp-sizes role="list" aria-live="polite"></div>
            <div class="lxpdp-selection lxa-selection" data-lxpdp-selection hidden>
                <strong data-lxpdp-selected-text></strong>
                <span data-lxpdp-selected-stock></span>
            </div>
        </section>

        <form method="post" action="{{ data_get($commerce, 'cart_action') }}" class="lxpdp-cart-form lxa-cart" data-lxpdp-cart-form>
            @csrf
            <input type="hidden" name="sellable_sku_id" value="" data-lxpdp-sku-input>
            <input type="hidden" name="quantity" value="1">
            <button class="lxpdp-primary-button lxa-buy-button" type="submit" disabled data-lxpdp-buy>
                Chọn màu và kích thước
            </button>
        </form>

        <div class="lxa-assurance" aria-label="Quyền lợi mua hàng">
            <div><strong>COD</strong><span>Nhận hàng rồi thanh toán</span></div>
            <div><strong>Đổi size</strong><span>Hỗ trợ theo chính sách</span></div>
            <div><strong>Số đo riêng</strong><span>Được xác minh theo mẫu</span></div>
        </div>

        @if($facts->isNotEmpty())
            <dl class="lxa-mini-facts">
                @foreach($facts as $fact)
                    <div><dt>{{ data_get($fact, 'label') }}</dt><dd>{{ data_get($fact, 'value') }}</dd></div>
                @endforeach
            </dl>
        @endif
    </aside>
</div>

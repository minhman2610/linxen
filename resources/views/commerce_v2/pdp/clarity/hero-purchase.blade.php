@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $allColors = collect((array) data_get($commerce, 'colors', []))->values();
    $availableColors = $allColors
        ->filter(fn ($color) => (bool) data_get($color, 'sellable') && (float) data_get($color, 'available', 0) > 0)
        ->values();
    $defaultColor = (array) data_get($commerce, 'default_color', []);
    if (! data_get($defaultColor, 'sellable') || (float) data_get($defaultColor, 'available', 0) <= 0) {
        $defaultColor = (array) ($availableColors->first() ?: $defaultColor);
    }
    $defaultMedia = collect((array) data_get($defaultColor, 'media', []))->take(6)->values();
    $heroMedia = (array) ($defaultMedia->first() ?: []);
    $advisor = (array) data_get($pdp, 'fit.advisor', []);
    $shortName = trim((string) (data_get($identity, 'short_name') ?: data_get($identity, 'name')));
    $fullName = trim((string) data_get($identity, 'name'));
    $descriptor = trim((string) preg_replace(
        '/^'.preg_quote($shortName, '/').'\s*[–—\-:]?\s*/u',
        '',
        $fullName
    ));
    $description = \Illuminate\Support\Str::limit(
        \Illuminate\Support\Str::squish((string) data_get($identity, 'description')),
        180,
        '…'
    );
    $requestedColor = \Illuminate\Support\Str::lower(trim((string) request('color', '')));
    $requestedUnavailable = $requestedColor !== ''
        ? $allColors->first(function ($color) use ($requestedColor) {
            $keys = collect([
                data_get($color, 'id'),
                data_get($color, 'code'),
                data_get($color, 'key'),
            ])->map(fn ($value) => \Illuminate\Support\Str::lower(trim((string) $value)));

            return $keys->contains($requestedColor)
                && (! data_get($color, 'sellable') || (float) data_get($color, 'available', 0) <= 0);
        })
        : null;
@endphp

<div class="lxc-shell lxc-hero" data-lxc-reveal>
    <div class="lxc-hero__gallery-column">
        <div class="lxc-hero__topline" aria-hidden="true">
            <span>LIN XÉN / PRODUCT FOCUS</span>
            <span>{{ now()->format('Y') }}</span>
        </div>

        <div class="lxpdp-gallery lxc-gallery" data-lxpdp-gallery aria-label="Hình ảnh sản phẩm">
            <div class="lxpdp-gallery__stage lxc-gallery__stage">
                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--prev lxc-gallery__nav lxc-gallery__nav--prev"
                    data-lxpdp-gallery-prev
                    aria-label="Ảnh trước"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>

                <figure class="lxpdp-gallery__figure lxc-gallery__figure">
                    <img
                        data-lxpdp-main-image
                        src="{{ data_get($heroMedia, 'url', data_get($pdp, 'media.cover_url')) }}"
                        alt="{{ $fullName }} - {{ data_get($defaultColor, 'label') }}"
                        width="1120"
                        height="1400"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <figcaption class="lxc-gallery__meta">
                        <span data-lxpdp-image-role>{{ data_get($heroMedia, 'role') === 'hero' ? 'Tổng thể' : 'Hình ảnh sản phẩm' }}</span>
                        <span data-lxpdp-image-counter>{{ $defaultMedia->isNotEmpty() ? '01 / '.str_pad((string) $defaultMedia->count(), 2, '0', STR_PAD_LEFT) : '' }}</span>
                    </figcaption>
                </figure>

                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--next lxc-gallery__nav lxc-gallery__nav--next"
                    data-lxpdp-gallery-next
                    aria-label="Ảnh tiếp theo"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>

            <div class="lxpdp-gallery__thumbs lxc-gallery__thumbs" data-lxpdp-thumbs role="list" aria-label="Chọn ảnh sản phẩm">
                @foreach($defaultMedia as $index => $media)
                    <button
                        type="button"
                        class="lxpdp-gallery__thumb lxc-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}"
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

            <p class="lxpdp-gallery__notice lxc-gallery__notice" data-lxpdp-gallery-notice @if($defaultMedia->isNotEmpty()) hidden @endif>
                Màu này đang chờ bộ ảnh được duyệt. LIN XÉN không dùng ảnh của màu khác để minh họa.
            </p>
        </div>
    </div>

    <aside class="lxpdp-buy-panel lxc-buy" aria-label="Thông tin mua hàng" data-lxc-purchase>
        <div class="lxc-buy__head">
            <p class="lxc-kicker">Thiết kế mới · Ready to wear</p>
            <h1>{{ $shortName }}</h1>
            @if($descriptor !== '')
                <p class="lxc-buy__descriptor">{{ $descriptor }}</p>
            @endif
            @if($description !== '')
                <p class="lxc-buy__description">{{ $description }}</p>
            @endif
        </div>

        <div class="lxc-price-line">
            <div class="lxpdp__price lxc-price" data-lxpdp-price>
                <strong>{{ number_format((float) data_get($commerce, 'price.min'), 0, ',', '.') }}₫</strong>
                @if(data_get($commerce, 'price.has_sale') && data_get($commerce, 'price.original_min') > data_get($commerce, 'price.min'))
                    <del>{{ number_format((float) data_get($commerce, 'price.original_min'), 0, ',', '.') }}₫</del>
                @endif
            </div>
            <span class="lxc-stock {{ data_get($commerce, 'availability.in_stock') ? 'is-in' : 'is-out' }}">
                <i aria-hidden="true"></i>
                {{ data_get($commerce, 'availability.in_stock') ? 'Sẵn sàng giao' : 'Tạm hết hàng' }}
            </span>
        </div>

        <section class="lxpdp-selector lxc-selector" aria-labelledby="lxsColorTitle">
            <div class="lxc-selector__head">
                <h2 id="lxsColorTitle">Màu sắc</h2>
                <span data-lxpdp-color-label>{{ data_get($defaultColor, 'label', 'Chọn màu') }}</span>
            </div>

            @if($availableColors->isNotEmpty())
                <div class="lxc-color-list" role="list">
                    @foreach($availableColors as $color)
                        @php
                            $cover = data_get($color, 'media.0.thumb_url')
                                ?: data_get($color, 'media.0.url')
                                ?: data_get($color, 'cover_url');
                            $active = (string) data_get($color, 'id') === (string) data_get($defaultColor, 'id');
                        @endphp
                        <button
                            type="button"
                            class="lxpdp-color-card lxc-color {{ $active ? 'is-active' : '' }}"
                            data-lxpdp-color
                            data-color-id="{{ data_get($color, 'id') }}"
                            data-color-code="{{ data_get($color, 'code') }}"
                            data-color-sellable="1"
                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                            aria-label="Màu {{ data_get($color, 'label') }}"
                        >
                            <span class="lxc-color__visual" style="--lxc-swatch:{{ data_get($color, 'hex') ?: '#dfe3ef' }}">
                                @if($cover)
                                    <img src="{{ $cover }}" alt="" width="72" height="90" loading="lazy" decoding="async">
                                @else
                                    <i style="--lxc-swatch:{{ data_get($color, 'hex') ?: '#dfe3ef' }}"></i>
                                @endif
                            </span>
                            <strong>{{ data_get($color, 'label') }}</strong>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="lxc-all-soldout" role="status">
                    Tất cả màu hiện đang tạm hết hàng.
                </div>
            @endif

            @if($requestedUnavailable)
                <div class="lxc-color-unavailable" role="status">
                    <span style="--lxc-swatch:{{ data_get($requestedUnavailable, 'hex') ?: '#cbd5e1' }}"></span>
                    <div>
                        <strong>{{ data_get($requestedUnavailable, 'label') }}</strong>
                        <small>Màu này đang tạm hết hàng</small>
                    </div>
                </div>
            @endif
        </section>

        <section class="lxpdp-selector lxc-selector lxc-selector--size" aria-labelledby="lxsSizeTitle">
            <div class="lxc-selector__head">
                <h2 id="lxsSizeTitle">Kích thước</h2>
                <button
                    type="button"
                    class="lxpdp-size-advisor-link lxc-size-guide"
                    data-lxpdp-size-advisor-open
                    @if(!data_get($advisor, 'enabled')) disabled @endif
                >
                    Tìm size của bạn
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
                </button>
            </div>
            <div class="lxpdp-size-list lxc-size-list" data-lxpdp-sizes role="list" aria-live="polite"></div>
            <div class="lxpdp-selection lxc-selection" data-lxpdp-selection hidden>
                <strong data-lxpdp-selected-text></strong>
                <span data-lxpdp-selected-stock></span>
            </div>
        </section>

        <form method="post" action="{{ data_get($commerce, 'cart_action') }}" class="lxpdp-cart-form lxc-cart" data-lxpdp-cart-form>
            @csrf
            <input type="hidden" name="sellable_sku_id" value="" data-lxpdp-sku-input>
            <input type="hidden" name="quantity" value="1">
            <button class="lxpdp-primary-button lxc-buy-button" type="submit" disabled data-lxpdp-buy>
                Chọn màu và kích thước
            </button>
        </form>
    </aside>
</div>

<nav class="lxc-dock" data-lxc-dock aria-label="Thanh mua hàng nhanh">
    <div class="lxc-dock__inner">
        <a class="lxc-dock__icon" href="{{ route('commerce.v2.home') }}" aria-label="Trang chủ">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-7 9 7v9h-6v-6H9v6H3z"/></svg>
        </a>
        <a class="lxc-dock__icon" href="{{ route('commerce.v2.search') }}" aria-label="Tìm kiếm">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
        </a>
        <a class="lxc-dock__icon" href="{{ route('commerce.v2.cart.index') }}" aria-label="Giỏ hàng">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l-1 13H6L5 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
        </a>

        <div class="lxc-dock__summary" aria-live="polite">
            <strong data-lxc-dock-price>{{ number_format((float) data_get($commerce, 'price.min'), 0, ',', '.') }}₫</strong>
            <span data-lxc-dock-selection>Chọn màu &amp; size</span>
        </div>

        <button type="button" class="lxc-dock__cta" data-lxc-dock-submit disabled>
            <span data-lxc-dock-label>Chọn size</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
        </button>
    </div>
</nav>

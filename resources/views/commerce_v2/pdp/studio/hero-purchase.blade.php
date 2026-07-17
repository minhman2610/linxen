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

<div class="lxs-shell lxs-hero" data-lxs-reveal>
    <div class="lxs-hero__gallery-column">
        <div class="lxs-hero__topline" aria-hidden="true">
            <span>LIN XÉN / STUDIO SIGNAL</span>
            <span>{{ now()->format('Y') }}</span>
        </div>

        <div class="lxpdp-gallery lxs-gallery" data-lxpdp-gallery aria-label="Hình ảnh sản phẩm">
            <div class="lxpdp-gallery__stage lxs-gallery__stage">
                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--prev lxs-gallery__nav lxs-gallery__nav--prev"
                    data-lxpdp-gallery-prev
                    aria-label="Ảnh trước"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>

                <figure class="lxpdp-gallery__figure lxs-gallery__figure">
                    <img
                        data-lxpdp-main-image
                        src="{{ data_get($heroMedia, 'url', data_get($pdp, 'media.cover_url')) }}"
                        alt="{{ $fullName }} - {{ data_get($defaultColor, 'label') }}"
                        width="1120"
                        height="1400"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <figcaption class="lxs-gallery__meta">
                        <span data-lxpdp-image-role>{{ data_get($heroMedia, 'role') === 'hero' ? 'Tổng thể' : 'Hình ảnh sản phẩm' }}</span>
                        <span data-lxpdp-image-counter>{{ $defaultMedia->isNotEmpty() ? '01 / '.str_pad((string) $defaultMedia->count(), 2, '0', STR_PAD_LEFT) : '' }}</span>
                    </figcaption>
                </figure>

                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--next lxs-gallery__nav lxs-gallery__nav--next"
                    data-lxpdp-gallery-next
                    aria-label="Ảnh tiếp theo"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>

            <div class="lxpdp-gallery__thumbs lxs-gallery__thumbs" data-lxpdp-thumbs role="list" aria-label="Chọn ảnh sản phẩm">
                @foreach($defaultMedia as $index => $media)
                    <button
                        type="button"
                        class="lxpdp-gallery__thumb lxs-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}"
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

            <p class="lxpdp-gallery__notice lxs-gallery__notice" data-lxpdp-gallery-notice @if($defaultMedia->isNotEmpty()) hidden @endif>
                Màu này đang chờ bộ ảnh được duyệt. LIN XÉN không dùng ảnh của màu khác để minh họa.
            </p>
        </div>
    </div>

    <aside class="lxpdp-buy-panel lxs-buy" aria-label="Thông tin mua hàng" data-lxs-purchase>
        <div class="lxs-buy__head">
            <p class="lxs-kicker">Thiết kế mới · Ready to wear</p>
            <h1>{{ $shortName }}</h1>
            @if($descriptor !== '')
                <p class="lxs-buy__descriptor">{{ $descriptor }}</p>
            @endif
            @if($description !== '')
                <p class="lxs-buy__description">{{ $description }}</p>
            @endif
        </div>

        <div class="lxs-price-line">
            <div class="lxpdp__price lxs-price" data-lxpdp-price>
                <strong>{{ number_format((float) data_get($commerce, 'price.min'), 0, ',', '.') }}₫</strong>
                @if(data_get($commerce, 'price.has_sale') && data_get($commerce, 'price.original_min') > data_get($commerce, 'price.min'))
                    <del>{{ number_format((float) data_get($commerce, 'price.original_min'), 0, ',', '.') }}₫</del>
                @endif
            </div>
            <span class="lxs-stock {{ data_get($commerce, 'availability.in_stock') ? 'is-in' : 'is-out' }}">
                <i aria-hidden="true"></i>
                {{ data_get($commerce, 'availability.in_stock') ? 'Sẵn sàng giao' : 'Tạm hết hàng' }}
            </span>
        </div>

        <section class="lxpdp-selector lxs-selector" aria-labelledby="lxsColorTitle">
            <div class="lxs-selector__head">
                <h2 id="lxsColorTitle">Màu sắc</h2>
                <span data-lxpdp-color-label>{{ data_get($defaultColor, 'label', 'Chọn màu') }}</span>
            </div>

            @if($availableColors->isNotEmpty())
                <div class="lxs-color-list" role="list">
                    @foreach($availableColors as $color)
                        @php
                            $cover = data_get($color, 'media.0.thumb_url')
                                ?: data_get($color, 'media.0.url')
                                ?: data_get($color, 'cover_url');
                            $active = (string) data_get($color, 'id') === (string) data_get($defaultColor, 'id');
                        @endphp
                        <button
                            type="button"
                            class="lxpdp-color-card lxs-color {{ $active ? 'is-active' : '' }}"
                            data-lxpdp-color
                            data-color-id="{{ data_get($color, 'id') }}"
                            data-color-code="{{ data_get($color, 'code') }}"
                            data-color-sellable="1"
                            aria-pressed="{{ $active ? 'true' : 'false' }}"
                            aria-label="Màu {{ data_get($color, 'label') }}"
                        >
                            <span class="lxs-color__visual" style="--lxs-swatch:{{ data_get($color, 'hex') ?: '#dfe3ef' }}">
                                @if($cover)
                                    <img src="{{ $cover }}" alt="" width="72" height="90" loading="lazy" decoding="async">
                                @else
                                    <i style="--lxs-swatch:{{ data_get($color, 'hex') ?: '#dfe3ef' }}"></i>
                                @endif
                            </span>
                            <strong>{{ data_get($color, 'label') }}</strong>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="lxs-all-soldout" role="status">
                    Tất cả màu hiện đang tạm hết hàng.
                </div>
            @endif

            @if($requestedUnavailable)
                <div class="lxs-color-unavailable" role="status">
                    <span style="--lxs-swatch:{{ data_get($requestedUnavailable, 'hex') ?: '#cbd5e1' }}"></span>
                    <div>
                        <strong>{{ data_get($requestedUnavailable, 'label') }}</strong>
                        <small>Màu này đang tạm hết hàng</small>
                    </div>
                </div>
            @endif
        </section>

        <section class="lxpdp-selector lxs-selector lxs-selector--size" aria-labelledby="lxsSizeTitle">
            <div class="lxs-selector__head">
                <h2 id="lxsSizeTitle">Kích thước</h2>
                <button
                    type="button"
                    class="lxpdp-size-advisor-link lxs-size-guide"
                    data-lxpdp-size-advisor-open
                    @if(!data_get($advisor, 'enabled')) disabled @endif
                >
                    Tìm size của bạn
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
                </button>
            </div>
            <div class="lxpdp-size-list lxs-size-list" data-lxpdp-sizes role="list" aria-live="polite"></div>
            <div class="lxpdp-selection lxs-selection" data-lxpdp-selection hidden>
                <strong data-lxpdp-selected-text></strong>
                <span data-lxpdp-selected-stock></span>
            </div>
        </section>

        <form method="post" action="{{ data_get($commerce, 'cart_action') }}" class="lxpdp-cart-form lxs-cart" data-lxpdp-cart-form>
            @csrf
            <input type="hidden" name="sellable_sku_id" value="" data-lxpdp-sku-input>
            <input type="hidden" name="quantity" value="1">
            <button class="lxpdp-primary-button lxs-buy-button" type="submit" disabled data-lxpdp-buy>
                Chọn màu và kích thước
            </button>
        </form>

        <div class="lxs-buy-confidence" aria-label="Quyền lợi mua hàng">
            <span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v10H4zM7 17v2M17 17v2M8 12h8"/></svg>
                COD khi nhận hàng
            </span>
            <span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h10v10H7zM4 12a8 8 0 0 1 13.7-5.7M20 12a8 8 0 0 1-13.7 5.7"/></svg>
                Hỗ trợ đổi size
            </span>
            <span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 18h16M6 18V9l6-4 6 4v9M9 12h6"/></svg>
                Giao hàng toàn quốc
            </span>
        </div>
    </aside>
</div>

<nav class="lxs-mobile-dock" data-lxs-mobile-dock aria-label="Thanh công cụ mua hàng">
    <a href="{{ route('commerce.v2.home') }}" aria-label="Trang chủ">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-7 9 7v9h-6v-6H9v6H3z"/></svg>
        <span>Home</span>
    </a>
    <a href="{{ route('commerce.v2.search') }}" aria-label="Tìm kiếm">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
        <span>Tìm</span>
    </a>
    <a href="{{ route('commerce.v2.account.index') }}" aria-label="Tài khoản">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c.8-5 3.5-7 8-7s7.2 2 8 7"/></svg>
        <span>Tôi</span>
    </a>
    <a href="{{ route('commerce.v2.cart.index') }}" aria-label="Giỏ hàng">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l-1 13H6L5 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
        <span>Giỏ</span>
    </a>
    <button type="button" class="lxs-mobile-dock__cta" data-lxs-dock-submit disabled>
        <span data-lxs-dock-label>Chọn màu &amp; size</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
    </button>
</nav>

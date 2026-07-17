@extends('commerce_v2.layouts.app')

@section('og_type', 'product')

@push('head')
    <link
        rel="stylesheet"
        href="{{ asset('commerce-v2/pdp-sales-experience.css') }}?v=1"
    >
@endpush

@section('content')
@php
    $colors = collect($product['colors'] ?? []);
    $defaultColor = $colors->firstWhere(
        'id',
        $product['default_color_id'] ?? null
    ) ?? $colors->first();
    $defaultMedia = collect(
        (array) data_get($defaultColor, 'media', [])
    );
    $heroMedia = $defaultMedia->first();
    $structured = (array) ($product['structured_specs'] ?? []);
    $sizeAdvisor = (array) ($product['size_advisor'] ?? []);
    $sizeChart = (array) data_get(
        $sizeAdvisor,
        'size_chart',
        []
    );
@endphp

<article
    class="lxpdp"
    data-lxpdp
    data-size-advice-url="{{ data_get($sizeAdvisor, 'endpoint_url') }}"
>
    <nav class="lxpdp__breadcrumb" aria-label="Đường dẫn">
        <a href="{{ route('commerce.v2.home') }}">Trang chủ</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('commerce.v2.shop') }}">Sản phẩm</a>
        <span aria-hidden="true">/</span>
        <span>{{ $product['short_name'] ?: $product['name'] }}</span>
    </nav>

    <section class="lxpdp__hero">
        <div
            class="lxpdp-gallery"
            data-lxpdp-gallery
            aria-label="Hình ảnh sản phẩm"
        >
            <div class="lxpdp-gallery__stage">
                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--prev"
                    data-lxpdp-gallery-prev
                    aria-label="Ảnh trước"
                >‹</button>

                <figure class="lxpdp-gallery__figure">
                    <img
                        data-lxpdp-main-image
                        src="{{ data_get($heroMedia, 'url', $product['cover_url']) }}"
                        alt="{{ $product['name'] }} - {{ data_get($defaultColor, 'label') }}"
                        width="960"
                        height="1200"
                        fetchpriority="high"
                        decoding="async"
                    >
                    <figcaption class="lxpdp-gallery__caption">
                        <span data-lxpdp-image-role>
                            {{ data_get($heroMedia, 'role') === 'hero' ? 'Ảnh chính' : 'Hình ảnh sản phẩm' }}
                        </span>
                        <span data-lxpdp-image-counter>
                            {{ $defaultMedia->isNotEmpty() ? '1 / '.$defaultMedia->count() : '' }}
                        </span>
                    </figcaption>
                </figure>

                <button
                    type="button"
                    class="lxpdp-gallery__nav lxpdp-gallery__nav--next"
                    data-lxpdp-gallery-next
                    aria-label="Ảnh tiếp theo"
                >›</button>
            </div>

            <div
                class="lxpdp-gallery__thumbs"
                data-lxpdp-thumbs
                role="list"
                aria-label="Chọn ảnh sản phẩm"
            >
                @foreach($defaultMedia as $index => $media)
                    <button
                        type="button"
                        class="lxpdp-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}"
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

            <p
                class="lxpdp-gallery__notice"
                data-lxpdp-gallery-notice
                @if($defaultMedia->isNotEmpty()) hidden @endif
            >
                Màu này chưa có bộ ảnh đã duyệt. LIN XÉN không dùng ảnh của màu khác để minh họa.
            </p>
        </div>

        <aside class="lxpdp-buy-panel" aria-label="Thông tin mua hàng">
            <div class="lxpdp-buy-panel__top">
                <p class="lxpdp__code">{{ $product['code'] }}</p>
                <h1>{{ $product['name'] }}</h1>

                <div class="lxpdp__price" data-lxpdp-price>
                    <strong>{{ number_format($product['price_min'], 0, ',', '.') }}₫</strong>
                    @if(
                        $product['has_sale']
                        && $product['original_min'] > $product['price_min']
                    )
                        <del>{{ number_format($product['original_min'], 0, ',', '.') }}₫</del>
                    @endif
                </div>

                <p class="lxpdp__availability">
                    <span class="{{ $product['in_stock'] ? 'is-in-stock' : 'is-out-of-stock' }}">
                        {{ $product['in_stock'] ? 'Đang có hàng' : 'Tạm hết hàng' }}
                    </span>
                    @if($product['available_total'] > 0)
                        <span>· {{ (int) $product['available_total'] }} sản phẩm khả dụng</span>
                    @endif
                </p>
            </div>

            @if(!empty($product['highlights']))
                <ul class="lxpdp-highlights" aria-label="Điểm nổi bật">
                    @foreach($product['highlights'] as $highlight)
                        <li>
                            <span>{{ data_get($highlight, 'label') }}</span>
                            <strong>{{ data_get($highlight, 'value') }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif

            <section class="lxpdp-selector" aria-labelledby="lxpdpColorTitle">
                <div class="lxpdp-selector__heading">
                    <h2 id="lxpdpColorTitle">Màu sắc</h2>
                    <span data-lxpdp-color-label>{{ data_get($defaultColor, 'label', 'Chọn màu') }}</span>
                </div>

                <div class="lxpdp-color-list" role="list">
                    @foreach($colors as $color)
                        @php
                            $colorCover = data_get($color, 'media.0.thumb_url')
                                ?: data_get($color, 'cover_url');
                            $isDefault = data_get($color, 'id')
                                === data_get($defaultColor, 'id');
                        @endphp
                        <button
                            type="button"
                            class="lxpdp-color-card {{ $isDefault ? 'is-active' : '' }}"
                            data-lxpdp-color
                            data-color-id="{{ data_get($color, 'id') }}"
                            data-color-code="{{ data_get($color, 'code') }}"
                            aria-pressed="{{ $isDefault ? 'true' : 'false' }}"
                            data-color-sellable="{{ data_get($color, 'sellable') ? '1' : '0' }}"
                            aria-label="Xem màu {{ data_get($color, 'label') }}{{ data_get($color, 'sellable') ? '' : ', hiện tạm hết hàng' }}"
                        >
                            <span class="lxpdp-color-card__visual">
                                @if($colorCover)
                                    <img
                                        src="{{ $colorCover }}"
                                        alt=""
                                        width="58"
                                        height="72"
                                        loading="lazy"
                                    >
                                @else
                                    <i style="--lxpdp-swatch:{{ data_get($color, 'hex') ?: '#d9d1cb' }}"></i>
                                @endif
                            </span>
                            <span>
                                <strong>{{ data_get($color, 'label') }}</strong>
                                <small>
                                    @if(data_get($color, 'sellable'))
                                        {{ (int) data_get($color, 'available') }} còn hàng
                                    @else
                                        Tạm hết
                                    @endif
                                </small>
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="lxpdp-selector" aria-labelledby="lxpdpSizeTitle">
                <div class="lxpdp-selector__heading">
                    <h2 id="lxpdpSizeTitle">Kích thước</h2>
                    <button
                        type="button"
                        class="lxpdp-size-advisor-link"
                        data-lxpdp-size-advisor-open
                        @if(!data_get($sizeAdvisor, 'enabled')) disabled @endif
                    >
                        Tìm size phù hợp
                    </button>
                </div>

                <div
                    class="lxpdp-size-list"
                    data-lxpdp-sizes
                    role="list"
                    aria-live="polite"
                ></div>

                <div class="lxpdp-selection" data-lxpdp-selection hidden>
                    <strong data-lxpdp-selected-text></strong>
                    <span data-lxpdp-selected-stock></span>
                </div>
            </section>

            <form
                method="post"
                action="{{ route('commerce.v2.cart.items.store') }}"
                class="lxpdp-cart-form"
                data-lxpdp-cart-form
            >
                @csrf
                <input
                    type="hidden"
                    name="sellable_sku_id"
                    value=""
                    data-lxpdp-sku-input
                >
                <input type="hidden" name="quantity" value="1">

                <button
                    class="lxpdp-primary-button"
                    type="submit"
                    disabled
                    data-lxpdp-buy
                >
                    Chọn màu và kích thước
                </button>
            </form>

            <div class="lxpdp-trust">
                <div>
                    <strong>Thanh toán COD</strong>
                    <span>Nhận hàng rồi thanh toán</span>
                </div>
                <div>
                    <strong>Tồn kho được kiểm tra lại</strong>
                    <span>Giá và số lượng được ERP xác nhận khi thêm giỏ</span>
                </div>
                <div>
                    <strong>Hỗ trợ chọn size</strong>
                    <span>Gợi ý minh bạch nguồn và độ tin cậy</span>
                </div>
            </div>
        </aside>
    </section>

    <section class="lxpdp-content">
        <div class="lxpdp-content__intro">
            <p class="lxpdp-kicker">Chi tiết sản phẩm</p>
            <h2>Hiểu rõ thiết kế trước khi chọn</h2>
            @if(!empty($product['description']))
                <p>{{ $product['description'] }}</p>
            @endif
        </div>

        <div class="lxpdp-accordion-grid">
            @foreach([
                'design' => 'Thiết kế',
                'materials' => 'Chất liệu',
                'fit' => 'Form và độ vừa',
                'style' => 'Phong cách',
                'care' => 'Bảo quản',
            ] as $sectionKey => $sectionLabel)
                @php
                    $section = (array) data_get(
                        $structured,
                        $sectionKey,
                        []
                    );
                    $items = collect((array) data_get($section, 'items', []));
                @endphp
                <details
                    class="lxpdp-detail-card"
                    @if($loop->first) open @endif
                >
                    <summary>
                        <span>{{ $sectionLabel }}</span>
                        <small>
                            {{ data_get($section, 'status') === 'available' ? 'Đã xác minh' : 'Thông tin giới hạn' }}
                        </small>
                    </summary>
                    <div class="lxpdp-detail-card__body">
                        @if($items->isNotEmpty())
                            <dl>
                                @foreach($items as $item)
                                    <div>
                                        <dt>{{ data_get($item, 'label') }}</dt>
                                        <dd>{{ data_get($item, 'value') }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @if(data_get($section, 'message'))
                            <p>{{ data_get($section, 'message') }}</p>
                        @elseif($items->isEmpty())
                            <p>Nguồn ERP hiện chưa có thông tin được xác minh cho mục này.</p>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>

        <section class="lxpdp-size-chart">
            <div>
                <p class="lxpdp-kicker">Chọn size có căn cứ</p>
                <h2>Bảng kích thước và tư vấn</h2>
                <p>
                    {{ data_get($sizeChart, 'message', data_get($sizeAdvisor, 'disclaimer')) }}
                </p>
                <button
                    type="button"
                    class="lxpdp-secondary-button"
                    data-lxpdp-size-advisor-open
                    @if(!data_get($sizeAdvisor, 'enabled')) disabled @endif
                >
                    Nhập số đo để kiểm tra
                </button>
            </div>

            @if(data_get($sizeChart, 'image_url'))
                <a
                    href="{{ data_get($sizeChart, 'image_url') }}"
                    target="_blank"
                    rel="noopener"
                    class="lxpdp-size-chart__image"
                >
                    <img
                        src="{{ data_get($sizeChart, 'thumb_url') ?: data_get($sizeChart, 'image_url') }}"
                        alt="Bảng kích thước {{ $product['name'] }}"
                        loading="lazy"
                    >
                    <span>Mở bảng kích thước</span>
                </a>
            @else
                <div class="lxpdp-size-chart__empty">
                    Bảng số đo thành phẩm riêng cho mẫu này đang được cập nhật.
                </div>
            @endif
        </section>
    </section>

    <div class="lxpdp-mobile-buy" data-lxpdp-mobile-buy>
        <div>
            <strong>{{ number_format($product['price_min'], 0, ',', '.') }}₫</strong>
            <span data-lxpdp-mobile-selection>Chọn màu và size</span>
        </div>
        <button type="button" data-lxpdp-mobile-submit disabled>
            Thêm vào giỏ
        </button>
    </div>

    <dialog class="lxpdp-advisor" data-lxpdp-size-advisor>
        <form method="dialog" class="lxpdp-advisor__close-form">
            <button type="submit" aria-label="Đóng tư vấn size">×</button>
        </form>

        <div class="lxpdp-advisor__content">
            <p class="lxpdp-kicker">Tư vấn kích thước</p>
            <h2>Nhập số đo của bạn</h2>
            <p>
                Chiều cao và cân nặng giúp tham khảo. Để đưa gợi ý, hệ thống cần đủ vòng ngực, eo và hông.
            </p>

            <form data-lxpdp-size-form>
                <div class="lxpdp-advisor__grid">
                    <label>
                        <span>Chiều cao</span>
                        <input type="number" name="height_cm" min="130" max="200" inputmode="decimal">
                        <small>cm</small>
                    </label>
                    <label>
                        <span>Cân nặng</span>
                        <input type="number" name="weight_kg" min="30" max="150" inputmode="decimal">
                        <small>kg</small>
                    </label>
                    <label>
                        <span>Vòng ngực</span>
                        <input type="number" name="bust_cm" min="45" max="160" inputmode="decimal">
                        <small>cm</small>
                    </label>
                    <label>
                        <span>Vòng eo</span>
                        <input type="number" name="waist_cm" min="45" max="160" inputmode="decimal">
                        <small>cm</small>
                    </label>
                    <label>
                        <span>Vòng hông</span>
                        <input type="number" name="hip_cm" min="45" max="180" inputmode="decimal">
                        <small>cm</small>
                    </label>
                    <label>
                        <span>Cách mặc mong muốn</span>
                        <select name="fit_preference">
                            <option value="fitted">Ôm vừa</option>
                            <option value="regular" selected>Vừa vặn</option>
                            <option value="relaxed">Thoải mái</option>
                        </select>
                    </label>
                </div>

                <div class="lxpdp-advisor__actions">
                    <button type="submit" class="lxpdp-primary-button">
                        Kiểm tra size
                    </button>
                    <button type="button" class="lxpdp-text-button" data-lxpdp-size-clear>
                        Xóa số đo đã lưu
                    </button>
                </div>
            </form>

            <div
                class="lxpdp-advisor__result"
                data-lxpdp-size-result
                aria-live="polite"
                hidden
            ></div>

            <p class="lxpdp-advisor__disclaimer">
                {{ data_get($sizeAdvisor, 'disclaimer') }}
            </p>
        </div>
    </dialog>
</article>

<script
    type="application/json"
    id="lxv2ProductData"
>{!! $productPayloadJson !!}</script>
@endsection

@push('scripts')
    <script
        src="{{ asset('commerce-v2/pdp-sales-experience.js') }}?v=1"
        defer
    ></script>
@endpush

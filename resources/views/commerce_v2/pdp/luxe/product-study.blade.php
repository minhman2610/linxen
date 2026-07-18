@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $studies = collect((array) data_get(
        $pdp,
        'media.product_study_by_color',
        []
    ))->values();
    $defaultColorId = (string) data_get(
        $commerce,
        'default_color_id',
        data_get($commerce, 'default_color.id')
    );
    $defaultStudy = (array) (
        $studies->firstWhere('color_id', $defaultColorId)
        ?: $studies->first(fn ($study) => ! empty(data_get($study, 'items')))
        ?: $studies->first()
        ?: []
    );
    $defaultItems = collect(
        (array) data_get($defaultStudy, 'items', [])
    )->values();
    $jsonFlags = JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES;
@endphp

<div
    class="lxl-study"
    data-lxl-product-study
>
    <div class="lxl-study__shell">
        <header class="lxl-study__header">
            <div>
                <p class="lxl-study__eyebrow">Chi tiết sản phẩm</p>
                <h2>Nhìn rõ sản phẩm trước khi chọn</h2>
            </div>
            <div class="lxl-study__intro">
                <span data-lxl-study-color>
                    {{ data_get($defaultStudy, 'color_label', 'Màu đang chọn') }}
                </span>
                <p>
                    Các ảnh dưới đây thuộc bộ ảnh rõ sản phẩm đã được duyệt và chỉ hiển thị đúng màu đang xem.
                </p>
            </div>
        </header>

        <nav
            class="lxl-study__nav"
            data-lxl-study-nav
            aria-label="Các góc ảnh sản phẩm"
            @if($defaultItems->isEmpty()) hidden @endif
        >
            @foreach($defaultItems as $index => $item)
                <button
                    type="button"
                    data-lxl-study-jump="{{ $index }}"
                    aria-label="Đi tới {{ data_get($item, 'angle_label') }}"
                >
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    {{ data_get($item, 'angle_label') }}
                </button>
            @endforeach
        </nav>

        <div
            class="lxl-study__list"
            data-lxl-study-list
            @if($defaultItems->isEmpty()) hidden @endif
        >
            @foreach($defaultItems as $index => $item)
                @php
                    $hero = (array) data_get($item, 'hero', []);
                    $alternates = collect(
                        (array) data_get($item, 'alternates', [])
                    )->take(3);
                @endphp
                <article
                    class="lxl-study-card lxl-study-card--{{ ($index % 4) + 1 }}"
                    data-lxl-study-item="{{ $index }}"
                >
                    <figure class="lxl-study-card__media">
                        <img
                            src="{{ data_get($hero, 'url') }}"
                            alt="{{ data_get($identity, 'name') }} — {{ data_get($defaultStudy, 'color_label') }} — {{ data_get($item, 'angle_label') }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            decoding="async"
                        >
                        <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </figure>

                    <div class="lxl-study-card__copy">
                        <small>Góc nhìn sản phẩm</small>
                        <h3>{{ data_get($item, 'angle_label') }}</h3>
                        <p>{{ data_get($item, 'angle_description') }}</p>

                        @if($alternates->isNotEmpty())
                            <div class="lxl-study-card__alternates" aria-label="Ảnh bổ sung cùng góc">
                                @foreach($alternates as $alternate)
                                    <a
                                        href="{{ data_get($alternate, 'url') }}"
                                        target="_blank"
                                        rel="noopener"
                                        aria-label="Mở ảnh bổ sung {{ data_get($item, 'angle_label') }}"
                                    >
                                        <img
                                            src="{{ data_get(
                                                $alternate,
                                                'thumb_url',
                                                data_get($alternate, 'url')
                                            ) }}"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div
            class="lxl-study__empty"
            data-lxl-study-empty
            role="status"
            @if($defaultItems->isNotEmpty()) hidden @endif
        >
            <svg viewBox="0 0 48 48" aria-hidden="true">
                <path d="M8 11h32v26H8z"/>
                <circle cx="18" cy="21" r="4"/>
                <path d="m12 33 8-8 6 5 5-4 5 7"/>
            </svg>
            <div>
                <strong>Đang cập nhật ảnh chi tiết đúng màu</strong>
                <p>
                    Màu này chưa có đủ ảnh rõ sản phẩm đã duyệt. LIN XÉN không dùng ảnh của màu khác để thay thế.
                </p>
            </div>
        </div>
    </div>

    <script type="application/json" data-lxl-study-data>{!! json_encode($studies->all(), $jsonFlags) !!}</script>
</div>


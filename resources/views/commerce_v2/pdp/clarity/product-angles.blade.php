@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $defaultColor = (array) data_get($commerce, 'default_color', []);
    $clarityItems = collect((array) data_get($defaultColor, 'clarity_media', []))
        ->filter(fn ($item) => trim((string) data_get($item, 'url')) !== '')
        ->take(8)
        ->values();

    if ($clarityItems->isEmpty()) {
        $clarityItems = collect((array) data_get($defaultColor, 'media', []))
            ->filter(function ($item) {
                $category = \Illuminate\Support\Str::upper(
                    (string) data_get($item, 'category_code')
                );

                return \Illuminate\Support\Str::contains(
                    $category,
                    'PRODUCT_CLARITY'
                ) && trim((string) data_get($item, 'url')) !== '';
            })
            ->take(8)
            ->values();
    }

    $angleLabel = static fn (array $item): string => \Illuminate\Support\Str::squish(
        (string) (
            data_get($item, 'angle_label')
            ?: data_get($item, 'shot_angle_label')
            ?: 'Ảnh đã duyệt'
        )
    );
@endphp

<div
    class="lxc-angles"
    data-lxc-clarity-section
    data-lxc-reveal
>
    <div class="lxc-shell">
        <header class="lxc-angles__header">
            <div>
                <p class="lxc-kicker">Chi tiết sản phẩm</p>
                <h2>Xem rõ từng góc của {{ data_get($identity, 'short_name') ?: data_get($identity, 'name') }}</h2>
            </div>
            <div class="lxc-angles__intro">
                <span data-lxc-clarity-color>{{ data_get($defaultColor, 'label', 'Màu đang chọn') }}</span>
                <p>Bộ ảnh rõ sản phẩm đã được duyệt, hiển thị đúng màu bạn đang xem và không mượn ảnh từ màu khác.</p>
            </div>
        </header>

        <nav
            class="lxc-angle-nav"
            data-lxc-angle-nav
            aria-label="Các góc ảnh sản phẩm"
            @if($clarityItems->isEmpty()) hidden @endif
        >
            @foreach($clarityItems as $index => $item)
                @php $label = $angleLabel((array) $item); @endphp
                <button
                    type="button"
                    data-lxc-angle-jump="{{ $index }}"
                    aria-label="Đi tới ảnh {{ $label }}"
                >
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div
            class="lxc-angle-grid"
            data-lxc-clarity-grid
            @if($clarityItems->isEmpty()) hidden @endif
        >
            @foreach($clarityItems as $index => $item)
                @php
                    $label = $angleLabel((array) $item);
                @endphp
                <figure
                    class="lxc-angle-card lxc-angle-card--{{ min($index + 1, 8) }}"
                    data-lxc-clarity-item="{{ $index }}"
                >
                    <div class="lxc-angle-card__media">
                        <img
                            src="{{ data_get($item, 'url') }}"
                            alt="{{ data_get($identity, 'name') }} — {{ data_get($defaultColor, 'label') }} — {{ $label }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            decoding="async"
                        >
                        <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <figcaption>
                        <small>Góc nhìn</small>
                        <h3>{{ $label }}</h3>
                        <p>Góc ảnh đã được ERP duyệt cho màu bạn đang xem.</p>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        <div
            class="lxc-angles__empty"
            data-lxc-clarity-empty
            role="status"
            @if($clarityItems->isNotEmpty()) hidden @endif
        >
            <span aria-hidden="true">
                <svg viewBox="0 0 48 48"><path d="M8 12h32v24H8z"/><circle cx="18" cy="21" r="4"/><path d="m12 32 8-7 6 5 5-4 5 6"/></svg>
            </span>
            <div>
                <strong>Đang cập nhật bộ ảnh rõ sản phẩm</strong>
                <p>Màu này chưa có đủ ảnh góc đã duyệt. LIN XÉN không dùng ảnh của màu khác để thay thế.</p>
            </div>
        </div>
    </div>
</div>

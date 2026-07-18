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

    $angleLabel = function (array $item): string {
        $blob = \Illuminate\Support\Str::upper(trim(
            (string) data_get($item, 'shot_angle')
            .' '
            .(string) data_get($item, 'role')
        ));

        return match (true) {
            \Illuminate\Support\Str::contains($blob, ['FRONT_3Q', 'FRONT 3Q', 'FRONT THREE', '3/4 FRONT'])
                => 'Góc trước 3/4',
            \Illuminate\Support\Str::contains($blob, ['BACK_3Q', 'BACK 3Q', '3/4 BACK'])
                => 'Góc sau 3/4',
            \Illuminate\Support\Str::contains($blob, ['LEFT_SIDE', 'SIDE_LEFT', 'LEFT PROFILE'])
                => 'Góc nghiêng trái',
            \Illuminate\Support\Str::contains($blob, ['RIGHT_SIDE', 'SIDE_RIGHT', 'RIGHT PROFILE'])
                => 'Góc nghiêng phải',
            \Illuminate\Support\Str::contains($blob, ['FULL_FRONT', 'PRODUCT_FRONT', 'FRONT'])
                => 'Mặt trước',
            \Illuminate\Support\Str::contains($blob, ['FULL_BACK', 'PRODUCT_BACK', 'BACK'])
                => 'Mặt sau',
            \Illuminate\Support\Str::contains($blob, ['SIDE', 'PROFILE'])
                => 'Góc nghiêng',
            \Illuminate\Support\Str::contains($blob, ['DETAIL', 'CLOSE', 'MACRO'])
                => 'Chi tiết sản phẩm',
            \Illuminate\Support\Str::contains($blob, ['LIFESTYLE', 'MODEL'])
                => 'Trên người mẫu',
            default => match ((string) data_get($item, 'role')) {
                'front' => 'Mặt trước',
                'back' => 'Mặt sau',
                'side' => 'Góc nghiêng',
                'detail' => 'Chi tiết sản phẩm',
                'lifestyle' => 'Trên người mẫu',
                default => 'Góc nhìn sản phẩm',
            },
        };
    };

    $angleDescription = function (string $label): string {
        return match ($label) {
            'Mặt trước' => 'Quan sát toàn bộ đường nét và tỷ lệ phía trước.',
            'Mặt sau' => 'Kiểm tra phom lưng, khóa và độ rơi của sản phẩm.',
            'Góc trước 3/4' => 'Cảm nhận độ nổi khối và cách phom ôm cơ thể.',
            'Góc sau 3/4' => 'Xem rõ chuyển tiếp từ lưng sang hông và gấu.',
            'Góc nghiêng trái', 'Góc nghiêng phải', 'Góc nghiêng'
                => 'Đánh giá độ dày, chiều sâu và đường cong của phom.',
            'Chi tiết sản phẩm' => 'Nhìn gần chất liệu và điểm nhấn thiết kế.',
            'Trên người mẫu' => 'Hình dung tỷ lệ sản phẩm khi mặc thực tế.',
            default => 'Một góc nhìn đã được chọn để làm rõ sản phẩm.',
        };
    };
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
                    $description = $angleDescription($label);
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
                        <p>{{ $description }}</p>
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

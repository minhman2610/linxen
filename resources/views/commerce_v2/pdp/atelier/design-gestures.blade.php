@php
    $items = collect((array) data_get($pdp, 'product_truth.highlights', []));
    if ($items->isEmpty()) {
        $items = collect((array) data_get($pdp, 'product_truth.design.items', []));
    }
    $items = $items->take(3)->values();
    $descriptions = [
        'silhouette' => 'Đường cắt định hình tổng thể và tạo khoảng thở tự nhiên khi mặc.',
        'form dáng' => 'Đường cắt định hình tổng thể và tạo khoảng thở tự nhiên khi mặc.',
        'neckline' => 'Một điểm nhìn gọn gàng giúp phần cổ và khuôn mặt nổi bật hơn.',
        'cổ áo' => 'Một điểm nhìn gọn gàng giúp phần cổ và khuôn mặt nổi bật hơn.',
        'sleeve_length' => 'Tỷ lệ tay áo tạo độ mềm và cân bằng cho bờ vai.',
        'tay áo' => 'Tỷ lệ tay áo tạo độ mềm và cân bằng cho bờ vai.',
        'length' => 'Chiều dài được xác minh để bạn hình dung rõ tỷ lệ trước khi chọn.',
        'độ dài' => 'Chiều dài được xác minh để bạn hình dung rõ tỷ lệ trước khi chọn.',
    ];
    $cardClasses = ['is-ink', 'is-wine', 'is-paper'];
@endphp

@if($items->isNotEmpty())
    <div class="lxa-chapter lxa-gestures" data-lxa-reveal>
        <header class="lxa-chapter__head">
            <p class="lxa-kicker">Designed in gestures</p>
            <h2>Những chi tiết tạo nên dấu ấn.</h2>
        </header>

        <div class="lxa-gesture-grid">
            @foreach($items as $index => $item)
                @php
                    $lookup = mb_strtolower((string) (data_get($item, 'key') ?: data_get($item, 'label')));
                    $description = $descriptions[$lookup] ?? 'Một chi tiết đã được xác minh trong hồ sơ sản phẩm.';
                @endphp
                <article class="lxa-gesture {{ $cardClasses[$index] ?? 'is-paper' }}">
                    <span class="lxa-gesture__index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="lxa-gesture__orbit" aria-hidden="true"></div>
                    <div>
                        <p>{{ data_get($item, 'label') }}</p>
                        <h3>{{ data_get($item, 'value') }}</h3>
                        <span>{{ $description }}</span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endif

@php
    $fitItems = collect((array) data_get($pdp, 'fit.fit_items', []));
    $chart = (array) data_get($pdp, 'fit.garment_size_chart', []);
    $lengthPoint = collect((array) data_get($chart, 'points', []))->first(fn ($point) => str_contains((string) data_get($point, 'code'), 'length'));
    $model = (array) data_get($pdp, 'fit.model_measurements', []);
@endphp
<div class="lxpdp-story-block lxpdp-fit-scale">
    <div class="lxpdp-story-copy">
        <p class="lxpdp-kicker">Form và tỷ lệ</p>
        <h2>Hình dung sản phẩm trước khi mặc</h2>
        <p>{{ data_get($pdp, 'fit.fit_message') ?: 'Thông tin bên dưới được tổng hợp từ dữ liệu thiết kế và Tech Pack đã có trong ERP.' }}</p>
    </div>
    <div class="lxpdp-fit-scale__cards">
        @foreach($fitItems as $item)
            <article><span>{{ data_get($item, 'label') }}</span><strong>{{ data_get($item, 'value') }}</strong></article>
        @endforeach
        @if($lengthPoint)
            <article class="is-accent">
                <span>{{ data_get($lengthPoint, 'label') }}</span>
                <strong>
                    @foreach((array) data_get($lengthPoint, 'display_values', []) as $size => $value)
                        {{ $size }}: {{ $value }}{{ data_get($lengthPoint, 'unit', 'cm') }}@if(!$loop->last) · @endif
                    @endforeach
                </strong>
            </article>
        @endif
        @if($model)
            <article><span>Người mẫu</span><strong>{{ data_get($model, 'summary') }}</strong></article>
        @endif
    </div>
</div>

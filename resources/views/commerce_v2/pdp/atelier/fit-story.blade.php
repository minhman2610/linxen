@php
    $identity = (array) data_get($pdp, 'identity', []);
    $chart = (array) data_get($pdp, 'fit.garment_size_chart', []);
    $points = collect((array) data_get($chart, 'points', []));
    $defaultMedia = collect((array) data_get($pdp, 'commerce.default_color.media', []));
    $production = collect((array) data_get($pdp, 'media.production_truth', []));
    $image = data_get($production->first(), 'url')
        ?: data_get($defaultMedia->get(1), 'url')
        ?: data_get($defaultMedia->first(), 'url')
        ?: data_get($pdp, 'media.cover_url');
    $findPoint = function (array $codes) use ($points): array {
        return (array) ($points->first(function ($point) use ($codes) {
            $code = mb_strtolower((string) data_get($point, 'code'));
            $label = mb_strtolower((string) data_get($point, 'label'));
            return collect($codes)->contains(fn ($needle) => str_contains($code.' '.$label, $needle));
        }) ?: []);
    };
    $lengthPoint = $findPoint(['dress_length', 'dài váy', 'length']);
    $bustPoint = $findPoint(['bust', 'ngực']);
    $waistPoint = $findPoint(['waist', 'eo']);
    $sizes = collect((array) data_get($chart, 'sizes', []));
    $focusSize = (string) ($sizes->contains('M') ? 'M' : ($sizes->first() ?: ''));
    $displayValue = function (array $point, string $size): string {
        $display = data_get($point, 'display_values.'.$size);
        $raw = data_get($point, 'values.'.$size);
        $value = $display !== null && $display !== '' ? $display : $raw;
        return $value !== null && $value !== '' ? $value.' '.data_get($point, 'unit', 'cm') : '—';
    };
@endphp

<div class="lxa-fit" data-lxa-reveal>
    <div class="lxa-fit__image">
        @if($image)
            <img src="{{ $image }}" alt="Phom dáng {{ data_get($identity, 'name') }}" loading="lazy" decoding="async">
        @endif
        <div class="lxa-fit__image-copy">
            <p>Fit & scale</p>
            <h2>Nhìn rõ tỷ lệ trước khi mặc.</h2>
        </div>
    </div>

    <div class="lxa-fit__panel">
        <p class="lxa-kicker">Fit confidence</p>
        <h2>Chọn size bằng dữ liệu thật.</h2>
        <p class="lxa-fit__intro">
            Bảng số đo được đọc từ hồ sơ sản xuất của riêng mẫu này. Đây là số đo thành phẩm — hãy so với một sản phẩm đang mặc vừa để chọn tự tin hơn.
        </p>

        <div class="lxa-fit__metrics">
            <article>
                <span>Form</span>
                <strong>{{ data_get($pdp, 'product_truth.design.items.0.value', 'Đã xác minh') }}</strong>
            </article>
            @if($focusSize !== '' && $lengthPoint !== [])
                <article><span>Độ dài size {{ $focusSize }}</span><strong>{{ $displayValue($lengthPoint, $focusSize) }}</strong></article>
            @endif
            @if($focusSize !== '' && $bustPoint !== [])
                <article><span>Vòng ngực size {{ $focusSize }}</span><strong>{{ $displayValue($bustPoint, $focusSize) }}</strong></article>
            @endif
            @if($focusSize !== '' && $waistPoint !== [])
                <article><span>Vòng eo size {{ $focusSize }}</span><strong>{{ $displayValue($waistPoint, $focusSize) }}</strong></article>
            @endif
        </div>

        <button
            type="button"
            class="lxa-outline-button"
            data-lxpdp-size-advisor-open
            @if(!data_get($pdp, 'fit.advisor.enabled')) disabled @endif
        >Kiểm tra size của bạn — khoảng 30 giây</button>

        @if(data_get($chart, 'structured'))
            <p class="lxa-source-note">Số đo được xác minh riêng cho mẫu này</p>
        @endif
    </div>
</div>

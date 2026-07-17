@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $cover = data_get($pdp, 'commerce.default_color.media.0.url') ?: data_get($pdp, 'media.cover_url');
@endphp
<div class="lxs-shell lxs-final-cta" data-lxs-reveal>
    <div class="lxs-final-cta__media">
        @if($cover)<img src="{{ $cover }}" alt="" loading="lazy" decoding="async">@endif
    </div>
    <div>
        <p class="lxs-kicker">Sẵn sàng chọn phiên bản của bạn?</p>
        <h2>{{ data_get($identity, 'short_name') ?: data_get($identity, 'name') }}</h2>
        <p>Quay lại khu vực màu và size để hoàn tất lựa chọn.</p>
    </div>
    <div class="lxs-final-cta__action">
        <strong>{{ number_format((float) data_get($commerce, 'price.min'), 0, ',', '.') }}₫</strong>
        <button type="button" class="lxs-button lxs-button--primary" data-pdp-scroll-to-purchase>Chọn màu &amp; size</button>
    </div>
</div>

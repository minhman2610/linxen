@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $media = collect((array) data_get($commerce, 'default_color.media', []));
    $image = data_get($media->last(), 'url')
        ?: data_get($media->first(), 'url')
        ?: data_get($pdp, 'media.cover_url');
@endphp

<div class="lxa-finale" data-lxa-reveal>
    @if($image)
        <img
            class="lxa-finale__image"
            data-lxa-finale-image
            src="{{ $image }}"
            alt="{{ data_get($identity, 'name') }}"
            loading="lazy"
            decoding="async"
        >
    @endif
    <div class="lxa-finale__shade" aria-hidden="true"></div>
    <div class="lxa-finale__copy">
        <p class="lxa-kicker lxa-kicker--light">Your colour. Your fit.</p>
        <h2>Bạn đã thấy phom.<br>Giờ hãy chọn phiên bản của mình.</h2>
        <p>Chọn màu, đối chiếu size và thêm sản phẩm vào giỏ khi mọi chi tiết đã đủ rõ.</p>
        <button type="button" class="lxa-finale__button" data-pdp-scroll-to-purchase>
            Trở lại chọn màu & size
        </button>
        <div class="lxa-finale__policies">
            <span>Thanh toán khi nhận hàng</span>
            <span>Hỗ trợ đổi size</span>
            <span>Giao hàng toàn quốc</span>
        </div>
    </div>
</div>

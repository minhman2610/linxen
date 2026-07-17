@php
    $media = collect((array) data_get($pdp, 'media.production_truth', []));
    $specs = collect((array) data_get($pdp, 'product_truth.raw_specs', []));
@endphp
<div class="lxpdp-story-block lxpdp-product-truth">
    <header class="lxpdp-story-heading">
        <p class="lxpdp-kicker">Sản phẩm thật</p>
        <h2>Những chi tiết giúp bạn mua tự tin hơn</h2>
        <p>Ảnh quảng cáo tạo cảm xúc; dữ liệu và ảnh sản phẩm thực tế giúp xác nhận thiết kế.</p>
    </header>
    @if($media->isNotEmpty())
        <div class="lxpdp-product-truth__gallery">
            @foreach($media as $item)
                <figure><img src="{{ data_get($item, 'url') }}" alt="{{ data_get($item, 'role') ?: 'Chi tiết sản phẩm' }}" loading="lazy" decoding="async"><figcaption>{{ data_get($item, 'role') ?: data_get($item, 'category_code') ?: 'Sản phẩm thực tế' }}</figcaption></figure>
            @endforeach
        </div>
    @endif
    @if($specs->isNotEmpty())
        <dl class="lxpdp-truth-specs">
            @foreach($specs as $spec)<div><dt>{{ data_get($spec, 'label') }}</dt><dd>{{ data_get($spec, 'value') }}</dd></div>@endforeach
        </dl>
    @endif
</div>

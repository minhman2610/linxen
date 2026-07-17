@php $items = collect((array) data_get($pdp, 'discovery.related_products', []))->take(3)->values(); @endphp
@if($items->isNotEmpty())
<div class="lxs-shell lxs-complete-look" data-lxs-reveal>
    <div class="lxs-section-heading lxs-section-heading--split">
        <div>
            <p class="lxs-kicker">Complete the look</p>
            <h2>Những lựa chọn có thể đi cùng thiết kế này.</h2>
        </div>
        <a href="{{ route('commerce.v2.shop') }}">Xem toàn bộ sản phẩm</a>
    </div>
    <div class="lxs-product-row">
        @foreach($items as $item)
            <a href="{{ data_get($item, 'url') }}" class="lxs-product-card">
                <span><img src="{{ data_get($item, 'cover_url') }}" alt="{{ data_get($item, 'name') }}" loading="lazy" decoding="async"></span>
                <strong>{{ data_get($item, 'name') }}</strong>
                <small>{{ number_format((float) data_get($item, 'price_min'), 0, ',', '.') }}₫</small>
            </a>
        @endforeach
    </div>
</div>
@endif

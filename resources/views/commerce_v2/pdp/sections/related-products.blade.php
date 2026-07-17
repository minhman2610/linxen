@php $products = collect((array) data_get($pdp, 'discovery.related_products', [])); @endphp
<div class="lxpdp-story-block">
    <header class="lxpdp-story-heading"><p class="lxpdp-kicker">Khám phá tiếp</p><h2>Cùng form, cùng cảm hứng</h2></header>
    <div class="lxpdp-related-grid">
        @foreach($products as $item)
            <a href="{{ data_get($item, 'url') }}"><img src="{{ data_get($item, 'cover_url') }}" alt="{{ data_get($item, 'name') }}" loading="lazy"><strong>{{ data_get($item, 'name') }}</strong><span>{{ number_format((float) data_get($item, 'price_min'), 0, ',', '.') }}₫</span></a>
        @endforeach
    </div>
</div>

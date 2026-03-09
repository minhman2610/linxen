<div class="lx-product-grid">

    @foreach($home['featured_products'] as $product)

    @php

    $slug = Str::slug($product['name']).'-'.$product['product_id'];
    $thumb = $product['media']['thumb_mobile'];
    $price = (float)$product['price'];

    $colors = $product['colors'] ?? [];
    $hasColors = count($colors) > 1;

    @endphp

    <div class="lx-product-card">

        <div class="lx-product-media">

            <a href="{{ route('linxen.product',['slug'=>$slug]) }}">
                <img src="{{ $thumb }}" loading="lazy">
            </a>

        </div>

        <div class="lx-product-head">

            <p class="lx-product-name">
                {{ $product['name'] }}
            </p>

        </div>

        <div class="lx-product-price">

            {{ number_format($price) }}₫

        </div>

        @if($hasColors)

        <div class="lx-product-colors">

            @foreach($colors as $color)

            <span class="lx-color-swatch"></span>

            @endforeach

        </div>

        @endif

        <div class="lx-product-tags">

            <span class="lx-tag">✨ Được yêu thích</span>

        </div>

        <a href="{{ route('linxen.product',['slug'=>$slug]) }}"
            class="lx-product-cta">

            Xem chi tiết →

        </a>

    </div>

    @endforeach

</div>
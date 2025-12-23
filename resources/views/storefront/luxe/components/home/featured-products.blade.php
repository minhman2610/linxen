{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – SALE & STATUS (UPDATED LAYOUT) --}}
{{-- ===================================================== --}}
@if(!empty($home['featured_products']) && is_array($home['featured_products']))
<section class="lx-product-section">

    <div class="lx-section-header">
        <h2 class="lx-section-title">VÁY NỔI BẬT</h2>
        <p class="lx-section-desc">
            Thiết kế được khách hàng LIN XÉN yêu thích
        </p>
    </div>

    <div class="lx-product-grid">
        @foreach($home['featured_products'] as $product)

            @continue(
                empty($product['product_id'])
                || empty($product['name'])
                || empty($product['media']['thumb_mobile'])
            )

            @php
                $slug = \Illuminate\Support\Str::slug($product['name'])
                        . '-' . $product['product_id'];

                $thumb = $product['media']['thumb_mobile'];

                $price       = (float) $product['price'];
                $salePercent = 20; // demo
                $salePrice   = round($price * (100 - $salePercent) / 100);

                // demo màu ngẫu nhiên
                $colors = collect(['black','white','beige','brown','red','blue'])
                            ->shuffle()
                            ->take(3);
            @endphp

            <div class="lx-product-card">

                {{-- IMAGE --}}
                <div class="lx-product-media">
                    <a href="{{ route('linxen.product', ['slug' => $slug]) }}">
                        <img src="{{ $thumb }}"
                             alt="{{ $product['name'] }}"
                             loading="lazy">
                    </a>

                    {{-- SALE BADGE --}}
                    <span class="lx-sale-badge">
                        -{{ $salePercent }}%
                    </span>
                </div>

                {{-- NAME + BEST SELLER --}}
                <div class="lx-product-head">
                    <span class="lx-badge-best">Best seller</span>
                    <p class="lx-product-name one-line">
                        {{ $product['name'] }}
                    </p>
                </div>

                {{-- PRICE --}}
                <div class="lx-product-price-wrap">
                    <span class="lx-price-sale">
                        {{ number_format($salePrice) }}₫
                    </span>
                    <span class="lx-price-origin">
                        {{ number_format($price) }}₫
                    </span>
                </div>

                {{-- STATUS TAGS --}}
                <div class="lx-product-tags">
                    <span class="lx-tag lx-tag-stock">✔ Còn hàng</span>
                    <span class="lx-tag lx-tag-trend">✨ Xu hướng</span>
                </div>

                {{-- COLORS --}}
                <div class="lx-product-colors">
                    @foreach($colors as $color)
                        <span class="lx-color-swatch {{ $color }}"></span>
                    @endforeach
                </div>

            </div>

        @endforeach
    </div>

</section>
@endif

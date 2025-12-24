{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – SALE & STATUS (WITH COLORS) --}}
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

                $colorPool = [
                    'black',
                    'white',
                    'beige',
                    'brown',
                    'red',
                    'blue',
                    'navy',
                    'olive'
                ];

                shuffle($colorPool);
                $productColors = array_slice($colorPool, 0, 3);
            @endphp

            <div class="lx-product-card">

                {{-- IMAGE --}}
                <div class="lx-product-media">
                    <a href="{{ route('linxen.product', ['slug' => $slug]) }}">
                        <img src="{{ $thumb }}"
                             alt="{{ $product['name'] }}"
                             loading="lazy">
                    </a>

                    <span class="lx-sale-badge">
                        -{{ $salePercent }}%
                    </span>
                </div>

                {{-- NAME + BEST SELLER --}}
                <div class="lx-product-head">
                    <span class="lx-tag lx-tag-best">🔥 Bán chạy</span>
                    <p class="lx-product-name">
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

                {{-- COLORS + QUICK ORDER (⬇️ DƯỚI GIÁ – CÙNG 1 HÀNG) --}}
                <div class="lx-product-variants">

                    <div class="lx-product-colors">
                        @foreach($productColors as $index => $color)
                            <span class="lx-color-swatch {{ $color }} {{ $index === 0 ? 'active' : '' }}"></span>
                        @endforeach
                    </div>

                    <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
                       class="lx-quick-order-inline"
                       aria-label="Đặt hàng">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="currentColor"
                                  d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 
                                     22 7 22s2-.9 2-2-.9-2-2-2Zm10 
                                     0c-1.1 0-1.99.9-1.99 2S15.9 
                                     22 17 22s2-.9 2-2-.9-2-2-2ZM7.17 
                                     14h9.66c.75 0 1.41-.41 
                                     1.75-1.03l3.58-6.49a1 
                                     1 0 0 0-.87-1.48H5.21L4.27 
                                     2H1v2h2l3.6 7.59-1.35 
                                     2.44C4.52 14.37 5.48 
                                     16 7 16h12v-2H7.17Z"/>
                        </svg>
                    </a>

                </div>

                {{-- STATUS --}}
                <div class="lx-product-tags">
                    <span class="lx-tag lx-tag-stock">✔ Còn hàng</span>
                    <span class="lx-tag lx-tag-trend">✨ Xu hướng</span>
                </div>

            </div>

        @endforeach
    </div>

</section>
@endif

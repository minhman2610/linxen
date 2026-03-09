{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – CLEAN PRICE (NO FLASH SALE) --}}
{{-- ===================================================== --}}
@if(!empty($home['featured_products']) && is_array($home['featured_products']))
<section class="lx-product-section">

    @php
        /**
         * ❌ LOẠI BỎ SKU CHA ĐÃ DÙNG CHO FLASH SALE
         */
        $flashSaleCodes = [
            'SP14535165',
            'SP14530509',
            'SP14529951',
            'SP14527951',
            'SP14527939',
            'SP14530151',
        ];
    @endphp

    <div class="lx-section-header">
        <h2 class="lx-section-title">VÁY NỔI BẬT</h2>
        <p class="lx-section-desc">
            Thiết kế được khách hàng LIN XÉN yêu thích
        </p>
    </div>

    <div class="lx-product-grid">
        @foreach($home['featured_products'] as $product)

            @php
                $code = $product['code'] ?? null;
            @endphp

            {{-- ❌ Bỏ sản phẩm thuộc Flash Sale --}}
            @continue($code && in_array($code, $flashSaleCodes, true))

            @continue(
                empty($product['product_id'])
                || empty($product['name'])
                || empty($product['media']['thumb_mobile'])
            )

            @php
                $slug = \Illuminate\Support\Str::slug($product['name'])
                        . '-' . $product['product_id'];

                $thumb = $product['media']['thumb_mobile'];
                $price = (float) $product['price'];
            @endphp

            <div class="lx-product-card">

                {{-- IMAGE --}}
                <div class="lx-product-media">
                    <a href="{{ route('linxen.product', ['slug' => $slug]) }}">
                        <img src="{{ $thumb }}"
                             alt="{{ $product['name'] }}"
                             loading="lazy">
                    </a>
                </div>

                {{-- NAME --}}
                <div class="lx-product-head">
                    <p class="lx-product-name">
                        {{ $product['name'] }}
                    </p>
                </div>

                {{-- PRICE --}}
<div class="lx-product-price-wrap">

@php
$price = (float) $product['price'];
$original = (float) ($product['original_price'] ?? $price);
$salePercent = (int) ($product['sale_percent'] ?? 0);
@endphp


@if($original > $price)

    <div class="lx-price-sale">
        {{ number_format($price) }}₫
    </div>

    <div class="lx-price-origin">
        {{ number_format($original) }}₫
    </div>

    <div class="lx-price-discount">
        -{{ $salePercent }}%
    </div>

@else

    <div class="lx-price-normal">
        {{ number_format($price) }}₫
    </div>

@endif

</div>

                <!-- {{-- QUICK ORDER --}}
                <div class="lx-product-variants">

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

                </div> -->

                {{-- STATUS --}}
                <!-- <div class="lx-product-tags">
                    <span class="lx-tag lx-tag-stock">✔ Còn hàng</span>
                    <span class="lx-tag lx-tag-trend">✨ Xu hướng</span>
                </div> -->

            </div>

        @endforeach
    </div>

</section>
@endif
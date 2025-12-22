{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – MOBILE FIRST (STATIC, POLISHED) --}}
{{-- ===================================================== --}}
@if(!empty($products) && is_array($products))
<section class="lx-product-section">

    {{-- SECTION HEADER --}}
    <div class="lx-section-header">
        <h2 class="lx-section-title">VÁY ĐƯỢC YÊU THÍCH</h2>
        <p class="lx-section-desc">
            Những chiếc váy dễ mặc cho nhịp sống hằng ngày
        </p>
        
    </div>

    {{-- PRODUCT GRID --}}
    <div class="lx-product-grid">
        @foreach($products as $product)

            @continue(
                empty($product['product_id'])
                || empty($product['name'])
                || empty($product['media']['images'])
            )

            @php
                $slug = \Illuminate\Support\Str::slug($product['name'])
                        . '-' . $product['product_id'];

                $image = $product['media']['images'][0];
            @endphp

            <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
               class="lx-product-card">

                {{-- MEDIA --}}
                <div class="lx-product-media">

                    <img
                        src="{{ $image }}"
                        alt="{{ $product['name'] }}"
                        loading="lazy"
                    >

                    {{-- QUICK BUY ICON --}}
                    <span class="lx-quick-buy" aria-label="Xem sản phẩm">
                        <svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true">
                            <path fill="currentColor"
                                  d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2Zm10 
                                     0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 
                                     2-2-.9-2-2-2ZM7.17 14h9.66c.75 0 
                                     1.41-.41 1.75-1.03l3.58-6.49a1 
                                     1 0 0 0-.87-1.48H5.21L4.27 
                                     2H1v2h2l3.6 7.59-1.35 
                                     2.44C4.52 14.37 5.48 
                                     16 7 16h12v-2H7.17Z"/>
                        </svg>
                    </span>
                </div>

                {{-- TAG (DƯỚI ẢNH) --}}
                @if(!empty($product['tag']))
                    <div class="lx-product-tag-below">
                        {{ $product['tag'] }}
                    </div>
                @endif

                {{-- INFO --}}
                <div class="lx-product-info">

                    {{-- COLOR VARIANTS --}}
                    <div class="lx-product-colors">
                        <span class="lx-color-swatch black"></span>
                        <span class="lx-color-swatch red"></span>
                        <span class="lx-color-swatch blue"></span>
                    </div>

                    <p class="lx-product-name">
                        {{ $product['name'] }}
                    </p>

                    <p class="lx-product-price">
                        {{ number_format($product['price']) }}₫
                    </p>

                    <p class="lx-product-micro">
                        {{ $product['micro_copy'] ?? 'Dễ mặc mỗi ngày' }}
                    </p>
                </div>

            </a>
        @endforeach
    </div>

</section>
@endif

{{-- ===================================================== --}}
{{-- 🎨 CSS – FASHION, MOBILE FIRST --}}
{{-- ===================================================== --}}
<style>
.lx-product-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    padding: 0 14px;
}

.lx-product-card {
    text-decoration: none;
    color: inherit;
}

/* MEDIA */
.lx-product-media {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    border-radius: 8px;
    background: #f4f4f4;
}

.lx-product-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* QUICK BUY ICON */
.lx-quick-buy {
    position: absolute;
    right: 10px;
    bottom: 10px;

    width: 38px;
    height: 38px;
    border-radius: 50%;

    background: rgba(59,42,34,.92);
    color: #fff;

    display: flex;
    align-items: center;
    justify-content: center;

    box-shadow: 0 6px 16px rgba(0,0,0,.18);
}

/* TAG BELOW IMAGE */
.lx-product-tag-below {
    margin-top: 6px;
    font-size: 11px;
    color: #555;
}

/* INFO */
.lx-product-info {
    padding: 8px 2px 18px;
}

/* COLOR VARIANTS */
.lx-product-colors {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 0 6px;
}

.lx-color-swatch {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    position: relative;
}

.lx-color-swatch::after {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,.15);
}

.lx-color-swatch.black { background: #111; }
.lx-color-swatch.red   { background: #b11226; }
.lx-color-swatch.blue  { background: #1f3a5f; }

/* NAME */
.lx-product-name {
    font-family: ui-serif, Georgia, 'Times New Roman', serif;
    font-size: 14px;
    line-height: 1.45;
    color: #222;
    margin-bottom: 6px;

    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* PRICE */
.lx-product-price {
    font-size: 15px;
    font-weight: 700;
    letter-spacing: .3px;
    color: #3b2a22;
    margin-bottom: 6px;
}

/* MICRO */
.lx-product-micro {
    font-size: 12px;
    color: #777;
}

/* MOBILE */
@media (max-width: 480px) {
    .lx-product-name { font-size: 13.5px; }
    .lx-product-price { font-size: 14.5px; }
}
</style>

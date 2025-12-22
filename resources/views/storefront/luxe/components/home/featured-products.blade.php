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
        <a href="{{ route('linxen.collection', ['slug' => 'all']) }}"
           class="lx-section-link">
            Xem tất cả
        </a>
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

                    {{-- CTA ORDER --}}
                    <span class="lx-quick-buy">
                        Đặt hàng
                    </span>
                </div>

                {{-- TAG (KHÔNG ĐÈ ẢNH) --}}
                @if(!empty($product['tag']))
                    <div class="lx-product-tag-below">
                        {{ $product['tag'] }}
                    </div>
                @endif

                {{-- INFO --}}
                <div class="lx-product-info">

                    {{-- COLOR VARIANTS (FIX CỨNG, TO, RÕ) --}}
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
{{-- 🎨 CSS – FASHION, CLEAR, MOBILE FIRST --}}
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

/* QUICK BUY BUTTON */
.lx-quick-buy {
    position: absolute;
    right: 10px;
    bottom: 10px;

    background: rgba(59,42,34,.9); /* deep brown */
    color: #fff;

    font-size: 12px;
    padding: 6px 12px;
    border-radius: 20px;

    letter-spacing: .3px;
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

/* COLOR VARIANTS – TO, RÕ, ẤN TƯỢNG */
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

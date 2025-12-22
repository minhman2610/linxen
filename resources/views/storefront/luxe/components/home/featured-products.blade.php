{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – MOBILE FIRST (STATIC, CLEAN) --}}
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

                // CHỈ LẤY 1 ẢNH ĐẠI DIỆN
                $image = $product['media']['images'][0];
            @endphp

            <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
               class="lx-product-card">

                {{-- MEDIA --}}
                <div class="lx-product-media">

                    {{-- TAG (NẾU CÓ) --}}
                    @if(!empty($product['tag']))
                        <span class="lx-product-tag">
                            {{ $product['tag'] }}
                        </span>
                    @endif

                    <img
                        src="{{ $image }}"
                        alt="{{ $product['name'] }}"
                        loading="lazy"
                    >
                </div>

                {{-- INFO --}}
                <div class="lx-product-info">

                    {{-- FIX CỨNG MÀU --}}
                    <div class="lx-product-colors">
                        <span class="lx-color-dot black"></span>
                        <span class="lx-color-dot red"></span>
                        <span class="lx-color-dot blue"></span>
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
{{-- 🎨 CSS – MOBILE FIRST, CLEAN FASHION UI --}}
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
    border-radius: 6px;
    background: #f5f5f5;
}

.lx-product-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* TAG */
.lx-product-tag {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 2;

    background: rgba(0,0,0,.65);
    color: #fff;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 12px;
}

/* INFO */
.lx-product-info {
    padding: 10px 2px 18px;
}

/* NAME – dễ đọc, nữ tính */
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

/* PRICE – nổi bật */
.lx-product-price {
    font-size: 15px;
    font-weight: 700;
    letter-spacing: .3px;

    color: #3b2a22; /* deep brown luxe */
    margin-bottom: 6px;
}

/* MICRO */
.lx-product-micro {
    font-size: 12px;
    color: #777;
}

/* COLORS – FIX CỨNG */
.lx-product-colors {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}

.lx-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,.15);
}

.lx-color-dot.black { background: #111; }
.lx-color-dot.red   { background: #b11226; }
.lx-color-dot.blue  { background: #1f3a5f; }

/* MOBILE TUNE */
@media (max-width: 480px) {
    .lx-product-name { font-size: 13.5px; }
    .lx-product-price { font-size: 14.5px; }
}
</style>

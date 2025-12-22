{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – SALE FOCUSED / MOBILE FIRST --}}
{{-- ===================================================== --}}
@if(!empty($products) && is_array($products))
<section class="lx-product-section">

    <div class="lx-section-header">
        <h2 class="lx-section-title">VÁY ĐANG GIẢM GIÁ</h2>
        <p class="lx-section-desc">
            Ưu đãi đặc biệt – số lượng có hạn
        </p>
    </div>

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

                // FIX TAY SALE
                $salePercent = 20;
                $price       = $product['price'];
                $salePrice   = round($price * (100 - $salePercent) / 100);
            @endphp

            <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
               class="lx-product-card">

                {{-- ================= IMAGE ================= --}}
                <div class="lx-product-media">

                    <img src="{{ $image }}"
                         alt="{{ $product['name'] }}"
                         loading="lazy">

                    {{-- SALE BADGE --}}
                    <span class="lx-sale-badge">
                        -{{ $salePercent }}%
                    </span>

                    {{-- SALE TEXT --}}
                    <span class="lx-sale-text">
                        Giảm {{ $salePercent }}% hôm nay
                    </span>

                </div>

                {{-- ================= PRICE ================= --}}
                <div class="lx-product-price-wrap">
                    <span class="lx-price-sale">
                        {{ number_format($salePrice) }}₫
                    </span>
                    <span class="lx-price-origin">
                        {{ number_format($price) }}₫
                    </span>
                </div>

                {{-- ================= NAME ================= --}}
                <p class="lx-product-name one-line">
                    {{ $product['name'] }}
                </p>

                {{-- ================= STATUS ================= --}}
                <div class="lx-product-status">
                    HÀNG CÓ SẴN
                </div>

                {{-- ================= COLORS ================= --}}
                <div class="lx-product-colors">
                    <span class="lx-color-swatch active black"></span>
                    <span class="lx-color-swatch red"></span>
                    <span class="lx-color-swatch blue"></span>
                </div>

            </a>

        @endforeach
    </div>

</section>
@endif
<style>
    /* GRID */
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

/* IMAGE */
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
}

/* SALE BADGE */
.lx-sale-badge {
    position: absolute;
    top: 8px;
    left: 8px;

    background: #b11226;
    color: #fff;

    font-size: 12px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 4px;
}

/* SALE TEXT */
.lx-sale-text {
    position: absolute;
    bottom: 8px;
    left: 8px;

    background: rgba(0,0,0,.55);
    color: #fff;

    font-size: 11px;
    padding: 4px 6px;
    border-radius: 4px;
}

/* PRICE */
.lx-product-price-wrap {
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.lx-price-sale {
    font-size: 15px;
    font-weight: 700;
    color: #b11226;
}

.lx-price-origin {
    font-size: 12px;
    color: #999;
    text-decoration: line-through;
}

/* NAME */
.lx-product-name {
    margin-top: 4px;
    font-size: 13.5px;
    color: #222;
}

.lx-product-name.one-line {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* STATUS */
.lx-product-status {
    margin-top: 4px;
    font-size: 11px;
    color: #3b2a22;
    font-weight: 600;
}

/* COLORS */
.lx-product-colors {
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.lx-color-swatch {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    opacity: .7;
}

.lx-color-swatch.active {
    width: 18px;
    height: 18px;
    opacity: 1;
}

.lx-color-swatch.black { background: #111; }
.lx-color-swatch.red   { background: #b11226; }
.lx-color-swatch.blue  { background: #1f3a5f; }

/* MOBILE */
@media (max-width: 480px) {
    .lx-price-sale { font-size: 14.5px; }
}
</style>
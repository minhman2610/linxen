{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – SALE & STATUS FOCUSED --}}
{{-- ===================================================== --}}
@if(!empty($products) && is_array($products))
<section class="lx-product-section">

    <div class="lx-section-header">
        <h2 class="lx-section-title">VÁY NỔI BẬT</h2>
        <p class="lx-section-desc">
            Thiết kế được khách hàng LIN XÉN yêu thích
        </p>
    </div>

    <div class="lx-product-grid">
        @foreach($products as $product)

            @continue(
                empty($product['product_id'])
                || empty($product['name'])
                || empty($product['thumb_url'])
            )

            @php
                $slug = \Illuminate\Support\Str::slug($product['name'])
                        . '-' . $product['product_id'];

                // FIX TAY DEMO
                $price        = $product['price'];
                $salePercent  = 20;
                $salePrice   = round($price * (100 - $salePercent) / 100);

                // FIX TAY STATUS
                $status = 'in_stock'; // in_stock | out_stock | preorder | best | trend
            @endphp

            <div class="lx-product-card">

                {{-- IMAGE --}}
                <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
                   class="lx-product-media">

                    <img src="{{ $product['thumb_url'] }}"
                         alt="{{ $product['name'] }}"
                         loading="lazy">

                    {{-- SALE BADGE --}}
                    <span class="lx-sale-badge">
                        -{{ $salePercent }}%
                    </span>

                </a>

                {{-- PRICE --}}
                <div class="lx-product-price-wrap">
                    <span class="lx-price-sale">
                        {{ number_format($salePrice) }}₫
                    </span>
                    <span class="lx-price-origin">
                        {{ number_format($price) }}₫
                    </span>
                </div>

                {{-- NAME --}}
                <p class="lx-product-name one-line">
                    {{ $product['name'] }}
                </p>

                {{-- STATUS --}}
                <div class="lx-product-tags">

                    <span class="lx-tag lx-tag-stock">
                        ✔ Còn hàng
                    </span>

                    <span class="lx-tag lx-tag-best">
                        🔥 Bán chạy
                    </span>

                </div>

                {{-- COLORS --}}
                <div class="lx-product-colors">
                    <span class="lx-color-swatch active black"></span>
                    <span class="lx-color-swatch red"></span>
                    <span class="lx-color-swatch blue"></span>
                </div>

                {{-- ACTION --}}
                <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
                   class="lx-btn-order">
                    ĐẶT HÀNG
                </a>

            </div>

        @endforeach
    </div>

</section>
@endif

<style>
    /* GRID */
.lx-product-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    padding: 0 14px;
}

.lx-product-card {
    display: flex;
    flex-direction: column;
}

/* IMAGE */
.lx-product-media {
    position: relative;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    border-radius: 10px;
    background: #f4f4f4;
}

.lx-product-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* SALE BADGE – RIGHT */
.lx-sale-badge {
    position: absolute;
    top: 8px;
    right: 8px;

    background: #b11226;
    color: #fff;

    font-size: 12px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 6px;
}

/* PRICE */
.lx-product-price-wrap {
    margin-top: 10px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.lx-price-sale {
    font-size: 16.5px;
    font-weight: 700;
    color: #b11226;
}

.lx-price-origin {
    font-size: 13px;
    color: #999;
    text-decoration: line-through;
}

/* NAME */
.lx-product-name {
    margin-top: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #222;
    line-height: 1.35;
}

.one-line {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* TAGS */
.lx-product-tags {
    margin-top: 6px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.lx-tag {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 999px;
    border: 1px solid;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* TAG TYPES */
.lx-tag-stock {
    color: #1f7a4f;
    border-color: #1f7a4f;
    background: rgba(31,122,79,.08);
}

.lx-tag-out {
    color: #999;
    border-color: #ccc;
}

.lx-tag-pre {
    color: #8a5a00;
    border-color: #e0b95c;
}

.lx-tag-best {
    color: #b11226;
    border-color: #b11226;
    background: rgba(177,18,38,.08);
}

.lx-tag-trend {
    color: #1f3a5f;
    border-color: #1f3a5f;
}

/* COLORS – TO, CÓ VIỀN */
.lx-product-colors {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.lx-color-swatch {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #ddd;
    box-sizing: border-box;
}

.lx-color-swatch.active {
    width: 22px;
    height: 22px;
    border-color: #000;
}

.lx-color-swatch.black { background: #111; }
.lx-color-swatch.red   { background: #b11226; }
.lx-color-swatch.blue  { background: #1f3a5f; }

/* ORDER BUTTON */
.lx-btn-order {
    margin-top: 12px;
    padding: 10px 0;
    text-align: center;

    font-size: 13px;
    font-weight: 600;
    letter-spacing: .4px;

    color: #fff;
    background: #3b2a22;
    border-radius: 8px;
    text-decoration: none;
}
</style>
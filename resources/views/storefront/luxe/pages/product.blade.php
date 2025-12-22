@extends('storefront.luxe.layouts.app')

@section('content')

@php
    use Illuminate\Support\Str;

    // ============================
    // NORMALIZE DATA FROM API
    // ============================
    $images = [];

    if (!empty($product['images']) && is_array($product['images'])) {
        $images = $product['images'];
    } elseif (!empty($product['thumb_url'])) {
        $images = [$product['thumb_url']];
    }

    $mainImage = $images[0] ?? '';

    $variants   = $product['variants'] ?? [];
    $attributes = $product['attributes'] ?? [];
@endphp

<section class="lx-product-detail">

    {{-- =========================
        PRODUCT GALLERY
    ========================= --}}
    <div class="lx-product-gallery">
        <div class="lx-product-main-image">
            <img id="lxMainImage"
                 src="{{ $mainImage }}"
                 alt="{{ $product['name'] ?? '' }}">
        </div>

        @if(count($images) > 1)
            <div class="lx-product-thumbs">
                @foreach($images as $img)
                    <img src="{{ $img }}"
                         onclick="previewImage('{{ $img }}')"
                         alt="">
                @endforeach
            </div>
        @endif
    </div>

    {{-- =========================
        PRODUCT INFO
    ========================= --}}
    <div class="lx-product-info">

        <h1 class="lx-product-title">
            {{ $product['name'] ?? '' }}
        </h1>

        <div class="lx-product-meta">
            @if(!empty($product['code']))
                <span>Mã SP: <strong>{{ $product['code'] }}</strong></span>
            @endif
            <span class="lx-badge">{{ strtoupper($brand) }}</span>
        </div>

        <div class="lx-product-price">
            {{ number_format($product['price'] ?? 0) }}₫
        </div>

        <div class="lx-product-description">
            {!! nl2br(e($product['description'] ?? 'Thiết kế tinh tế – phom dáng hiện đại.')) !!}
        </div>

        {{-- =========================
            VARIANTS
        ========================= --}}
        @if(!empty($attributes))
            <div class="lx-product-variants" id="lxVariants">
                @foreach($attributes as $attr => $values)
                    @php
                        $attrKey = Str::slug($attr, '_');
                    @endphp
                    <div class="lx-attr-group"
                         data-attr="{{ $attr }}"
                         data-attr-key="{{ $attrKey }}">
                        <label>{{ $attr }}</label>
                        <div class="lx-attr-values">
                            @foreach($values as $val)
                                <div class="variant-option"
                                     data-attr="{{ $attr }}"
                                     data-attr-key="{{ $attrKey }}"
                                     data-value="{{ $val }}">
                                    {{ $val }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- =========================
            STOCK
        ========================= --}}
        <p class="lx-product-stock" id="lxStock">
            Vui lòng chọn biến thể
        </p>

        {{-- =========================
            ACTIONS
        ========================= --}}
        <div class="lx-product-actions">
            <div class="lx-qty">
                <button type="button" onclick="changeQty(-1)">−</button>
                <input type="number" id="lxQty" value="1" min="1">
                <button type="button" onclick="changeQty(1)">+</button>
            </div>

            <button
                class="lx-btn-primary lx-btn-full"
                id="lxAddToCartBtn"
                type="button">
                THÊM VÀO GIỎ
            </button>
        </div>

        <ul class="lx-product-trust">
            <li>✔ Thiết kế độc quyền LIN XÉN</li>
            <li>✔ Chất liệu cao cấp</li>
            <li>✔ Đổi trả trong 7 ngày</li>
        </ul>

    </div>
</section>

<div id="lxToast" class="lx-toast"></div>

@endsection
<style>
    /* =====================================================
   PRODUCT PAGE LAYOUT FIX
===================================================== */

/* Chừa chỗ cho bottom-nav */
.lx-product-detail {
    max-width: 1200px;
    margin: 0 auto;
    padding: 16px 16px 90px; /* 👈 90px = height bottom-nav + breathing space */
    display: grid;
    gap: 32px;
}

/* Desktop */
@media (min-width: 768px) {
    .lx-product-detail {
        grid-template-columns: 1fr 1fr;
        padding-bottom: 40px; /* desktop không có bottom-nav */
    }
}

/* IMAGE */
.lx-product-main-image img {
    width: 100%;
    aspect-ratio: 3 / 4;
    object-fit: cover;
    display: block;
}

/* THUMBS */
.lx-product-thumbs {
    display: flex;
    gap: 8px;
    margin-top: 10px;
}

.lx-product-thumbs img {
    width: 64px;
    height: 86px;
    object-fit: cover;
    cursor: pointer;
}

/* ACTIONS */
.lx-product-actions {
    margin-top: 20px;
    display: flex;
    gap: 12px;
}

/* QTY */
.lx-qty {
    display: flex;
    border: 1px solid #ddd;
}

.lx-qty button {
    width: 36px;
    background: #fff;
    border: none;
}

.lx-qty input {
    width: 50px;
    text-align: center;
    border: none;
}

</style>
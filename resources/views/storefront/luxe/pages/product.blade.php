@extends('storefront.luxe.layouts.app')

@section('content')

@php
    use Illuminate\Support\Str;

    /* ============================
       NORMALIZE DATA
    ============================ */
    $images = [];

    if (!empty($product['images']) && is_array($product['images'])) {
        $images = $product['images'];
    } elseif (!empty($product['thumb_url'])) {
        $images = [$product['thumb_url']];
    }

    $variants   = $product['variants'] ?? [];
    $attributes = $product['attributes'] ?? [];
@endphp

<section class="lx-product-detail">

    {{-- =====================================================
       PRODUCT GALLERY – SLIDER
    ===================================================== --}}
    <div class="lx-product-gallery">

        <div class="swiper lx-product-swiper">
            <div class="swiper-wrapper">

                @foreach($images as $img)
                    <div class="swiper-slide">
                        <img
                            src="{{ $img }}"
                            alt="{{ $product['name'] ?? '' }}"
                            loading="lazy">
                    </div>
                @endforeach

            </div>

            {{-- Pagination --}}
            <div class="swiper-pagination"></div>
        </div>

    </div>

    {{-- =====================================================
       PRODUCT INFO
    ===================================================== --}}
    <div class="lx-product-info">

        {{-- TITLE --}}
        <h1 class="lx-product-title">
            {{ $product['name'] ?? '' }}
        </h1>

        {{-- META --}}
        <div class="lx-product-meta">
            @if(!empty($product['code']))
                <span>Mã SP: <strong>{{ $product['code'] }}</strong></span>
            @endif
            <span class="lx-badge">{{ strtoupper($brand) }}</span>
        </div>

        {{-- PRICE --}}
        <div class="lx-product-price">
            {{ number_format($product['price'] ?? 0) }}₫
        </div>

        {{-- DESCRIPTION --}}
        <div class="lx-product-description">
            {!! nl2br(e($product['description'] ?? 'Thiết kế tinh tế – phom dáng hiện đại.')) !!}
        </div>

        {{-- =====================================================
           VARIANTS
        ===================================================== --}}
        @if(!empty($attributes))
            <div class="lx-product-variants" id="lxVariants">

                @foreach($attributes as $attr => $values)
                    @php
                        $attrKey = Str::slug($attr, '_');
                    @endphp

                    <div class="lx-attr-group"
                         data-attr="{{ $attr }}"
                         data-attr-key="{{ $attrKey }}">

                        <label class="lx-attr-label">
                            {{ $attr }}
                        </label>

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

        {{-- STOCK --}}
        <p class="lx-product-stock" id="lxStock">
            Vui lòng chọn biến thể
        </p>

        {{-- =====================================================
           ACTIONS
        ===================================================== --}}
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

        {{-- TRUST --}}
        <ul class="lx-product-trust">
            <li>✔ Thiết kế độc quyền LIN XÉN</li>
            <li>✔ Chất liệu cao cấp</li>
            <li>✔ Đổi trả trong 7 ngày</li>
        </ul>

    </div>

</section>

{{-- TOAST --}}
<div id="lxToast" class="lx-toast"></div>

@endsection

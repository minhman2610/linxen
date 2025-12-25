@extends('storefront.luxe.layouts.app')

@section('content')

@php
    use Illuminate\Support\Str;

    $images     = $images ?? [];
    $variants   = $variants ?? [];
    $attributes = $attributes ?? [];
@endphp

<section class="lx-product-detail">

    {{-- =====================================================
       PRODUCT GALLERY – FULL BLEED
    ===================================================== --}}
    <div class="lx-product-gallery">

        <div class="lx-product-main" id="lxProductMain">
            <img
                id="lxMainImage"
                src="{{ $mainImage }}"
                data-index="0"
                alt="{{ $product['name'] ?? '' }}"
                loading="eager">

            {{-- Subtle navigation zones --}}
            <button class="lx-gallery-btn prev" id="lxGalleryPrev" aria-label="Previous"></button>
            <button class="lx-gallery-btn next" id="lxGalleryNext" aria-label="Next"></button>
        </div>

        @if(count($images) > 1)
            <div class="lx-product-thumbs" id="lxProductThumbs">
                @foreach($images as $index => $img)
                    <img
                        src="{{ $img['thumb'] }}"
                        data-full="{{ $img['full'] }}"
                        data-index="{{ $index }}"
                        class="{{ $index === 0 ? 'active' : '' }}"
                        alt="{{ $product['name'] ?? '' }} thumbnail {{ $index + 1 }}"
                        loading="lazy">
                @endforeach
            </div>
        @endif

    </div>

    {{-- =====================================================
       PRODUCT CONTENT – CONTAINER (FIX CẮN MÉP)
    ===================================================== --}}
    <div class="lx-product-content">

        {{-- =====================================================
           PRODUCT INFO – PREMIUM
        ===================================================== --}}
        <div class="lx-product-info">

            {{-- BRAND LINE --}}
            <div class="lx-product-brandline">
                <span class="lx-brand">{{ strtoupper($brand) }}</span>
                <span class="lx-divider">—</span>
                <span class="lx-tagline">Thiết kế tinh tế cho vẻ đẹp hiện đại</span>
            </div>

            {{-- TITLE --}}
            <h1 class="lx-product-title">
                {{ $product['name'] ?? '' }}
            </h1>

            {{-- META --}}
            <div class="lx-product-meta">
                @if(!empty($product['code']))
                    <span class="lx-meta-item">
                        Mã SP: <strong>{{ $product['code'] }}</strong>
                    </span>
                    <span class="lx-meta-dot">•</span>
                @endif
                <span class="lx-meta-item">Hàng thiết kế</span>
            </div>

            {{-- PRICE --}}
            <div class="lx-product-price-wrap">
                <span class="lx-product-price">
                    {{ number_format($product['price'] ?? 0) }}₫
                </span>
                <span class="lx-price-note">
                    Giá đã bao gồm VAT
                </span>
            </div>

            {{-- DESCRIPTION --}}
            <div class="lx-product-description">
                {!! nl2br(e(
                    $product['description']
                    ?? 'Thiết kế tinh tế, phom dáng chuẩn, tôn dáng và dễ phối trong nhiều hoàn cảnh.'
                )) !!}
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
                                    <button
                                        type="button"
                                        class="variant-option"
                                        data-attr="{{ $attr }}"
                                        data-attr-key="{{ $attrKey }}"
                                        data-value="{{ $val }}">
                                        {{ $val }}
                                    </button>
                                @endforeach
                            </div>

                        </div>
                    @endforeach

                </div>
            @endif

            {{-- STOCK --}}
            <p class="lx-product-stock" id="lxStock">
                Vui lòng chọn đầy đủ biến thể
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
                    Thêm vào giỏ hàng
                </button>

            </div>

            {{-- TRUST / USP --}}
            <ul class="lx-product-trust">
                <li>✔ Thiết kế độc quyền LIN XÉN</li>
                <li>✔ Chất liệu cao cấp, chọn lọc</li>
                <li>✔ Đổi trả trong 7 ngày</li>
            </ul>

        </div>
        {{-- /product-info --}}

    </div>
    {{-- /product-content --}}

</section>

{{-- TOAST --}}
<div id="lxToast" class="lx-toast"></div>

@endsection

@extends('storefront.luxe.layouts.app')

@section('content')

@php
    use Illuminate\Support\Str;

    $images     = $images ?? [];
    $variants   = $variants ?? [];
    $attributes = $attributes ?? [];
@endphp

<section class="lx-product-detail">
    @include('storefront.luxe.components.product.breadcrumb', [
    'breadcrumbs' => $breadcrumbs
])
    {{-- =====================================================
       PRODUCT GALLERY – SWIPER PRO
    ===================================================== --}}
    <div class="lx-product-gallery">

        <div class="swiper lx-product-main-swiper">
            <div class="swiper-wrapper">
                @foreach($images as $index => $img)
                    <div class="swiper-slide">
                        <img
                            src="{{ $img['mobile'] ?? $img['thumb'] }}"
                            data-full="{{ $img['full'] }}"
                            alt="{{ $product['name'] ?? '' }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            class="lx-main-image">
                    </div>
                @endforeach
            </div>

            <div class="swiper-pagination"></div>
            <div class="lx-gallery-nav lx-gallery-prev"></div>
            <div class="lx-gallery-nav lx-gallery-next"></div>
        </div>

        @if(count($images) > 1)
            <div class="swiper lx-product-thumb-swiper">
                <div class="swiper-wrapper">
                    @foreach($images as $img)
                        <div class="swiper-slide">
                            <img src="{{ $img['thumb'] }}" loading="lazy" alt="">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    {{-- =====================================================
       PRODUCT CONTENT
    ===================================================== --}}
    <div class="lx-product-content">

        {{-- ================= PRODUCT INFO ================= --}}
        <div class="lx-product-info">

            <div class="lx-product-brandline">
                <span class="lx-brand">{{ strtoupper($brand) }}</span>
                <span class="lx-divider">—</span>
                <span class="lx-tagline">Thiết kế tinh tế cho vẻ đẹp hiện đại</span>
            </div>

            <h1 class="lx-product-title">
                {{ $product['name'] ?? '' }}
            </h1>

            <div class="lx-product-meta">
                @if(!empty($product['code']))
                    <span>Mã SP: <strong>{{ $product['code'] }}</strong></span>
                    <span class="dot">•</span>
                @endif
                <span>Hàng thiết kế</span>
            </div>

            <div class="lx-product-price-wrap">
                <span class="lx-product-price">
                    {{ number_format($product['price'] ?? 0) }}₫
                </span>
                <span class="lx-price-note">Đã bao gồm VAT</span>
            </div>

            <div class="lx-product-description">
                {!! nl2br(e(
                    $product['description']
                    ?? 'Thiết kế tinh tế, phom dáng chuẩn, dễ mặc trong nhiều hoàn cảnh.'
                )) !!}
            </div>

            {{-- ================= VARIANTS ================= --}}
            @if(!empty($attributes))
                <div class="lx-product-variants" id="lxVariants">
                    @foreach($attributes as $attr => $values)
                        @php $attrKey = Str::slug($attr, '_'); @endphp
                        <div class="lx-attr-group" data-attr="{{ $attr }}" data-attr-key="{{ $attrKey }}">
                            <label class="lx-attr-label">{{ $attr }}</label>
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

            <p class="lx-product-stock" id="lxStock">
                Vui lòng chọn đầy đủ biến thể
            </p>

            {{-- ================= ACTIONS ================= --}}
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

            {{-- ================= TRUST ================= --}}
            <ul class="lx-product-trust">
                <li>✔ Thiết kế độc quyền LIN XÉN</li>
                <li>✔ Chất liệu chọn lọc, form chuẩn</li>
                <li>✔ Đổi trả trong 7 ngày</li>
            </ul>

        </div>

        {{-- =====================================================
           ACCORDION – GIẢI QUYẾT LĂN TĂN
        ===================================================== --}}
        <div class="lx-product-accordion">

            <details open>
                <summary>Chất liệu & phom dáng</summary>
                <p>Chất liệu cao cấp, mềm mại, đứng phom. Thiết kế tôn dáng, dễ mặc.</p>
            </details>

            <details>
                <summary>Hướng dẫn chọn size</summary>
                <p>Nếu bạn ở giữa hai size, nên chọn size lớn hơn để mặc thoải mái.</p>
            </details>

            <details>
                <summary>Bảo quản</summary>
                <p>Giặt tay nhẹ, không vắt mạnh, phơi nơi thoáng mát.</p>
            </details>

            <details>
                <summary>Đổi trả</summary>
                <p>Hỗ trợ đổi trả trong 7 ngày nếu sản phẩm chưa qua sử dụng.</p>
            </details>

        </div>

        {{-- =====================================================
           SOCIAL PROOF – NHẸ
        ===================================================== --}}
        <div class="lx-product-social-proof">
            <p>💬 <strong>Khách hàng LIN XÉN:</strong> “Form váy rất tôn dáng, mặc lên nhìn gọn và sang.”</p>
        </div>

        {{-- =====================================================
           PHỐI CÙNG
        ===================================================== --}}
        @if(!empty($relatedProducts))
            <section class="lx-related-products">
                <h3>Phối cùng</h3>
                <div class="lx-related-grid">
                    @foreach($relatedProducts as $rp)
                        <a href="{{ route('linxen.product', ['slug' => $rp['slug']]) }}"
                           class="lx-related-card">
                            <img src="{{ $rp['thumb_mobile'] }}" alt="">
                            <span>{{ $rp['name'] }}</span>
                            <strong>{{ number_format($rp['price']) }}₫</strong>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

    </div>

</section>

{{-- =====================================================
   STICKY CTA – MOBILE
===================================================== --}}
<div class="lx-sticky-cta" id="lxStickyCTA">
    <span class="price">{{ number_format($product['price'] ?? 0) }}₫</span>
    <button onclick="document.getElementById('lxAddToCartBtn').click()">
        Thêm vào giỏ
    </button>
</div>

@endsection

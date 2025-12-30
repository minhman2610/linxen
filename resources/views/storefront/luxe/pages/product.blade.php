@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-product-detail lx-pdp">

    <div class="lx-product-content lx-pdp-content">

        {{-- ================= PRODUCT LAYOUT ================= --}}
        <div class="lx-product-layout">

            {{-- LEFT: GALLERY --}}
            <div class="lx-product-gallery-wrap">
                @include('storefront.luxe.components.product.gallery', [
                    'images'  => $images,
                    'product' => $product
                ])
            </div>

            {{-- RIGHT: INFO --}}
            <div class="lx-product-info">

                {{-- ================= BREADCRUMB ================= --}}
                @include('storefront.luxe.components.product.breadcrumb', [
                    'breadcrumbs' => $breadcrumbs
                ])

                {{-- BRAND HERO --}}
                <div class="lx-brand-hero">
                    <div
                        class="lx-brand-typewriter"
                        data-text="LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY"
                    >
                        LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY
                    </div>

                    <h1 class="lx-product-title luxury-title">
                        <span class="lx-title-icon">✦</span>
                        {{ $product['name'] ?? '' }}
                    </h1>
                </div>

                {{-- META --}}
                <div class="lx-product-meta">
                    @if(!empty($product['code']))
                        <span class="lx-meta-tag">
                            MÃ {{ $product['code'] }}
                        </span>
                    @endif
                    <span class="lx-meta-tag accent">
                        THIẾT KẾ LIN XÉN
                    </span>
                </div>

                {{-- PRICE --}}
                <div class="lx-product-price-wrap">
                    <span class="lx-product-price">
                        {{ number_format($product['price'] ?? 0) }}₫
                    </span>
                    <span class="lx-price-note">
                        ĐÃ BAO GỒM VAT
                    </span>
                </div>

                {{-- DESCRIPTION --}}
                <div class="lx-product-description">
                    {!! nl2br(e(
                        $product['description']
                        ?? 'Thiết kế tinh tế, phom dáng chuẩn, dễ mặc trong nhiều hoàn cảnh.'
                    )) !!}
                </div>

                {{-- ================= VARIANTS ================= --}}
                @include('storefront.luxe.components.product.variants', [
                    'attributes' => $attributes,
                    'variants'   => $variants
                ])

                {{-- ================= SIZE GUIDE (CTA + MODAL) ================= --}}
                @include('storefront.luxe.components.product.size-guide')

                {{-- ================= PDP BOTTOM GROUP ================= --}}
                <div class="lx-pdp-bottom">

                    {{-- ACTIONS --}}
                    @include('storefront.luxe.components.product.actions')

                    {{-- TRUST --}}
                    @include('storefront.luxe.components.product.trust')

                    {{-- REAL CUSTOMER GALLERY (FROM 3MG ERP) --}}
                    @include('storefront.luxe.components.product.real-gallery', [
                        'ugcMedia' => $ugcMedia ?? []
                    ])

                    {{-- SUGGESTED PRODUCTS (FROM 3MG ERP) --}}
                    @if(($suggestedCount ?? 0) > 0)
                        @include('storefront.luxe.components.product.suggested-products', [
                            'suggestedProducts' => $suggestedProducts ?? []
                        ])
                    @endif

                </div>

            </div>
        </div>
    </div>

</section>
@endsection

{{-- ================= SIZE GUIDE JS ================= --}}
@push('scripts')
<script>
document.addEventListener('click', function (e) {
    const overlay = document.querySelector('[data-size-guide-overlay]');
    if (!overlay) return;

    // OPEN
    if (e.target.closest('[data-size-guide-open]')) {
        overlay.hidden = false;
        document.body.style.overflow = 'hidden'; // lock scroll
        return;
    }

    // CLOSE bằng nút X
    if (e.target.closest('[data-size-guide-close]')) {
        overlay.hidden = true;
        document.body.style.overflow = '';
        return;
    }

    // CLOSE khi click ra ngoài modal
    if (
        e.target.closest('[data-size-guide-overlay]') &&
        !e.target.closest('.lx-size-guide-modal')
    ) {
        overlay.hidden = true;
        document.body.style.overflow = '';
        return;
    }
});
</script>
@endpush


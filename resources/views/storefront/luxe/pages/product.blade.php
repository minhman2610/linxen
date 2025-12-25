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
                    <div class="lx-brand-typewriter"
                         data-text="LIN XÉN — CHÚNG TÔI ĐAM MÊ VÁY">
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

                {{-- VARIANTS --}}
                @include('storefront.luxe.components.product.variants', [
                    'attributes' => $attributes
                ])

                {{-- ACTIONS --}}
                @include('storefront.luxe.components.product.actions')

                {{-- TRUST --}}
                @include('storefront.luxe.components.product.trust')
                {{-- REAL CUSTOMER GALLERY (FROM 3MG ERP) --}}
@include('storefront.luxe.components.product.real-gallery', [
    'ugcMedia' => $ugcMedia ?? []
])

            </div>
        </div>
    </div>
</section>
@endsection

@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- CATEGORY HERO --}}
{{-- ===================================================== --}}
<section class="lx-category-hero {{ empty($collection['hero']) ? 'no-hero' : '' }}">

    @if(!empty($collection['hero']))
        <img
            src="{{ $collection['hero'] }}"
            alt="{{ $collection['name'] }} LIN XÉN"
            loading="eager">
    @endif

    <div class="lx-category-hero-text">
        <h1>{{ $collection['name'] }}</h1>

        @if(!empty($collection['description']))
            <p class="lx-category-desc">
                {{ $collection['description'] }}
            </p>
        @else
            <p class="lx-category-desc muted">
                Khám phá các thiết kế nữ tính, tinh tế và dễ mặc từ LIN XÉN.
            </p>
        @endif
    </div>

</section>

{{-- ===================================================== --}}
{{-- COLLECTION PRODUCTS --}}
{{-- ===================================================== --}}
<section class="lx-collection-products">

    @if(empty($products))
        {{-- EMPTY STATE --}}
        <div class="lx-empty-state">
            <p>Hiện chưa có sản phẩm phù hợp trong danh mục này.</p>
        </div>
    @else
        <div class="lx-product-grid">

            @foreach($products as $product)
                <a
                    href="/{{ $brand }}/product/{{ $product['slug'] ?? $product['product_id'] }}"
                    class="lx-product-card"
                >

                    {{-- PRODUCT IMAGE --}}
                    <div class="lx-product-thumb">
                        <img
                            src="{{ $product['thumb'] }}"
                            alt="{{ $product['name'] }}"
                            loading="lazy">
                    </div>

                    {{-- PRODUCT INFO --}}
                    <div class="lx-product-info">
                        <h3 class="lx-product-name">
                            {{ $product['name'] }}
                        </h3>

                        <div class="lx-product-price">
                            {{ number_format($product['price']) }}₫
                        </div>
                    </div>

                </a>
            @endforeach

        </div>
    @endif

</section>

@endsection

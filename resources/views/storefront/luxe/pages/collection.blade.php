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
<section class="lx-product-section">

    <div class="lx-product-grid">

        @foreach($products as $product)

            <div class="lx-product-card">

                {{-- IMAGE --}}
                <div class="lx-product-media">
                    <a href="{{ route('linxen.product', ['slug' => $product['slug']]) }}">
                        <img src="{{ $product['thumb'] }}"
                             alt="{{ $product['name'] }}"
                             loading="lazy">
                    </a>

                    @if($product['sale_percent'])
                        <span class="lx-sale-badge">
                            -{{ $product['sale_percent'] }}%
                        </span>
                    @endif
                </div>

                {{-- NAME --}}
                <div class="lx-product-head">
                    <span class="lx-tag lx-tag-best">{{ $product['tag'] }}</span>
                    <p class="lx-product-name">{{ $product['name'] }}</p>
                </div>

                {{-- PRICE --}}
                @php
                    $salePrice = round(
                        $product['price'] * (100 - $product['sale_percent']) / 100
                    );
                @endphp

                <div class="lx-product-price-wrap">
                    <span class="lx-price-sale">
                        {{ number_format($salePrice) }}₫
                    </span>
                    <span class="lx-price-origin">
                        {{ number_format($product['price']) }}₫
                    </span>
                </div>

                {{-- COLORS + QUICK ORDER --}}
                <div class="lx-product-variants">
                    <div class="lx-product-colors">
                        @foreach($product['colors'] as $i => $color)
                            <span class="lx-color-swatch {{ $color }} {{ $i === 0 ? 'active' : '' }}"></span>
                        @endforeach
                    </div>

                    <a href="{{ route('linxen.product', ['slug' => $product['slug']]) }}"
                       class="lx-quick-order-inline"
                       aria-label="Đặt hàng">
                        🛒
                    </a>
                </div>

                {{-- STATUS --}}
                <div class="lx-product-tags">
                    <span class="lx-tag lx-tag-stock">✔ Còn hàng</span>
                </div>

            </div>

        @endforeach

    </div>

    {{-- PAGINATION --}}
    <div class="lx-pagination">
        {{ $products->links('storefront.luxe.partials.pagination') }}
    </div>

</section>


@endsection

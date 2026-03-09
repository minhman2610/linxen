{{-- ===================================================== --}}
{{-- FEATURED PRODUCTS – LIN XÉN STYLE --}}
{{-- ===================================================== --}}

<div class="lx-product-grid">

@foreach($home['featured_products'] as $product)

@php

$slug = Str::slug($product['name']).'-'.$product['product_id'];
$thumb = $product['media']['thumb_mobile'];
$price = (float)$product['price'];

$colors = $product['colors'] ?? [];
$hasColors = count($colors) > 1;

$stock = $product['available'] ?? null;

@endphp


<div class="lx-product-card">

    {{-- IMAGE --}}
    <div class="lx-product-media">

        <a href="{{ route('linxen.product',['slug'=>$slug]) }}">

            <img
                src="{{ $thumb }}"
                alt="{{ $product['name'] }}"
                loading="lazy"
            >

        </a>

        {{-- BADGE --}}
        <div class="lx-badge">

            <span>✨ Bestseller</span>

        </div>

    </div>



    {{-- BODY --}}
    <div class="lx-product-body">

        {{-- NAME --}}
        <h3 class="lx-product-name">

            <a href="{{ route('linxen.product',['slug'=>$slug]) }}">
                {{ $product['name'] }}
            </a>

        </h3>



        {{-- PRICE --}}
        <div class="lx-product-price">

            {{ number_format($price) }}₫

        </div>



        {{-- COLORS --}}
        @if($hasColors)

        <div class="lx-product-colors">

            @foreach($colors as $color)

            <span class="lx-color-dot"></span>

            @endforeach

        </div>

        @endif



        {{-- STOCK --}}
        @if($stock && $stock < 8)

        <div class="lx-product-stock">

            Chỉ còn <strong>{{ $stock }}</strong> sản phẩm

        </div>

        @endif



        {{-- CTA --}}
        <a
            href="{{ route('linxen.product',['slug'=>$slug]) }}"
            class="lx-product-btn"
        >
            Xem chi tiết
        </a>

    </div>

</div>

@endforeach

</div>



{{-- VIEW MORE --}}
<div class="lx-product-more">

<a href="/collections/vay" class="lx-more-btn">

Khám phá thêm mẫu →

</a>

</div>
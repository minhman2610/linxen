@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- COLLECTION HERO --}}
{{-- ===================================================== --}}
<section class="lx-category-hero {{ empty($collection['hero']) ? 'no-hero' : '' }}">

    @if(!empty($collection['hero']))
        <img
            src="{{ $collection['hero'] }}"
            alt="{{ $collection['name'] ?? 'LIN XÉN' }}"
            loading="eager"
            fetchpriority="high"
            width="1600"
            height="600">
    @endif

    <div class="lx-category-hero-text">

        <h1>{{ $collection['name'] ?? 'Bộ sưu tập LIN XÉN' }}</h1>

        <p class="lx-category-desc">
            {{ $collection['description'] ?? 'Thiết kế nữ tính – sang trọng – dễ mặc.' }}
        </p>

    </div>

</section>


{{-- ===================================================== --}}
{{-- SALE BANNER (TĂNG ĐỘNG LỰC MUA) --}}
{{-- ===================================================== --}}
@if(request()->is('collections/sale'))
<section class="lx-sale-banner">

    🔥 SALE 30-50% – SỐ LƯỢNG CÓ HẠN

</section>
@endif



{{-- ===================================================== --}}
{{-- QUICK FILTER (TỐI ƯU MOBILE) --}}
{{-- ===================================================== --}}
<div class="lx-collection-filter">

    <a href="?sort=best" class="filter-pill">Bán chạy</a>

    <a href="?sort=new" class="filter-pill">Hàng mới</a>

    <a href="?stock=1" class="filter-pill">Có sẵn</a>

    <a href="?price=low" class="filter-pill">Giá tốt</a>

</div>



{{-- ===================================================== --}}
{{-- COLLECTION PRODUCTS --}}
{{-- ===================================================== --}}
<section class="lx-product-section">

<div class="lx-product-grid">

@foreach($products as $product)

@php

$price = (float) ($product['price'] ?? 0);

$salePercent = (int) ($product['sale_percent'] ?? 0);

$salePrice = $salePercent > 0
    ? round($price * (100 - $salePercent) / 100)
    : $price;

$stock = $product['available'] ?? 0;

@endphp


<div class="lx-product-card">

{{-- ================================================= --}}
{{-- IMAGE --}}
{{-- ================================================= --}}
<div class="lx-product-media">

<a href="{{ route('linxen.product',['slug'=>$product['slug']]) }}">

<img
class="lx-img lazy"
src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='400'%3E%3Crect width='100%25' height='100%25' fill='%23f3f3f3'/%3E%3C/svg%3E"
data-src="{{ $product['thumb'] }}"
alt="{{ $product['name'] }}"
width="300"
height="400">

</a>


{{-- SALE BADGE --}}
@if($salePercent > 0)

<span class="lx-sale-badge">

-{{ $salePercent }}%

</span>

@endif


{{-- STOCK ALERT --}}
@if($stock > 0 && $stock < 5)

<span class="lx-stock-warning">

Còn {{ $stock }} chiếc

</span>

@endif



{{-- QUICK ORDER --}}
<a
href="{{ route('linxen.product',['slug'=>$product['slug']]) }}"
class="lx-quick-order-float">

<svg viewBox="0 0 24 24" width="16">

<path fill="currentColor"
d="M11 5h2v14h-2zM5 11h14v2H5z"/>

</svg>

</a>

</div>



{{-- ================================================= --}}
{{-- NAME --}}
{{-- ================================================= --}}
<div class="lx-product-head">

<p class="lx-product-name">

{{ $product['name'] }}

</p>

</div>



{{-- ================================================= --}}
{{-- PRICE --}}
{{-- ================================================= --}}
<div class="lx-product-price-wrap">

<span class="lx-price-sale">

{{ number_format($salePrice) }}₫

</span>

@if($salePercent > 0)

<span class="lx-price-origin">

{{ number_format($price) }}₫

</span>

@endif

</div>



{{-- ================================================= --}}
{{-- COLORS --}}
{{-- ================================================= --}}
@if(!empty($product['colors']))

<div class="lx-product-variants">

<div class="lx-product-colors">

@foreach($product['colors'] as $i=>$color)

<span class="lx-color-swatch {{ $color }} {{ $i===0?'active':'' }}"></span>

@endforeach

</div>

</div>

@endif



{{-- ================================================= --}}
{{-- TAGS --}}
{{-- ================================================= --}}
<div class="lx-product-tags">

@if($stock > 0)

<span class="lx-tag lx-tag-stock">

✔ Còn hàng

</span>

@endif


@if(!empty($product['tag']))

<span class="lx-tag lx-tag-best">

{{ $product['tag'] }}

</span>

@endif


@if($salePercent >= 40)

<span class="lx-tag lx-tag-hot">

HOT SALE

</span>

@endif

</div>

</div>

@endforeach

</div>



{{-- ===================================================== --}}
{{-- PAGINATION --}}
{{-- ===================================================== --}}
@if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)

<div class="lx-pagination">

{{ $products->links('storefront.luxe.partials.pagination') }}

</div>

@endif


</section>



{{-- ===================================================== --}}
{{-- STICKY CTA MOBILE --}}
{{-- ===================================================== --}}
<div class="lx-sticky-sale">

🔥 Xem sản phẩm SALE HOT

<a href="/collections/sale">

MUA NGAY

</a>

</div>



{{-- ===================================================== --}}
{{-- LAZY LOAD --}}
{{-- ===================================================== --}}
<script>

document.addEventListener('DOMContentLoaded',()=>{

const images=document.querySelectorAll('img.lazy')

if(!('IntersectionObserver'in window)){
images.forEach(img=>img.src=img.dataset.src)
return
}

const observer=new IntersectionObserver(entries=>{

entries.forEach(entry=>{

if(!entry.isIntersecting)return

const img=entry.target

img.src=img.dataset.src

observer.unobserve(img)

})

},{rootMargin:'200px'})

images.forEach(img=>observer.observe(img))

})

</script>

@endsection
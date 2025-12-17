@extends('storefront.luxe.layouts.master')

@section('content')

{{-- ===================================================== --}}
{{-- 🟨 HERO SECTION (VIDEO / IMAGE) --}}
{{-- ===================================================== --}}
@if(!empty($home['hero']))
<section class="lx-hero">

    @if(($home['hero']['type'] ?? null) === 'video')
        <div class="lx-hero-video-wrapper">
            <video class="lx-hero-video" autoplay muted playsinline loop>
                <source src="{{ $home['hero']['src'] }}" type="video/mp4">
            </video>
        </div>
    @else
        <div class="lx-hero-image"
             style="background-image:url('{{ $home['hero']['src'] }}')">
        </div>
    @endif

</section>
@endif


{{-- ===================================================== --}}
{{-- 👗 FEATURED PRODUCTS --}}
{{-- ===================================================== --}}
@if(!empty($home['featured_products']))
<section class="lx-product-section">

    <div class="lx-section-header">
        <h2 class="lx-section-title">GỢI Ý CHO BẠN 1235ss</h2>
        <a href="{{ route('linxen.collection', ['slug' => 'all']) }}"
           class="lx-section-link">
            Xem tất cả
        </a>
    </div>

    <div class="lx-product-grid">
        @foreach($home['featured_products'] as $product)
            <a href="{{ route('linxen.product', ['slug' => $product['code']]) }}"
               class="lx-product-card">

                <div class="lx-product-image">
                    <img
                        src="{{ $product['thumb_url'] }}"
                        alt="{{ $product['name'] }}"
                        loading="lazy"
                    >
                </div>

                <div class="lx-product-info">
                    <p class="lx-product-name">{{ $product['name'] }}</p>
                    <p class="lx-product-price">
                        {{ number_format($product['price']) }}₫
                    </p>
                </div>

            </a>
        @endforeach
    </div>

</section>
@endif

@endsection

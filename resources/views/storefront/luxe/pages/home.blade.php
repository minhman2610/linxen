@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- 👗 FEATURED PRODUCTS --}}
{{-- ===================================================== --}}
@if(!empty($home['featured_products']) && is_array($home['featured_products']))
<section class="lx-product-section">

    <div class="lx-section-header">
        <h2 class="lx-section-title">GỢI Ý CHO BẠN</h2>
        <a href="{{ route('linxen.collection', ['slug' => 'all']) }}"
           class="lx-section-link">
            Xem tất cả
        </a>
    </div>

    <div class="lx-product-grid">
        @foreach($home['featured_products'] as $product)

            @continue(empty($product['code']) || empty($product['thumb_url']))

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

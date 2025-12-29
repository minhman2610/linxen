@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- CATEGORY HERO (LCP IMAGE) --}}
{{-- ===================================================== --}}
<section class="lx-category-hero {{ empty($collection['hero']) ? 'no-hero' : '' }}">

    @if(!empty($collection['hero']))
        <img
            src="{{ $collection['hero'] }}"
            alt="{{ $collection['name'] }} LIN XÉN"
            loading="eager"
            fetchpriority="high"
            width="1600"
            height="600">
    @endif

    <div class="lx-category-hero-text">
        <h1>{{ $collection['name'] }}</h1>

        <p class="lx-category-desc {{ empty($collection['description']) ? 'muted' : '' }}">
            {{ $collection['description']
                ?? 'Khám phá các thiết kế nữ tính, tinh tế và dễ mặc từ LIN XÉN.' }}
        </p>
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
        <img
            class="lx-img lazy"
            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='400'%3E%3Crect width='100%25' height='100%25' fill='%23f3f3f3'/%3E%3C/svg%3E"
            data-src="{{ $product['thumb'] }}"
            alt="{{ $product['name'] }}"
            width="300"
            height="400">
    </a>

    {{-- SALE --}}
    @if($product['sale_percent'])
        <span class="lx-sale-badge">
            -{{ $product['sale_percent'] }}%
        </span>
    @endif

    {{-- QUICK ORDER --}}
    <a href="{{ route('linxen.product', ['slug' => $product['slug']]) }}"
       class="lx-quick-order-float"
       aria-label="Đặt hàng">
        🛒
    </a>

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

{{-- ===================================================== --}}
{{-- IMAGE LAZY LOAD SCRIPT (NON-BLOCKING) --}}
{{-- ===================================================== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    const images = document.querySelectorAll('img.lazy');

    if (!('IntersectionObserver' in window)) {
        images.forEach(img => img.src = img.dataset.src);
        return;
    }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
        });
    }, {
        rootMargin: '200px 0px',
        threshold: 0.01
    });

    images.forEach(img => observer.observe(img));
});
</script>

@endsection

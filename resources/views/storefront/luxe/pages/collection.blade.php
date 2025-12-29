@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- COLLECTION HERO --}}
{{-- ===================================================== --}}
@if (!empty($collection['hero']))
<section class="lx-collection-hero">
    <img
        src="{{ $collection['hero'] }}"
        alt="{{ $collection['name'] ?? 'LIN XÉN' }}"
        loading="lazy">
</section>
@endif

{{-- ===================================================== --}}
{{-- COLLECTION HEADER --}}
{{-- ===================================================== --}}
<section class="lx-collection-head">
    <h1 class="lx-collection-title">
        {{ $collection['name'] ?? 'Bộ sưu tập' }}
    </h1>

    @if (!empty($collection['description']))
        <p class="lx-collection-desc">
            {{ $collection['description'] }}
        </p>
    @endif
</section>

{{-- ===================================================== --}}
{{-- COLLECTION PRODUCTS --}}
{{-- ===================================================== --}}
<section class="lx-collection-products">

    @if ($products->count())

    <div class="lx-product-grid">

        @foreach ($products as $product)

            @php
                // =================================================
                // 🛡 PHÒNG THỦ DỮ LIỆU (CẤP CUỐI)
                // =================================================
                $name = is_string($product['name'] ?? null)
                    ? $product['name']
                    : '';

                $slug = is_string($product['slug'] ?? null)
                    ? $product['slug']
                    : null;

                // ✅ thumb LUÔN là string hợp lệ
                $thumb = is_string($product['thumb'] ?? null) && $product['thumb'] !== ''
                    ? $product['thumb']
                    : asset('themes/luxe/assets/images/placeholder/product.webp');

                $price = is_numeric($product['price'] ?? null)
                    ? (float) $product['price']
                    : 0;

                $salePercent = is_numeric($product['sale_percent'] ?? null)
                    ? (int) $product['sale_percent']
                    : 0;

                $salePrice = $salePercent > 0
                    ? round($price * (100 - $salePercent) / 100)
                    : $price;

                $available = (int) ($product['available'] ?? 0);

                $colors = is_array($product['colors'] ?? null)
                    ? $product['colors']
                    : [];

                $tag = is_string($product['tag'] ?? null)
                    ? $product['tag']
                    : null;
            @endphp

            <div class="lx-product-card">

                {{-- IMAGE --}}
                <div class="lx-product-media">
                    <a href="{{ $slug ? route('linxen.product', ['slug' => $slug]) : 'javascript:void(0)' }}">

                        <img
                            class="lx-img lazy"
                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='400'%3E%3Crect width='100%25' height='100%25' fill='%23f3f3f3'/%3E%3C/svg%3E"
                            data-src="{{ $thumb }}"
                            alt="{{ $name ?: 'LIN XÉN' }}"
                            width="300"
                            height="400"
                            loading="lazy">

                        @if ($tag)
                            <span class="lx-product-tag">{{ $tag }}</span>
                        @endif

                    </a>
                </div>

                {{-- INFO --}}
                <div class="lx-product-info">

                    <h3 class="lx-product-name">
                        <a href="{{ $slug ? route('linxen.product', ['slug' => $slug]) : 'javascript:void(0)' }}">
                            {{ $name }}
                        </a>
                    </h3>

                    {{-- PRICE --}}
                    <div class="lx-product-price">
                        @if ($salePercent > 0)
                            <span class="lx-price-sale">
                                {{ number_format($salePrice) }}đ
                            </span>
                            <span class="lx-price-origin">
                                {{ number_format($price) }}đ
                            </span>
                        @else
                            <span class="lx-price-normal">
                                {{ number_format($price) }}đ
                            </span>
                        @endif
                    </div>

                    {{-- COLORS --}}
                    @if (count($colors))
                        <div class="lx-product-colors">
                            @foreach ($colors as $color)
                                <span
                                    class="lx-color-dot"
                                    style="background-color: {{ $color }}">
                                </span>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>

        @endforeach

    </div>

    {{-- PAGINATION --}}
    <div class="lx-collection-pagination">
        {{ $products->links('storefront.luxe.partials.pagination') }}
    </div>

    @else

        <div class="lx-empty">
            <p>Không có sản phẩm phù hợp.</p>
        </div>

    @endif

</section>

{{-- ===================================================== --}}
{{-- IMAGE LAZY LOAD SCRIPT --}}
{{-- ===================================================== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    const images = document.querySelectorAll('img.lazy');

    if (!('IntersectionObserver' in window)) {
        images.forEach(img => {
            if (img.dataset.src) img.src = img.dataset.src;
        });
        return;
    }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            const img = entry.target;
            if (img.dataset.src) img.src = img.dataset.src;
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

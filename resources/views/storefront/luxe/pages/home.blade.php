@extends('storefront.luxe.layouts.master')

@section('content')

    {{-- 3) HERO VIDEO --}}
<section class="lx-hero">

    <div class="lx-hero-video-wrapper">
        <video class="lx-hero-video" autoplay muted playsinline loop>
            <source src="{{ asset('themes/luxe/assets/videos/ccm.mp4') }}" type="video/mp4">
        </video>
    </div>

    

</section>


    {{-- 5) FEATURED PRODUCTS --}}
<section class="lx-product-section">
    <div class="lx-section-header">
        <h2 class="lx-section-title">GỢI Ý CHO BẠN</h2>
        <a href="/linxen/c/all" class="lx-section-link">Xem tất cả</a>
    </div>

    <div class="lx-product-grid">
    @foreach($products as $product)
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
                <p class="lx-product-price">{{ number_format($product['price']) }}₫</p>
            </div>

        </a>
    @endforeach
</div>

</section>


@endsection
<style>
    /* HERO SECTION */
.lx-hero {
    position: relative;
    width: 100%;
    height: 80vh; /* anh có thể chỉnh 70–90vh tùy ý */
    overflow: hidden;
}

/* VIDEO WRAPPER */
.lx-hero-video-wrapper {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* VIDEO FULLSCREEN */
.lx-hero-video {
    width: 100%;
    height: 100%;
    object-fit: cover; /* giúp video full màn như reel */
    object-position: center;
}

/* TEXT OVERLAY */
.lx-hero-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    z-index: 10;
    color: #fff;
}

.lx-hero-text h1 {
    font-size: 32px;
    font-weight: 700;
    letter-spacing: 1px;
    text-shadow: 0px 4px 8px rgba(0,0,0,0.4);
}

.lx-btn-primary {
    margin-top: 18px;
}

/* MOBILE OPTIMIZE */
@media (max-width: 768px) {
    .lx-hero {
        height: 70vh;
    }

    .lx-hero-text h1 {
        font-size: 24px;
        line-height: 1.3;
    }
}

</style>
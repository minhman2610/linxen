@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- 1️⃣ HERO ĐỊNH VỊ (VIDEO + BRAND MESSAGE) --}}
{{-- ===================================================== --}}
<section class="lx-hero">
    <div class="lx-hero-video-wrapper">
    <video
        class="lx-hero-video"
        autoplay
        muted
        loop
        playsinline
        preload="metadata"
        poster="/themes/luxe/assets/images/home/hero-poster.webp"
    >
        <source src="/themes/luxe/assets/images/home/herovideo3.mp4" type="video/mp4">
    </video>
</div>


    <!-- <div class="lx-hero-text">
        <h1>
            Váy đẹp tinh giản<br>
            cho nhịp sống hiện đại
        </h1>
        <p class="lx-hero-sub">
            LIN XÉN – Thanh lịch, dễ mặc, không lỗi mốt
        </p>
        <a href="{{ route('linxen.collection', ['slug' => 'all']) }}"
           class="lx-btn-primary">
            Khám phá bộ sưu tập
        </a>
    </div> -->
</section>

{{-- ===================================================== --}}
{{-- FLASH SALE – LIN XÉN (FILTER BY SKU CHA – ROADMAP) --}}
{{-- ===================================================== --}}
<section class="lx-flash-sale">

    @php
        /**
         * 🔥 ROADMAP FLASH SALE – SKU CHA => GIÁ BÁN
         * Source of truth: product['code']
         */
        $flashSaleRoadmap = [
            'SP14535165' => 299000,
            'SP14530509' => 458000,
            'SP14529951' => 399000,
            'SP14527951' => 356000,
            'SP14527939' => 385000,
            'SP14530151' => 399000,
        ];
    @endphp

    {{-- HEAD --}}
    <div class="lx-flash-sale-head">
        <h3 class="lx-flash-sale-title">
            ⚡ FLASH SALE
        </h3>

        <div class="lx-flash-sale-countdown" data-end-time="2026-02-03 23:59:59">
            <div class="lx-countdown-item">
                <span class="num" data-days>00</span>
                <span class="label">Ngày</span>
            </div>
            <div class="lx-countdown-item">
                <span class="num" data-hours>00</span>
                <span class="label">Giờ</span>
            </div>
            <div class="lx-countdown-item">
                <span class="num" data-minutes>00</span>
                <span class="label">Phút</span>
            </div>
            <div class="lx-countdown-item">
                <span class="num" data-seconds>00</span>
                <span class="label">Giây</span>
            </div>
        </div>
    </div>

    {{-- LIST – TRƯỢT NGANG 1 HÀNG --}}
    <div class="lx-flash-sale-scroll">
        <div class="lx-product-row">

            @php $hasFlashSale = false; @endphp

            @foreach($home['featured_products'] as $product)

                @php
                    /**
                     * 🔑 SKU CHA – SOURCE OF TRUTH
                     */
                    $code = $product['code'] ?? null;
                @endphp

                {{-- ❌ Không nằm trong roadmap --}}
                @continue(!$code || !isset($flashSaleRoadmap[$code]))

                @continue(
                    empty($product['product_id'])
                    || empty($product['name'])
                    || empty($product['media']['thumb_mobile'])
                )

                @php
                    $hasFlashSale = true;

                    $slug  = \Illuminate\Support\Str::slug($product['name'])
                            . '-' . $product['product_id'];

                    $thumb = $product['media']['thumb_mobile'];

                    $originPrice = (float) $product['price'];
                    $salePrice   = (float) $flashSaleRoadmap[$code];

                    $salePercent = $originPrice > 0
                        ? round(100 - ($salePrice / $originPrice * 100))
                        : 0;

                    /**
                     * 🎨 RANDOM 3 MÀU – UI ONLY
                     */
                    $colorPool = [
                        'black',
                        'white',
                        'beige',
                        'brown',
                        'red',
                        'blue',
                        'navy',
                        'olive'
                    ];

                    shuffle($colorPool);
                    $productColors = array_slice($colorPool, 0, 3);
                @endphp

                <div class="lx-product-card">

                    {{-- IMAGE --}}
                    <div class="lx-product-media">
                        <a href="{{ route('linxen.product', ['slug' => $slug]) }}">
                            <img src="{{ $thumb }}"
                                 alt="{{ $product['name'] }}"
                                 loading="lazy">
                        </a>

                        <span class="lx-sale-badge">
                            -{{ $salePercent }}%
                        </span>
                    </div>

                    {{-- NAME --}}
                    <div class="lx-product-head">
                        <p class="lx-product-name one-line">
                            {{ $product['name'] }}
                        </p>
                    </div>

                    {{-- PRICE --}}
                    <div class="lx-product-price-wrap">
                        <span class="lx-price-sale">
                            {{ number_format($salePrice) }}₫
                        </span>
                        <span class="lx-price-origin">
                            {{ number_format($originPrice) }}₫
                        </span>
                    </div>

                </div>

            @endforeach

            {{-- FALLBACK --}}
            @if(!$hasFlashSale)
                <div class="lx-flash-sale-empty">
                    <p class="text-muted">
                        Hiện chưa có sản phẩm Flash Sale
                    </p>
                </div>
            @endif

        </div>
    </div>

</section>





{{-- ===================================================== --}}
{{-- CATEGORY PILLS – LIN XÉN (SEO OPTIMIZED) --}}
{{-- ===================================================== --}}
<section class="lx-category-pills refined"
         aria-labelledby="lx-category-featured-title">

    <div class="lx-category-head">
        <h2 id="lx-category-featured-title" class="lx-category-title">
            Danh mục nổi bật
        </h2>
        <span class="lx-category-hint">
            Gợi ý theo hoàn cảnh sử dụng
        </span>
    </div>

    <div class="lx-category-scroll" role="navigation" aria-label="Danh mục sử dụng">

        {{-- Đi làm --}}
        <a href="{{ route('linxen.collection', ['slug' => 'di-lam']) }}"
           class="lx-category-pill"
           title="Trang phục đi làm nữ LIN XÉN – Thanh lịch, dễ mặc">

            <div class="lx-category-thumb">
                <img
                    src="/themes/luxe/assets/images/categories/di-lam.webp"
                    alt="Trang phục nữ đi làm LIN XÉN"
                    loading="lazy">
            </div>

            <span class="lx-category-name">Đi làm</span>
        </a>

        {{-- Dạo phố --}}
        <a href="{{ route('linxen.collection', ['slug' => 'dao-pho']) }}"
           class="lx-category-pill"
           title="Trang phục dạo phố nữ LIN XÉN – Trẻ trung, thoải mái">

            <div class="lx-category-thumb">
                <img
                    src="/themes/luxe/assets/images/categories/dao-pho.webp"
                    alt="Trang phục nữ dạo phố LIN XÉN"
                    loading="lazy">
            </div>

            <span class="lx-category-name">Dạo phố</span>
        </a>

        {{-- Dự tiệc --}}
        <a href="{{ route('linxen.collection', ['slug' => 'du-tiec']) }}"
           class="lx-category-pill"
           title="Váy dự tiệc nữ LIN XÉN – Tinh tế, sang trọng">

            <div class="lx-category-thumb">
                <img
                    src="/themes/luxe/assets/images/categories/du-tiec.webp"
                    alt="Váy nữ dự tiệc LIN XÉN"
                    loading="lazy">
            </div>

            <span class="lx-category-name">Dự tiệc</span>
        </a>

        {{-- Thoải mái --}}
        <a href="{{ route('linxen.collection', ['slug' => 'thoai-mai']) }}"
           class="lx-category-pill"
           title="Trang phục mặc thoải mái nữ LIN XÉN – Nhẹ nhàng, dễ chịu">

            <div class="lx-category-thumb">
                <img
                    src="/themes/luxe/assets/images/categories/thoai-mai.webp"
                    alt="Trang phục nữ mặc thoải mái LIN XÉN"
                    loading="lazy">
            </div>

            <span class="lx-category-name">Thoải mái</span>
        </a>

        {{-- Thiết kế mới --}}
        <a href="{{ route('linxen.collection', ['slug' => 'thiet-ke-moi']) }}"
           class="lx-category-pill highlight"
           title="Thiết kế mới LIN XÉN – Bộ sưu tập mới nhất">

            <div class="lx-category-thumb">
                <img
                    src="/themes/luxe/assets/images/categories/new.webp"
                    alt="Thiết kế mới nhất LIN XÉN"
                    loading="lazy">
            </div>

            <span class="lx-category-name">Mới</span>
        </a>

    </div>
</section>
{{-- ===================================================== --}}
{{-- PRODUCT TYPE CATEGORIES – SEO OPTIMIZED --}}
{{-- ===================================================== --}}
<section class="lx-product-type-categories"
         aria-labelledby="lx-product-type-title">

    <div class="lx-product-type-head">
        <h2 id="lx-product-type-title" class="lx-product-type-title">
            Loại sản phẩm
        </h2>
        <span class="lx-product-type-hint">
            Phân loại theo kiểu dáng
        </span>
    </div>

    <div class="lx-product-type-scroll"
         role="navigation"
         aria-label="Loại sản phẩm nữ LIN XÉN">

        <a href="{{ route('linxen.collection', ['slug' => 'vay-basic']) }}"
           class="lx-product-type-card"
           title="Váy basic nữ LIN XÉN – Dễ mặc, không lỗi mốt">

            <img
                src="/themes/luxe/assets/images/categories/vay-basic.webp"
                alt="Váy basic nữ LIN XÉN"
                loading="lazy">

            <span>Váy basic</span>
        </a>

        <a href="{{ route('linxen.collection', ['slug' => 'vay-thiet-ke']) }}"
           class="lx-product-type-card highlight"
           title="Váy thiết kế nữ LIN XÉN – Form dáng tinh tế">

            <img
                src="/themes/luxe/assets/images/categories/vay-thiet-ke.webp"
                alt="Váy thiết kế nữ LIN XÉN"
                loading="lazy">

            <span>Váy thiết kế</span>
        </a>

        <a href="{{ route('linxen.collection', ['slug' => 'vay-body']) }}"
           class="lx-product-type-card"
           title="Váy body nữ LIN XÉN – Tôn dáng, quyến rũ">

            <img
                src="/themes/luxe/assets/images/categories/vay-body.webp"
                alt="Váy body nữ LIN XÉN"
                loading="lazy">

            <span>Váy body</span>
        </a>

        <a href="{{ route('linxen.collection', ['slug' => 'ao-da']) }}"
           class="lx-product-type-card"
           title="Áo dạ nữ LIN XÉN – Ấm áp, thanh lịch">

            <img
                src="/themes/luxe/assets/images/categories/ao-da.webp"
                alt="Áo dạ nữ LIN XÉN"
                loading="lazy">

            <span>Áo dạ</span>
        </a>

        <a href="{{ route('linxen.collection', ['slug' => 'set-bo']) }}"
           class="lx-product-type-card"
           title="Set bộ nữ LIN XÉN – Phối sẵn, mặc đẹp ngay">

            <img
                src="/themes/luxe/assets/images/categories/set-bo.webp"
                alt="Set bộ nữ LIN XÉN"
                loading="lazy">

            <span>Set bộ</span>
        </a>

    </div>
</section>



{{-- FEATURED PRODUCTS --}}
@include('storefront.luxe.components.home.featured-products', [
    'products' => $home['featured_products'] ?? []
])

{{-- ===================================================== --}}
{{-- 3️⃣ GIÁ TRỊ CỐT LÕI – ẢNH THẬT VS ẢNH BÁN HÀNG --}}
{{-- ===================================================== --}}
<section class="lx-trust-visual">

    <h3 class="lx-trust-title framed">
    Ảnh bán hàng ≈ Ảnh thực tế
</h3>


<p class="lx-trust-desc">
    LIN XÉN <strong>cam kết hình ảnh sát thực tế</strong> –  
    khách nhận hàng <strong>không bị “vỡ mộng”</strong> khi mở hộp.
</p>


    <div class="lx-trust-compare">

        {{-- ẢNH BÁN HÀNG --}}
        <div class="lx-trust-image">
            <img
                src="/themes/luxe/assets/images/home/anh_shop1.webp"
                alt="Ảnh bán hàng LIN XÉN"
                loading="lazy"
            >
            <span class="lx-trust-label">Ảnh bán hàng</span>
        </div>

        {{-- ARROW --}}
        <div class="lx-trust-arrow">
            →
        </div>

        {{-- ẢNH THỰC TẾ --}}
        <div class="lx-trust-image">
            <img
                src="/themes/luxe/assets/images/home/anh_real1.webp"
                alt="Ảnh thực tế LIN XÉN"
                loading="lazy"
            >
            <span class="lx-trust-label real">Ảnh thực tế</span>
        </div>

    </div>

    <div class="lx-trust-compare">

        {{-- ẢNH BÁN HÀNG --}}
        <div class="lx-trust-image">
            <img
                src="/themes/luxe/assets/images/home/anh_shop2.webp"
                alt="Ảnh bán hàng LIN XÉN"
                loading="lazy"
            >
            <span class="lx-trust-label">Ảnh bán hàng</span>
        </div>

        {{-- ARROW --}}
        <div class="lx-trust-arrow">
            →
        </div>

        {{-- ẢNH THỰC TẾ --}}
        <div class="lx-trust-image">
            <img
                src="/themes/luxe/assets/images/home/anh_real2.webp"
                alt="Ảnh thực tế LIN XÉN"
                loading="lazy"
            >
            <span class="lx-trust-label real">Ảnh thực tế</span>
        </div>

    </div>

    <div class="lx-trust-compare">

        {{-- ẢNH BÁN HÀNG --}}
        <div class="lx-trust-image">
            <img
                src="/themes/luxe/assets/images/home/anh_shop3.webp"
                alt="Ảnh bán hàng LIN XÉN"
                loading="lazy"
            >
            <span class="lx-trust-label">Ảnh bán hàng</span>
        </div>

        {{-- ARROW --}}
        <div class="lx-trust-arrow">
            →
        </div>

        {{-- ẢNH THỰC TẾ --}}
        <div class="lx-trust-image">
            <img
                src="/themes/luxe/assets/images/home/anh_real3.webp"
                alt="Ảnh thực tế LIN XÉN"
                loading="lazy"
            >
            <span class="lx-trust-label real">Ảnh thực tế</span>
        </div>

    </div>

</section>

{{-- ===================================================== --}}
{{-- EDITORIAL IMAGE – LIN XÉN --}}
{{-- ===================================================== --}}
<section class="lx-editorial">
    <img
        src="/themes/luxe/assets/images/home/anh_thudo.webp"
        alt="LIN XÉN – Timeless Style"
        loading="lazy"
    >

    
</section>

{{-- ===================================================== --}}
{{-- TRY AT HOME – PREMIUM FLOW (REFINED) --}}
{{-- ===================================================== --}}
<section class="lx-try-flow refined">

    <h3 class="lx-try-title">
        Thử đồ tại nhà<br>
        <span>Rồi hãy quyết định</span>
    </h3>

    <div class="lx-try-steps">

        <div class="lx-try-step">
            <div class="lx-step-icon">📦</div>
            <p>
                Shipper giao đến nhà<br>
                <small>Nhận hàng tận tay</small>
            </p>
        </div>

        <div class="lx-arrow"></div>

        <div class="lx-try-step">
            <div class="lx-step-icon">⏳</div>
            <p>
                Yêu cầu shipper chờ<br>
                <small>Trong thời gian mặc thử</small>
            </p>
        </div>

        <div class="lx-arrow"></div>

        <div class="lx-try-step">
            <div class="lx-step-icon">👗</div>
            <p>
                Mặc thử & cảm nhận<br>
                <small>Form dáng · chất vải</small>
            </p>
        </div>

        <div class="lx-arrow"></div>

        <div class="lx-try-step highlight">
            <div class="lx-step-icon">❤️</div>
            <p>
                Ưng thì thanh toán<br>
                <small>Không ưng chỉ trả phí ship</small>
            </p>
        </div>

    </div>

    <div class="lx-try-trust">
        Không áp lực mua · Không cần giải thích ·
        <strong>Quyết định hoàn toàn ở bạn</strong>
    </div>

</section>

<!-- ============================================= -->
<!-- EXCHANGE POSTER – LIN XÉN -->
<!-- ============================================= -->
<section class="lx-exchange-poster">

    <img
        src="/themes/luxe/assets/images/home/anh_doihang.webp"
        alt="LIN XÉN – Đổi hàng thật dễ dàng"
        loading="lazy"
    >

</section>

<section class="lx-exchange">

    <h3 class="lx-exchange-title">
        Đổi hàng trong 15 ngày<br>
        <span>Với bất kỳ lý do gì</span>
    </h3>

    <p class="lx-exchange-desc">
        LIN XÉN hiểu rằng đôi khi bạn cần thêm thời gian để quyết định.  
        Vì vậy, chúng tôi cho phép <strong>đổi hàng trong vòng 15 ngày</strong> kể từ khi nhận.
    </p>

    <div class="lx-exchange-steps">
        <div class="lx-ex-step">
    <span>1</span>
    <p>
        Tạo yêu cầu đổi hàng<br>
        <small>trên website / fanpage / hotline</small>
    </p>
</div>


        <div class="lx-ex-step">
            <span>2</span>
            <p>Shipper đến tận nhà<br><small>giao hàng mới</small></p>
        </div>

        <div class="lx-ex-step highlight">
            <span>3</span>
            <p>Thu hồi hàng cũ<br><small>bạn không cần mang đi đâu</small></p>
        </div>
    </div>

    <div class="lx-exchange-trust">
        Bạn không cần lo lắng –  
        <strong>LIN XÉN chủ động xử lý việc đổi hàng từ A đến Z</strong>.
    </div>

</section>


{{-- ===================================================== --}}
{{-- 🎨 STYLE — FIX HERO VIDEO BLOCKING HEADER --}}
{{-- ===================================================== --}}
<style>
/* HERO */
.lx-hero {
    position: relative;
    width: 100%;
    height: 80vh;
    overflow: hidden;
    z-index: 1;
}

/* VIDEO LAYER */
.lx-hero-video-wrapper {
    position: absolute;
    inset: 0;
    z-index: 1;
}

.lx-hero-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none; /* 🔑 FIX MENU CLICK */
}

/* HERO TEXT */
.lx-hero-text {
    position: relative;
    z-index: 2;
    text-align: center;
    color: #fff;

    top: 50%;
    transform: translateY(-50%);
}

.lx-hero-text h1 {
    font-size: 34px;
    line-height: 1.3;
    margin-bottom: 12px;
}

.lx-hero-sub {
    opacity: .9;
    margin-bottom: 20px;
}

/* SECTION */
.lx-section-title {
    text-align: center;
    font-size: 26px;
    margin-bottom: 6px;
}

.lx-section-desc {
    text-align: center;
    color: #777;
    margin-bottom: 24px;
}

/* TRUST / LOOK */
.lx-trust-grid,
.lx-look-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 20px;
    padding: 40px 20px;
    text-align: center;
}

/* CTA */
.lx-final-cta {
    background: #000;
    color: #fff;
    padding: 60px 20px;
    text-align: center;
}

/* MOBILE */
@media (max-width: 768px) {
    .lx-hero { height: 70vh; }
    .lx-hero-text h1 { font-size: 24px; }
}

/* =========================================
   FINAL FIX — HERO NOT BLOCK HEADER CLICKS
========================================= */

/* Disable pointer events for hero background */
.lx-hero,
.lx-hero-video-wrapper,
.lx-hero-video {
    pointer-events: none;
}

/* Enable pointer events for hero content */
.lx-hero-text,
.lx-hero-text * {
    pointer-events: auto;
}

/* Ensure header is isolated and clickable */
.lx-header {
    isolation: isolate;
    pointer-events: auto;
    z-index: 200;
}
/* ===============================
   LIN XÉN – Messenger Floating
================================ */
.lx-messenger-btn {
    position: fixed;
    right: 18px;
    bottom: 88px; /* tránh đè bottom nav */
    width: 56px;
    height: 56px;
    background: #0084ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 28px rgba(0,0,0,.35);
    z-index: 99999;
}

.lx-messenger-btn svg {
    width: 26px;
    height: 26px;
}

</style>
{{-- themes/luxe/layouts/app.blade.php --}}

<!-- LIN XÉN – Messenger Floating Button -->
<a href="https://m.me/linxen.vn"
   class="lx-messenger-btn"
   target="_blank"
   aria-label="Chat với LIN XÉN trên Messenger">
    <svg viewBox="0 0 24 24">
        <path fill="white"
              d="M12 2C6.48 2 2 6.02 2 11c0 2.89 
                 1.64 5.47 4.22 7.24V22l3.73-2.05
                 c.65.18 1.33.28 2.05.28
                 5.52 0 10-4.02 10-9
                 S17.52 2 12 2z"/>
    </svg>
</a>


@endsection


@push('scripts')
<script src="{{ asset('themes/luxe/assets/js/home.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/home.js')) }}"></script>

@endpush
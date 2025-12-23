@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- 1️⃣ HERO ĐỊNH VỊ (VIDEO + BRAND MESSAGE) --}}
{{-- ===================================================== --}}
<section class="lx-hero">
    <div class="lx-hero-video-wrapper">
        <video class="lx-hero-video" autoplay muted playsinline loop>
            <source src="/themes/luxe/assets/images/home/herovideo1.mp4" type="video/mp4">
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
{{-- CATEGORY PILLS – LIN XÉN (REFINED) --}}
{{-- ===================================================== --}}
<section class="lx-category-pills refined">

    <div class="lx-category-head">
        <h3 class="lx-category-title">Danh mục nổi bật</h3>
        <span class="lx-category-hint">Vuốt ngang để xem</span>
    </div>

    <div class="lx-category-scroll">

        <a href="/collections/di-lam" class="lx-category-pill">
            <div class="lx-category-thumb">
                <img src="/themes/luxe/assets/images/categories/di-lam.webp" alt="Đi làm">
            </div>
            <span class="lx-category-name">Đi làm</span>
        </a>

        <a href="/collections/dao-pho" class="lx-category-pill">
            <div class="lx-category-thumb">
                <img src="/themes/luxe/assets/images/categories/dao-pho.webp" alt="Dạo phố">
            </div>
            <span class="lx-category-name">Dạo phố</span>
        </a>

        <a href="/collections/du-tiec" class="lx-category-pill">
            <div class="lx-category-thumb">
                <img src="/themes/luxe/assets/images/categories/du-tiec.webp" alt="Dự tiệc">
            </div>
            <span class="lx-category-name">Dự tiệc</span>
        </a>

        <a href="/collections/thoai-mai" class="lx-category-pill">
            <div class="lx-category-thumb">
                <img src="/themes/luxe/assets/images/categories/thoai-mai.webp" alt="Thoải mái">
            </div>
            <span class="lx-category-name">Thoải mái</span>
        </a>

        <a href="/collections/thiet-ke-moi" class="lx-category-pill highlight">
            <div class="lx-category-thumb">
                <img src="/themes/luxe/assets/images/categories/new.webp" alt="Thiết kế mới">
            </div>
            <span class="lx-category-name">Mới</span>
        </a>

    </div>

</section>

{{-- ===================================================== --}}
{{-- PRODUCT TYPE CATEGORIES – IMAGE BASED --}}
{{-- ===================================================== --}}
<section class="lx-product-type-categories">

    <div class="lx-product-type-head">
        <h3 class="lx-product-type-title">Loại sản phẩm</h3>
        <span class="lx-product-type-hint">Chọn nhanh theo nhu cầu</span>
    </div>

    <div class="lx-product-type-scroll">

        <a href="/collections/vay-basic" class="lx-product-type-card">
            <img src="/themes/luxe/assets/images/categories/vay-basic.webp" alt="Váy basic">
            <span>Váy basic</span>
        </a>

        <a href="/collections/vay-thiet-ke" class="lx-product-type-card highlight">
            <img src="/themes/luxe/assets/images/categories/vay-thiet-ke.webp" alt="Váy thiết kế">
            <span>Váy thiết kế</span>
        </a>

        <a href="/collections/vay-body" class="lx-product-type-card">
            <img src="/themes/luxe/assets/images/categories/vay-body.webp" alt="Váy body">
            <span>Váy body</span>
        </a>

        <a href="/collections/ao-da" class="lx-product-type-card">
            <img src="/themes/luxe/assets/images/categories/ao-da.webp" alt="Áo dạ">
            <span>Áo dạ</span>
        </a>

        <a href="/collections/set-bo" class="lx-product-type-card">
            <img src="/themes/luxe/assets/images/categories/set-bo.webp" alt="Set bộ">
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




@endsection

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

</style>
{{-- themes/luxe/layouts/app.blade.php --}}
<script src="{{ asset('themes/luxe/assets/js/home.js') }}?v={{ filemtime(public_path('themes/luxe/assets/js/home.js')) }}"></script>

@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- 1️⃣ HERO ĐỊNH VỊ (VIDEO + BRAND MESSAGE) --}}
{{-- ===================================================== --}}
<section class="lx-hero">
    <div class="lx-hero-video-wrapper">
        <video class="lx-hero-video" autoplay muted playsinline loop>
            <source src="{{ asset('themes/luxe/assets/videos/ccm.mp4') }}" type="video/mp4">
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

{{-- FEATURED PRODUCTS --}}
@include('storefront.luxe.components.home.featured-products', [
    'products' => $home['featured_products'] ?? []
])

{{-- ===================================================== --}}
{{-- 3️⃣ GIÁ TRỊ CỐT LÕI – ẢNH THẬT VS ẢNH BÁN HÀNG --}}
{{-- ===================================================== --}}
<section class="lx-trust-visual">

    <h3 class="lx-trust-title">
        Ảnh bán hàng &nbsp;↔&nbsp; Ảnh thực tế
    </h3>

    <p class="lx-trust-desc">
        LIN XÉN cam kết hình ảnh sát thực tế – khách nhận hàng không bị “vỡ mộng”
    </p>

    <div class="lx-trust-compare">

        {{-- ẢNH BÁN HÀNG --}}
        <div class="lx-trust-image">
            <img
                src="{{ asset('images/home/anh_shop1.webp') }}"
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
                src="{{ asset('images/home/anh_real1.webp') }}"
                alt="Ảnh thực tế LIN XÉN"
                loading="lazy"
            >
            <span class="lx-trust-label real">Ảnh thực tế</span>
        </div>

    </div>

</section>
{{-- ===================================================== --}}
{{-- TRY AT HOME – PREMIUM FLOW --}}
{{-- ===================================================== --}}
<section class="lx-tryflow">

    <div class="lx-tryflow-header">
        <h2 class="lx-tryflow-title">
            Thử đồ tại nhà<br>
            <span>Quyết định sau</span>
        </h2>
        <p class="lx-tryflow-sub">
            Không áp lực mua · Không cần giải thích · Chủ động 100%
        </p>
    </div>

    <div class="lx-tryflow-steps">

        <div class="lx-tryflow-step">
            <div class="lx-step-circle">📦</div>
            <h4>Nhận hàng</h4>
            <p>Shipper giao tận tay</p>
        </div>

        <div class="lx-tryflow-line"></div>

        <div class="lx-tryflow-step">
            <div class="lx-step-circle">👗</div>
            <h4>Mặc thử</h4>
            <p>Thoải mái tại nhà</p>
        </div>

        <div class="lx-tryflow-line"></div>

        <div class="lx-tryflow-step">
            <div class="lx-step-circle">✨</div>
            <h4>So gương</h4>
            <p>Cảm nhận form & chất vải</p>
        </div>

        <div class="lx-tryflow-line"></div>

        <div class="lx-tryflow-step highlight">
            <div class="lx-step-circle">❤️</div>
            <h4>Ưng thì nhận</h4>
            <p>Không ưng chỉ trả phí ship</p>
        </div>

    </div>

    <div class="lx-tryflow-footer">
        <strong>LIN XÉN tin rằng:</strong>  
        Bạn cần cảm nhận trang phục trên chính cơ thể mình – không phải qua ảnh.
    </div>

</section>




{{-- ===================================================== --}}
{{-- 4️⃣ PHỐI ĐỒ --}}
{{-- ===================================================== --}}
<section class="lx-lookbook">
    <h2 class="lx-section-title">PHỐI ĐỒ GỢI Ý</h2>
    <p class="lx-section-desc">
        Một chiếc váy – nhiều cách mặc
    </p>

    <div class="lx-look-grid">
        <div class="lx-look-item">Đi làm</div>
        <div class="lx-look-item">Đi chơi</div>
        <div class="lx-look-item">Hẹn hò</div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- 5️⃣ CTA --}}
{{-- ===================================================== --}}
<section class="lx-final-cta">
    <h2>
        Chọn chiếc váy<br>
        phù hợp với bạn hôm nay
    </h2>
    <a href="{{ route('linxen.collection', ['slug' => 'all']) }}"
       class="lx-btn-primary">
        Xem toàn bộ váy
    </a>
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

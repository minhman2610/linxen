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

    <div class="lx-hero-text">
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
    </div>
</section>

{{-- ===================================================== --}}
{{-- 2️⃣ SẢN PHẨM CHỦ LỰC – VÁY --}}
{{-- ===================================================== --}}
@if(!empty($home['featured_products']) && is_array($home['featured_products']))
<section class="lx-product-section">

    <div class="lx-section-header">
        <h2 class="lx-section-title">VÁY BÁN CHẠY</h2>
        <p class="lx-section-desc">
            Những thiết kế được khách hàng LIN XÉN yêu thích nhất
        </p>
        <a href="{{ route('linxen.collection', ['slug' => 'all']) }}"
           class="lx-section-link">
            Xem tất cả
        </a>
    </div>

    <div class="lx-product-grid">
        @foreach($home['featured_products'] as $product)

            @continue(
                empty($product['product_id'])
                || empty($product['name'])
                || (
                    empty($product['thumb_url'])
                    && empty($product['thumb_url_mobile'])
                )
            )

            @php
                $slug = \Illuminate\Support\Str::slug($product['name'])
                        . '-' . $product['product_id'];

                // 📱 Ưu tiên ảnh mobile, fallback về desktop
                $thumb = $product['thumb_url_mobile']
                    ?? $product['thumb_url'];
            @endphp

            <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
               class="lx-product-card">

                <div class="lx-product-image">
                    <img
                        src="{{ $thumb }}"
                        alt="{{ $product['name'] }}"
                        loading="lazy"
                    >
                </div>

                <div class="lx-product-info">
                    <p class="lx-product-name">
                        {{ $product['name'] }}
                    </p>
                    <p class="lx-product-price">
                        {{ number_format($product['price']) }}₫
                    </p>
                </div>

            </a>
        @endforeach
    </div>

</section>
@endif


{{-- ===================================================== --}}
{{-- 3️⃣ GIÁ TRỊ CỐT LÕI – TRUST --}}
{{-- ===================================================== --}}
<section class="lx-trust">
    <div class="lx-trust-grid">
        <div class="lx-trust-item">
            <h4>Thiết kế chọn lọc</h4>
            <p>Form dáng tinh giản, dễ mặc, phù hợp nhiều vóc dáng</p>
        </div>
        <div class="lx-trust-item">
            <h4>Hình ảnh thật</h4>
            <p>Sản phẩm giống hình, không chỉnh sửa quá tay</p>
        </div>
        <div class="lx-trust-item">
            <h4>Đổi trả linh hoạt</h4>
            <p>Hỗ trợ đổi size nếu không vừa</p>
        </div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- 4️⃣ PHỐI ĐỒ / LOOK HOÀN CHỈNH --}}
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
{{-- 5️⃣ VÁY THEO HOÀN CẢNH --}}
{{-- ===================================================== --}}
<section class="lx-context">
    <h2 class="lx-section-title">CHỌN VÁY THEO HOÀN CẢNH</h2>

    <div class="lx-context-grid">
        <a href="#">Đi làm</a>
        <a href="#">Đi chơi</a>
        <a href="#">Dự tiệc</a>
        <a href="#">Du lịch</a>
    </div>
</section>

{{-- ===================================================== --}}
{{-- 6️⃣ SOCIAL PROOF NHẸ --}}
{{-- ===================================================== --}}
<section class="lx-social-proof">
    <p>
        Hơn <strong>10.000+</strong> khách hàng đã chọn LIN XÉN
    </p>
    <p class="lx-social-muted">
        Đánh giá trung bình 4.8★ từ khách hàng mua lại
    </p>
</section>

{{-- ===================================================== --}}
{{-- 7️⃣ CTA CUỐI --}}
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
{{-- 🎨 STYLE (TỐI GIẢN – DỄ MỞ RỘNG) --}}
{{-- ===================================================== --}}
<style>
/* HERO */
.lx-hero {
    position: relative;
    width: 100%;
    height: 80vh;
    overflow: hidden;
}
.lx-hero-video-wrapper,
.lx-hero-video {
    width: 100%;
    height: 100%;
}
.lx-hero-video {
    object-fit: cover;
}
.lx-hero-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #fff;
    z-index: 10;
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
.lx-section-link {
    display: block;
    text-align: center;
    margin-bottom: 30px;
}

/* TRUST / LOOK / CONTEXT */
.lx-trust-grid,
.lx-look-grid,
.lx-context-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 20px;
    padding: 40px 20px;
    text-align: center;
}
.lx-trust-item h4 {
    margin-bottom: 8px;
}

/* SOCIAL */
.lx-social-proof {
    text-align: center;
    padding: 40px 20px;
    background: #f7f7f7;
}
.lx-social-muted {
    color: #777;
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
</style>

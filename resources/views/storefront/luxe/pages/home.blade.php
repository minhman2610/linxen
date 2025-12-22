@extends('storefront.luxe.layouts.app')

@section('content')

{{-- ===================================================== --}}
{{-- 1️⃣ HERO --}}
{{-- ===================================================== --}}
<section class="lx-hero">
    <div class="lx-hero-video-wrapper">
        <video class="lx-hero-video" autoplay muted playsinline loop>
            <source src="{{ asset('themes/luxe/assets/videos/ccm.mp4') }}" type="video/mp4">
        </video>
    </div>
</section>

{{-- ===================================================== --}}
{{-- 2️⃣ SẢN PHẨM CHỦ LỰC – MOBILE FIRST (2 ẢNH ĐỔI NHAU) --}}
{{-- ===================================================== --}}
@if(!empty($home['featured_products']) && is_array($home['featured_products']))
<section class="lx-product-section">

    <div class="lx-section-header">
        <h2 class="lx-section-title">VÁY ĐƯỢC YÊU THÍCH</h2>
        <p class="lx-section-desc">
            Những chiếc váy dễ mặc cho nhịp sống hằng ngày
        </p>
        <a href="{{ route('linxen.collection', ['slug' => 'all']) }}"
           class="lx-section-link">
            Xem tất cả
        </a>
    </div>

    <div class="lx-product-grid">
        @foreach($home['featured_products'] as $product)

            @php
                $slug = \Illuminate\Support\Str::slug($product['name'])
                        . '-' . $product['product_id'];

                // LẤY ĐÚNG 2 ẢNH
                $images = array_slice(
                    $product['media']['images'] ?? [],
                    0,
                    2
                );

                if (count($images) < 2) {
                    $images[1] = $images[0] ?? null;
                }
            @endphp

            <a href="{{ route('linxen.product', ['slug' => $slug]) }}"
               class="lx-product-card">

                <div class="lx-product-media" data-image-switch>

                    @if(!empty($product['tag']))
                        <span class="lx-product-tag">
                            {{ $product['tag'] }}
                        </span>
                    @endif

                    <img src="{{ $images[0] }}"
                         class="is-active"
                         alt="{{ $product['name'] }}"
                         loading="lazy">

                    @if(!empty($images[1]))
                        <img src="{{ $images[1] }}"
                             alt="{{ $product['name'] }}"
                             loading="lazy">
                    @endif
                </div>

                <div class="lx-product-info">

                    @if(!empty($product['colors']))
                        <div class="lx-product-colors">
                            @foreach(array_slice($product['colors'], 0, 3) as $color)
                                <span class="lx-color-dot"
                                      style="background: {{ $color }}"></span>
                            @endforeach
                            @if(count($product['colors']) > 3)
                                <span class="lx-color-more">
                                    +{{ count($product['colors']) - 3 }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <p class="lx-product-name">
                        {{ $product['name'] }}
                    </p>

                    <p class="lx-product-price">
                        {{ number_format($product['price']) }}₫
                    </p>

                    <p class="lx-product-micro">
                        {{ $product['micro_copy'] ?? 'Dễ mặc mỗi ngày' }}
                    </p>
                </div>

            </a>
        @endforeach
    </div>

</section>
@endif

@endsection

{{-- ===================================================== --}}
{{-- 🎨 CSS — PRODUCT IMAGE CHANGE (MOBILE FIRST) --}}
{{-- ===================================================== --}}
<style>
/* GRID */
.lx-product-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    padding: 0 14px;
}

/* CARD */
.lx-product-card {
    text-decoration: none;
    color: inherit;
}

/* MEDIA */
.lx-product-media {
    position: relative;
    width: 100%;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    border-radius: 6px;
    background: #f5f5f5;
}

.lx-product-media img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;

    opacity: 0;
    transition: opacity .6s ease;
}

.lx-product-media img.is-active {
    opacity: 1;
}

/* TAG */
.lx-product-tag {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 2;

    background: rgba(0,0,0,.65);
    color: #fff;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 12px;
}

/* INFO */
.lx-product-info {
    padding: 10px 2px 16px;
}

.lx-product-name {
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 4px;
}

.lx-product-price {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
}

.lx-product-micro {
    font-size: 12px;
    color: #777;
}

/* COLORS */
.lx-product-colors {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}

.lx-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1px solid #ddd;
}

.lx-color-more {
    font-size: 11px;
    color: #555;
}

/* MOBILE */
@media (max-width: 480px) {
    .lx-product-name { font-size: 13px; }
    .lx-product-price { font-size: 13px; }
}
</style>

{{-- ===================================================== --}}
{{-- ⚙️ JS — AUTO IMAGE SWITCH (2 ẢNH / CARD) --}}
{{-- ===================================================== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('[data-image-switch]');

    cards.forEach(card => {
        const images = card.querySelectorAll('img');
        if (images.length < 2) return;

        let index = 0;

        setInterval(() => {
            images[index].classList.remove('is-active');
            index = index === 0 ? 1 : 0;
            images[index].classList.add('is-active');
        }, 1600); // tốc độ đổi ảnh (ms)
    });
});
</script>

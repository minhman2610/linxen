@extends('storefront.luxe.layouts.master')

@section('content')

{{-- ======================== --}}
{{-- 1. ANNOUNCEMENT BAR --}}
{{-- ======================== --}}
<div class="lx-announcement-bar">
    End of season sale up to 50% off. <a href="/linxen/sale">Shop Now</a>
</div>



{{-- ======================== --}}
{{-- 2. MOBILE HEADER --}}
{{-- ======================== --}}
<header class="lx-header">
    <button class="lx-header-btn">
        <img src="/themes/luxe/assets/icons/menu.svg" alt="Menu">
    </button>

    <button class="lx-header-btn">
        <img src="/themes/luxe/assets/icons/search.svg" alt="Search">
    </button>

    <div class="lx-header-logo">
        LUXE
    </div>

    <button class="lx-header-btn">
        <img src="/themes/luxe/assets/icons/user.svg" alt="User">
    </button>

    <button class="lx-header-btn cart-btn">
        <img src="/themes/luxe/assets/icons/cart.svg" alt="Cart">
        <span class="cart-count">0</span>
    </button>
</header>



{{-- ======================== --}}
{{-- 3. HERO FULLSCREEN --}}
{{-- ======================== --}}
<section class="lx-hero">
    <img src="/themes/luxe/assets/images/hero.jpg" class="lx-hero-img">

    <div class="lx-hero-text">
        <h1>TIMELESS STYLE FOR<br>MODERN LIVES</h1>

        <a href="/linxen/c/all" class="lx-btn-primary">
            SHOP ALL PRODUCTS
        </a>
    </div>
</section>



{{-- ======================== --}}
{{-- 4. TWO-BANNER SECTION --}}
{{-- ======================== --}}
<section class="lx-two-banners">
    <div class="banner-item">
        <img src="/themes/luxe/assets/images/banner1.jpg">
    </div>
    <div class="banner-item">
        <img src="/themes/luxe/assets/images/banner2.jpg">
    </div>
</section>



@endsection



{{-- ======================== --}}
{{-- MOBILE BOTTOM NAV --}}
{{-- ======================== --}}
@push('footer')
@include('storefront.luxe.components.bottom-nav')
@endpush



{{-- ======================== --}}
{{-- PAGE CSS --}}
{{-- ======================== --}}
@push('styles')
<style>

/* ----- Announcement Bar ----- */
.lx-announcement-bar {
    width: 100%;
    background: black;
    color: white;
    padding: 6px 12px;
    text-align: center;
    font-size: 12px;
}
.lx-announcement-bar a {
    color: white;
    text-decoration: underline;
}

/* ----- Header ----- */
.lx-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
}
.lx-header-btn img {
    width: 22px;
}
.lx-header-logo {
    font-size: 24px;
    font-weight: 700;
}

/* Cart badge */
.cart-btn {
    position: relative;
}
.cart-count {
    position: absolute;
    top: -4px;
    right: -4px;
    background: black;
    color: white;
    width: 16px;
    height: 16px;
    font-size: 11px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}


/* ----- HERO FULLSCREEN ----- */
.lx-hero {
    position: relative;
    width: 100%;
    height: 80vh;
    overflow: hidden;
}
.lx-hero-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.lx-hero-text {
    position: absolute;
    bottom: 16%;
    left: 0;
    right: 0;
    text-align: center;
    color: white;
}
.lx-hero-text h1 {
    font-size: 26px;
    font-weight: 700;
}
.lx-btn-primary {
    display: inline-block;
    margin-top: 12px;
    background: white;
    padding: 10px 22px;
    color: black;
    font-size: 14px;
    border-radius: 4px;
}


/* ----- TWO BANNERS ----- */
.lx-two-banners {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 12px;
}
.lx-two-banners img {
    width: 100%;
    border-radius: 6px;
}

</style>
@endpush

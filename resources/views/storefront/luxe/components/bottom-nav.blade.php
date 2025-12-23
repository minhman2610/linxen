<div class="lx-bottom-nav">

    {{-- HOME --}}
    <a href="{{ route('linxen.home') }}"
       class="lx-nav-item {{ request()->routeIs('linxen.home') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-home.svg" class="icon">
        <span>Trang chủ</span>
    </a>

    {{-- SEARCH --}}
    <a href="/search"
       class="lx-nav-item {{ request()->is('search') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-search.svg" class="icon">
        <span>Tìm kiếm</span>
    </a>

    {{-- VIDEO (HIGHLIGHT) --}}
    <a href="/video"
       class="lx-nav-item lx-nav-video {{ request()->is('video') ? 'active' : '' }}"
       aria-label="Xem video">
        <div class="lx-video-icon">
            ▶
        </div>
        <span>Video</span>
    </a>

    {{-- WISHLIST --}}
    <a href="/wishlist"
       class="lx-nav-item {{ request()->is('wishlist') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-heart.svg" class="icon">
        <span>Yêu thích</span>
    </a>

    {{-- CART --}}
    <a href="{{ route('linxen.cart') }}"
       class="lx-nav-item cart-btn {{ request()->routeIs('linxen.cart') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-cart.svg" class="icon">
        <span>Giỏ hàng</span>

        <span class="cart-count" id="lxCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </span>
    </a>

</div>
<style>
    /* =====================================================
   VIDEO TAB — HERO BUTTON
===================================================== */

.lx-nav-video {
    position: relative;
    top: -10px; /* nhô lên */
    color: #b11226;
}

/* ICON VIDEO */
.lx-video-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;

    background: linear-gradient(135deg, #b11226, #d42a3f);
    color: #fff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 18px;
    font-weight: 700;

    box-shadow: 0 6px 16px rgba(177,18,38,.35);
}

/* TEXT VIDEO */
.lx-nav-video span {
    margin-top: 2px;
    font-size: 10px;
    font-weight: 600;
}

/* ACTIVE VIDEO */
.lx-nav-video.active .lx-video-icon {
    box-shadow: 0 8px 20px rgba(177,18,38,.45);
}

</style>
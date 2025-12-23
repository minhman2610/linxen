<div class="lx-bottom-nav">

    {{-- HOME --}}
    <a href="{{ route('linxen.home') }}"
       class="lx-nav-item {{ request()->routeIs('linxen.home') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-home.svg" class="icon">
        <span class="lx-nav-text">Trang chủ</span>
    </a>

    {{-- SEARCH --}}
    <a href="/search"
       class="lx-nav-item {{ request()->is('search') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-search.svg" class="icon">
        <span class="lx-nav-text">Tìm kiếm</span>
    </a>

    {{-- REELS --}}
    <a href="/reels"
       class="lx-nav-item lx-nav-reels {{ request()->is('reels') ? 'active' : '' }}">
        <span class="lx-reels-bg">
            <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    <!-- rounded video frame -->
    <rect x="3" y="4" width="18" height="16" rx="4" ry="4" />

    <!-- vertical reel bar -->
    <rect x="7" y="4" width="2" height="16" rx="1" />

    <!-- play symbol (offset like TikTok) -->
    <polygon
        points="12,10 16,12 12,14"
        fill="currentColor"
        stroke="none"
    />
</svg>

        </span>
        <span class="lx-nav-text">Reels</span>
    </a>

    {{-- WISHLIST --}}
    <a href="/wishlist"
       class="lx-nav-item {{ request()->is('wishlist') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-heart.svg" class="icon">
        <span class="lx-nav-text">Yêu thích</span>
    </a>

    {{-- CART --}}
<a href="{{ route('linxen.cart') }}"
   class="lx-nav-item cart-btn {{ request()->routeIs('linxen.cart') ? 'active' : '' }}">

    <span class="lx-cart-icon-wrap">
        <img src="/themes/luxe/assets/icons/icon-cart.svg" class="icon">

        <span class="cart-count" id="lxCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </span>
    </span>

    <span class="lx-nav-text">Giỏ hàng</span>
</a>


</div>

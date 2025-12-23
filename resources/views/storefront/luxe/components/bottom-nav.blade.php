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
            <img src="/themes/luxe/assets/icons/icon-reels.svg" class="icon">
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
        <img src="/themes/luxe/assets/icons/icon-cart.svg" class="icon">
        <span class="lx-nav-text">Giỏ hàng</span>

        <span class="cart-count" id="lxCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </span>
    </a>

</div>

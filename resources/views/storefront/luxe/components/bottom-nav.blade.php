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

    {{-- WISHLIST --}}
    <a href="/wishlist"
       class="lx-nav-item {{ request()->is('wishlist') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-heart.svg" class="icon">
        <span>Yêu thích</span>
    </a>

    {{-- ACCOUNT --}}
    <a href="/account"
       class="lx-nav-item {{ request()->is('account') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-account.svg" class="icon">
        <span>Tài khoản</span>
    </a>

    {{-- CART --}}
    <a href="{{ route('linxen.cart') }}"
       class="lx-nav-item cart-btn {{ request()->routeIs('linxen.cart') ? 'active' : '' }}">
        <img src="/themes/luxe/assets/icons/icon-cart.svg" class="icon">
        <span>Giỏ hàng</span>

        {{-- Cart count (JS update) --}}
        <span class="cart-count" id="lxCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </span>
    </a>

</div>



<style>
/* -----------------------------------------------------
   LUXE — Mobile Bottom Navigation
------------------------------------------------------*/

.lx-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;

    height: 60px;
    background: #fff;
    border-top: 1px solid #e5e5e5;

    display: flex;
    justify-content: space-around;
    align-items: center;

    z-index: 999;
}

.lx-nav-item {
    flex: 1;
    text-align: center;
    text-decoration: none;
    color: #000;
    font-size: 11px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding-top: 4px;
}

.lx-nav-item .icon {
    width: 20px;
    height: 20px;
    margin-bottom: 2px;
}

.lx-nav-item.active span {
    font-weight: 600;
}

/* CART BADGE */
.cart-btn {
    position: relative;
}

.cart-count {
    position: absolute;
    top: 2px;
    right: 22px;
    background: #000;
    color: #fff;

    font-size: 10px;
    width: 16px;
    height: 16px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;
}

@media (min-width: 768px) {
    .lx-bottom-nav {
        display: none;
    }
}
</style>

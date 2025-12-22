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

        <span class="cart-count" id="lxCartCount">
            {{ array_sum(array_column(session('cart', []), 'qty')) }}
        </span>
    </a>

</div>

<style>
/* -----------------------------------------------------
   LUXE — Mobile Bottom Navigation (Compact & Clean)
------------------------------------------------------ */

.lx-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;

    height: 54px; /* 👈 BÉ LẠI */

    background: #ffffff; /* 🤍 nền trắng */

    /* 🌫 ĐỔ BÓNG TÁCH NỘI DUNG */
    box-shadow: 0 -4px 16px rgba(0,0,0,.08);

    display: flex;
    justify-content: space-around;
    align-items: center;

    z-index: 999;

    pointer-events: none;
}

/* Mobile mới cho click */
@media (max-width: 767px) {
    .lx-bottom-nav {
        pointer-events: auto;
    }
}

/* NAV ITEM */
.lx-nav-item {
    flex: 1;
    text-align: center;
    text-decoration: none;

    font-size: 10.5px;
    color: #666;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 2px;
    padding-top: 4px;
}

/* ICON — MÀU ĐỎ */
.lx-nav-item .icon {
    width: 18px;
    height: 18px;

    filter: invert(21%) sepia(88%) saturate(4222%) hue-rotate(350deg) brightness(92%) contrast(102%);
    /* 👆 ép icon SVG sang đỏ */
}

/* ACTIVE STATE */
.lx-nav-item.active {
    color: #b11226; /* 🔴 đỏ đậm */
}

.lx-nav-item.active .icon {
    filter: invert(21%) sepia(88%) saturate(4222%) hue-rotate(350deg) brightness(92%) contrast(110%);
}

/* CART */
.cart-btn {
    position: relative;
}

/* CART BADGE */
.cart-count {
    position: absolute;
    top: 2px;
    right: 20px;

    width: 14px;
    height: 14px;

    font-size: 9px;
    color: #fff;

    background: #b11226; /* 🔴 đỏ đồng bộ icon */

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;
}

/* DESKTOP: ẨN */
@media (min-width: 768px) {
    .lx-bottom-nav {
        display: none;
    }
}

</style>

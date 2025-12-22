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
   LUXE — Mobile Bottom Navigation (Premium)
------------------------------------------------------ */

.lx-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;

    height: 64px;

    /* 🌟 NỀN ẤN TƯỢNG – NÂU ẤM EDITORIAL */
    background: linear-gradient(
        180deg,
        #f7f2ed 0%,
        #efe7df 100%
    );

    border-top: 1px solid rgba(59,42,34,.18);

    display: flex;
    justify-content: space-around;
    align-items: center;

    z-index: 999;

    /* ❗ chỉ mobile mới ăn click */
    pointer-events: none;
}

/* Mobile enable click */
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

    font-size: 11px;
    color: #6a5f57; /* xám nâu dịu */

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 3px;
    padding-top: 6px;
}

/* ICON */
.lx-nav-item .icon {
    width: 20px;
    height: 20px;
    opacity: .75;
    transition: opacity .2s ease;
}

/* ACTIVE STATE */
.lx-nav-item.active {
    color: #3b2a22; /* deep brown LIN XÉN */
}

.lx-nav-item.active .icon {
    opacity: 1;
}

/* CART */
.cart-btn {
    position: relative;
}

/* CART BADGE */
.cart-count {
    position: absolute;
    top: 4px;
    right: 22px;

    width: 16px;
    height: 16px;

    font-size: 10px;
    color: #fff;

    background: linear-gradient(
        180deg,
        #4a3428,
        #2f2018
    );

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;
}

/* DESKTOP: HIDE */
@media (min-width: 768px) {
    .lx-bottom-nav {
        display: none;
    }
}

</style>
